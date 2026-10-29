<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\NjangiCycle;
use App\Models\NjangiCycleMember;
use App\Models\NjangiSession;
use App\Models\NjangiSessionBeneficiary;
use App\Models\NjangiPaymentSubmission;
use App\Models\NjangiDisbursement;
use App\Support\MemberResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Find member linked to this user (by user_id, falling back to email)
        $member = MemberResolver::fromUser($user);

        // Admin/Officer check: Check for Spatie officer roles OR legacy member roles
        $officerRoles = ['admin', 'Treasurer', 'Financial Secretary', 'Loan Committee', 'General Secretary'];
        $isAdmin = method_exists($user, 'hasAnyRole') && $user->hasAnyRole($officerRoles);
        
        if (!$isAdmin && $member) {
            $adminRoles = ['Secretary', 'Treasurer', 'Financial Secretary', 'Loan Officer', 'Lead Nformi', 'Loan Committee', 'General Secretary'];
            $isAdmin = $member->roles()->whereIn('name', $adminRoles)->exists();
        }

        $isDualRole = $isAdmin && ($member !== null);
        $portalMode = session('portal_mode', 'officer');

        if ($isAdmin && (!$member || $portalMode === 'officer')) {
            // Permission checks
            $canViewMembers = method_exists($user, 'can') ? ($user->can('members.view') || $user->hasRole('admin')) : true;
            $canViewNjangi = method_exists($user, 'can') ? ($user->can('njangi.view_cycles') || $user->hasAnyRole(['admin', 'Financial Secretary', 'Treasurer'])) : true;
            $canViewSavings = method_exists($user, 'can') ? ($user->can('savings.view_all') || $user->hasAnyRole(['admin', 'Treasurer', 'Financial Secretary'])) : true;
            $canViewLoans = method_exists($user, 'can') ? ($user->can('loans.view_all') || $user->hasAnyRole(['admin', 'Loan Committee', 'Treasurer'])) : true;
            $canViewAttendance = method_exists($user, 'can') ? ($user->can('attendance.view') || $user->can('attendance.mark') || $user->hasAnyRole(['admin', 'General Secretary'])) : true;
            $canViewGovernance = method_exists($user, 'can') ? ($user->can('governance.view_roles') || $user->hasRole('admin')) : true;

            // Global Metrics
            $totalMembersCount = \App\Models\Member::count();
            $activeMembersCount = \App\Models\Member::where('status', 'active')->count();
            $newMembersThisMonth = \App\Models\Member::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

            $activeCyclesCount = \App\Models\NjangiCycle::where('status', 'active')->count();
            $totalContributionsVolume = (float) \App\Models\NjangiContribution::sum('amount');
            $contributionsThisMonth = (float) \App\Models\NjangiContribution::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount');

            $totalSavingsDeposits = (float) \App\Models\SavingsTransaction::whereIn('type', ['deposit', 'adjustment'])->where('status', 'approved')->sum('amount');
            $totalSavingsWithdrawals = (float) \App\Models\SavingsTransaction::where('type', 'withdrawal')->where('status', 'approved')->sum('amount');
            $totalSavingsBalance = (float) max(0, $totalSavingsDeposits - $totalSavingsWithdrawals);

            $reservedWithdrawals = (float) \App\Models\SavingsWithdrawalRequest::whereIn('status', ['pending', 'approved', 'pending_approval', 'pending_disbursement'])->sum('gross_amount');
            $totalAvailableSavings = (float) max(0, $totalSavingsBalance - $reservedWithdrawals);
            $savingsDepositsThisMonth = (float) \App\Models\SavingsTransaction::where('type', 'deposit')->where('status', 'approved')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount');

            $activeLoans = \App\Models\LoanRequest::whereIn('status', ['active', 'defaulted'])->with('repayments')->get();
            $activeLoansCount = $activeLoans->count();
            $activeLoansRemainingBalance = (float) $activeLoans->sum(fn($loan) => $loan->remaining_balance);
            $totalLoanDisbursed = (float) \App\Models\LoanRequest::whereIn('status', ['active', 'repaid', 'defaulted'])->sum('amount');
            $totalLoansRepaid = (float) \App\Models\LoanRepayment::sum('amount');
            $loanRepaymentRate = $totalLoanDisbursed > 0 ? min(100, round(($totalLoansRepaid / $totalLoanDisbursed) * 100, 1)) : 100;
            $monthlyInflowTotal = (float) ($contributionsThisMonth + $savingsDepositsThisMonth);
            $totalManagedCapital = (float) ($totalContributionsVolume + $totalSavingsBalance);

            $totalAttendancesCount = \App\Models\MeetingAttendance::whereNotNull('attendance_status')->count();
            $presentAttendancesCount = \App\Models\MeetingAttendance::where('attendance_status', 'present')->count();
            $overallAttendanceRate = $totalAttendancesCount > 0 ? round(($presentAttendancesCount / $totalAttendancesCount) * 100) : 0;
            $totalSessionsCount = \App\Models\NjangiSession::count();

            // Urgent Officer Queues
            $pendingNjangiSubmissions = \App\Models\NjangiPaymentSubmission::where('status', 'pending')->count();
            $pendingSavingsDeposits = \App\Models\SavingsDepositRequest::where('status', 'pending')->count();
            $pendingSavingsWithdrawals = \App\Models\SavingsWithdrawalRequest::whereIn('status', ['pending_approval', 'pending_disbursement'])->count();
            $pendingCommitteeLoans = \App\Models\LoanRequest::where('status', 'pending_committee')->count();
            $pendingDisbursementLoans = \App\Models\LoanRequest::where('status', 'approved_pending_disbursement')->count();
            $pendingRepaymentRequests = \App\Models\LoanRepaymentRequest::where('status', 'pending')->count();

            // Next Assembly & Meeting Attendance
            $upcomingSession = \App\Models\NjangiSession::whereIn('status', ['open', 'scheduled'])
                ->where('session_date', '>=', now()->toDateString())
                ->orderBy('session_date', 'asc')
                ->first();

            $upcomingRsvpSummary = [
                'attending' => 0,
                'not_attending' => 0,
                'undecided' => 0,
                'total_eligible' => $activeMembersCount,
            ];

            if ($upcomingSession) {
                $upcomingRsvps = \App\Models\MeetingAttendance::where('njangi_session_id', $upcomingSession->id)->get();
                $upcomingRsvpSummary['attending'] = $upcomingRsvps->where('rsvp_status', 'attending')->count();
                $upcomingRsvpSummary['not_attending'] = $upcomingRsvps->where('rsvp_status', 'not_attending')->count();
                $upcomingRsvpSummary['undecided'] = max(0, $activeMembersCount - ($upcomingRsvpSummary['attending'] + $upcomingRsvpSummary['not_attending']));
            }

            // Monthly Activity Trends for current year
            $selectedYear = date('Y');
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $monthlyTrends = [];
            foreach ($months as $idx => $mName) {
                $mNum = $idx + 1;
                $njangiVol = (float) \App\Models\NjangiContribution::whereYear('created_at', $selectedYear)->whereMonth('created_at', $mNum)->sum('amount');
                $savingsVol = (float) \App\Models\SavingsTransaction::where('type', 'deposit')->where('status', 'approved')->whereYear('created_at', $selectedYear)->whereMonth('created_at', $mNum)->sum('amount');
                $loanVol = (float) \App\Models\LoanRepayment::whereYear('created_at', $selectedYear)->whereMonth('created_at', $mNum)->sum('amount');

                $monthlyTrends[] = [
                    'month' => $mName,
                    'njangi' => $njangiVol,
                    'savings' => $savingsVol,
                    'loans' => $loanVol,
                ];
            }

            // Chart Data Breakdowns
            $loanStatusDistribution = [
                'active' => \App\Models\LoanRequest::where('status', 'active')->count(),
                'repaid' => \App\Models\LoanRequest::where('status', 'repaid')->count(),
                'defaulted' => \App\Models\LoanRequest::where('status', 'defaulted')->count(),
                'pending' => \App\Models\LoanRequest::whereIn('status', ['pending_guarantors', 'pending_committee', 'approved_pending_disbursement'])->count(),
            ];

            $memberStatusDistribution = [
                'active' => $activeMembersCount,
                'inactive' => max(0, $totalMembersCount - $activeMembersCount),
            ];

            $capitalComposition = [
                'njangi' => $totalContributionsVolume,
                'savings' => $totalSavingsBalance,
                'loans' => $activeLoansRemainingBalance,
            ];

            // Recent Activity Feeds
            $recentSubmissions = \App\Models\NjangiPaymentSubmission::with('member')->latest()->take(5)->get();
            $recentLoanRequests = \App\Models\LoanRequest::with('member')->latest()->take(5)->get();
            $recentSavingsRequests = \App\Models\SavingsDepositRequest::with('member')->latest()->take(5)->get();
            $recentMembers = \App\Models\Member::with('rank')->latest()->take(5)->get();

            $appSettings = \App\Models\Setting::first();

            return view('dashboard', compact(
                'canViewMembers',
                'canViewNjangi',
                'canViewSavings',
                'canViewLoans',
                'canViewAttendance',
                'canViewGovernance',
                'totalMembersCount',
                'activeMembersCount',
                'newMembersThisMonth',
                'activeCyclesCount',
                'totalContributionsVolume',
                'contributionsThisMonth',
                'totalSavingsBalance',
                'totalAvailableSavings',
                'savingsDepositsThisMonth',
                'activeLoansCount',
                'activeLoansRemainingBalance',
                'totalLoanDisbursed',
                'totalLoansRepaid',
                'loanRepaymentRate',
                'monthlyInflowTotal',
                'totalManagedCapital',
                'overallAttendanceRate',
                'totalSessionsCount',
                'pendingNjangiSubmissions',
                'pendingSavingsDeposits',
                'pendingSavingsWithdrawals',
                'pendingCommitteeLoans',
                'pendingDisbursementLoans',
                'pendingRepaymentRequests',
                'upcomingSession',
                'upcomingRsvpSummary',
                'monthlyTrends',
                'loanStatusDistribution',
                'memberStatusDistribution',
                'capitalComposition',
                'recentSubmissions',
                'recentLoanRequests',
                'recentSavingsRequests',
                'recentMembers',
                'appSettings'
            ));
        }

        // If the user is neither an admin nor linked to a member, deny access
        if (!$member) {
            abort(403, 'Unauthorized action. User profile is not linked to a member record.');
        }

        // --- Member Portal Logic ---
        // Fetch all active cycles that the member is actually enrolled in.
        $memberCycles = collect();
        if ($member) {
            $memberCycles = NjangiCycle::where('status', 'active')
                ->whereHas('cycleMembers', function ($q) use ($member) {
                    $q->where('member_id', $member->id);
                })->get();
        }

        $activeCycle = null;
        if ($member && $memberCycles->isNotEmpty()) {
            $selectedCycleId = $request->input('cycle_id');
            if ($selectedCycleId) {
                $activeCycle = $memberCycles->firstWhere('id', $selectedCycleId);
            }
            if (!$activeCycle) {
                $activeCycle = $memberCycles->first();
            }
        }

        if (!$activeCycle) {
            $activeCycle = NjangiCycle::where('status', 'active')->first();
        }

        if (!$activeCycle) {
            $activeCycle = NjangiCycle::orderBy('id', 'desc')->first();
        }

        $cycleMember = null;
        $benefitOrder = null;
        $hasBenefited = false;
        $benefitSession = null;
        $activeSession = null;
        $sessions = collect();

        if ($activeCycle && $member) {
            $cycleMember = NjangiCycleMember::where('njangi_cycle_id', $activeCycle->id)
                ->where('member_id', $member->id)
                ->first();

            if ($cycleMember) {
                $benefitOrder = $cycleMember->benefit_order;

                $hasBenefited = NjangiDisbursement::where('njangi_cycle_member_id', $cycleMember->id)
                    ->where('status', 'paid')
                    ->exists()
                    || NjangiSessionBeneficiary::where('njangi_cycle_member_id', $cycleMember->id)
                        ->whereHas('session', function ($q) {
                            $q->where('status', 'closed');
                        })->exists();

                $sessionBeneficiary = NjangiSessionBeneficiary::where('njangi_cycle_member_id', $cycleMember->id)
                    ->first();
                if ($sessionBeneficiary) {
                    $benefitSession = $sessionBeneficiary->session;
                }
            }

            // Get open sessions or scheduled sessions for the dropdown
            $sessions = NjangiSession::where('njangi_cycle_id', $activeCycle->id)
                ->whereIn('status', ['open', 'scheduled'])
                ->orderBy('session_date')
                ->get();

            // Find current active/open session to default in form
            $activeSession = NjangiSession::where('njangi_cycle_id', $activeCycle->id)
                ->where('status', 'open')
                ->with(['beneficiaries.cycleMember.member'])
                ->first()
                ?? NjangiSession::where('njangi_cycle_id', $activeCycle->id)
                    ->where('status', 'scheduled')
                    ->with(['beneficiaries.cycleMember.member'])
                    ->orderBy('session_date')
                    ->first();
        }


        // Fetch Njangi contributions and refund/ledger reports
        $contributionsMade = collect();
        $contributionsReceived = collect();
        $refundSummary = collect();

        if ($activeCycle && $member) {
            $contributionsMade = \App\Models\NjangiContribution::where('contributor_member_id', $member->id)
                ->where('njangi_cycle_id', $activeCycle->id)
                ->with(['beneficiary', 'session'])
                ->orderBy('created_at', 'desc')
                ->get();

            $contributionsReceived = \App\Models\NjangiContribution::where('beneficiary_member_id', $member->id)
                ->where('njangi_cycle_id', $activeCycle->id)
                ->with(['contributor', 'session'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Calculate refund summary
            $cycleBeneficiaries = NjangiSessionBeneficiary::whereHas('session', function ($q) use ($activeCycle) {
                $q->where('njangi_cycle_id', $activeCycle->id);
            })
            ->with(['cycleMember.member', 'session'])
            ->get();


            $refundSummary = $cycleBeneficiaries->groupBy('njangi_cycle_member_id')->map(function ($beneficiarySessions) use ($member, $activeCycle) {
                $first = $beneficiarySessions->first();
                if (!$first || !$first->cycleMember || !$first->cycleMember->member) return null;

                $beneficiaryMember = $first->cycleMember->member;
                if ($beneficiaryMember->id === $member->id) return null;

                // What we paid to B when B benefited
                $amountRefunded = \App\Models\NjangiContribution::where('contributor_member_id', $member->id)
                    ->where('beneficiary_member_id', $beneficiaryMember->id)
                    ->where('njangi_cycle_id', $activeCycle->id)
                    ->sum('amount');

                // What B contributed to us when we benefited (our expected refund obligation to B)
                $expectedToRefund = \App\Models\NjangiContribution::where('contributor_member_id', $beneficiaryMember->id)
                    ->where('beneficiary_member_id', $member->id)
                    ->where('njangi_cycle_id', $activeCycle->id)
                    ->sum('amount');

                $outstanding = max(0, $expectedToRefund - $amountRefunded);

                // Determine dynamic status
                if ($expectedToRefund > 0) {
                    if ($outstanding <= 0) {
                        $status = 'Settled';
                    } elseif ($amountRefunded > 0) {
                        $status = 'Partial';
                    } else {
                        $status = 'Pending';
                    }
                } else {
                    $status = $amountRefunded > 0 ? 'Paid' : 'Not Paid';
                }

                return [
                    'beneficiary_name'   => $beneficiaryMember->first_name . ' ' . $beneficiaryMember->last_name,
                    'expected_to_refund' => $expectedToRefund,
                    'amount_refunded'    => $amountRefunded,
                    'outstanding'        => $outstanding,
                    'status'             => $status,
                ];
            })
            ->filter()
            ->values();
        }

        $activeLoans = collect();
        $pendingGuarantees = collect();
        $pendingSavingsRequests = collect();
        $pendingRepayRequests = collect();
        $pendingLoanRequests = collect();

        if ($member) {
            $activeLoans = $member->loanRequests()
                ->whereIn('status', ['active', 'defaulted'])
                ->with(['repayments', 'guarantors.guarantorMember'])
                ->get();

            $pendingGuarantees = \App\Models\LoanGuarantor::where('guarantor_member_id', $member->id)
                ->where('status', 'pending')
                ->with('loanRequest.member')
                ->get();

            $pendingSavingsRequests = \App\Models\SavingsDepositRequest::where('member_id', $member->id)
                ->where('status', 'pending')
                ->get();

            $pendingRepayRequests = \App\Models\LoanRepaymentRequest::where('member_id', $member->id)
                ->where('status', 'pending')
                ->get();

            $pendingLoanRequests = \App\Models\LoanRequest::where('member_id', $member->id)
                ->whereIn('status', ['pending_guarantors', 'pending_committee'])
                ->get();

            // Attendance & RSVP Snapshot
            $attendanceRate = $member->attendanceRate();
            $verifiedAttendanceCount = $member->attendances()->whereNotNull('attendance_status')->count();

            $upcomingSession = \App\Models\NjangiSession::where('organization_id', $member->organization_id)
                ->whereIn('status', ['open', 'scheduled'])
                ->where('session_date', '>=', now()->toDateString())
                ->orderBy('session_date', 'asc')
                ->first();

            $upcomingAttendance = null;
            if ($upcomingSession) {
                $upcomingAttendance = \App\Models\MeetingAttendance::where('njangi_session_id', $upcomingSession->id)
                    ->where('member_id', $member->id)
                    ->first();
            }

            // Personal combined ledger activity
            $personalSavings = \App\Models\SavingsTransaction::where('member_id', $member->id)
                ->where('status', 'approved')
                ->latest('transaction_date')
                ->take(6)
                ->get()
                ->map(fn($t) => [
                    'type' => 'savings',
                    'title' => 'Savings ' . ucfirst($t->type),
                    'amount' => (float) $t->amount,
                    'is_credit' => $t->type === 'deposit',
                    'date' => $t->transaction_date ? \Carbon\Carbon::parse($t->transaction_date)->format('M d, Y') : $t->created_at->format('M d, Y'),
                    'badge' => ucfirst($t->type),
                    'badge_color' => $t->type === 'deposit' ? 'emerald' : 'amber',
                ]);

            $personalNjangi = \App\Models\NjangiContribution::where('contributor_member_id', $member->id)
                ->latest()
                ->take(6)
                ->get()
                ->map(fn($c) => [
                    'type' => 'njangi',
                    'title' => 'Njangi Round Contribution',
                    'amount' => (float) $c->amount,
                    'is_credit' => true,
                    'date' => $c->created_at->format('M d, Y'),
                    'badge' => 'Njangi',
                    'badge_color' => 'blue',
                ]);

            $personalRepayments = \App\Models\LoanRepayment::whereHas('loanRequest', fn($q) => $q->where('member_id', $member->id))
                ->latest('payment_date')
                ->take(6)
                ->get()
                ->map(fn($r) => [
                    'type' => 'loan',
                    'title' => 'Loan Repayment',
                    'amount' => (float) $r->amount,
                    'is_credit' => false,
                    'date' => $r->payment_date ? \Carbon\Carbon::parse($r->payment_date)->format('M d, Y') : $r->created_at->format('M d, Y'),
                    'badge' => 'Repaid',
                    'badge_color' => 'violet',
                ]);

            $personalActivity = $personalSavings->concat($personalNjangi)->concat($personalRepayments)
                ->sortByDesc('date')
                ->take(6)
                ->values();

            $totalActiveLoanDebt = (float) $activeLoans->sum('remaining_balance');
            $netFinancialPosition = (float) ($member->savings_balance - $totalActiveLoanDebt);

            // Member Chart Breakdowns
            $memberPresentCount = $member->attendances()->where('attendance_status', 'present')->count();
            $memberAbsentCount = $member->attendances()->where('attendance_status', 'absent')->count();
            $memberExcusedCount = $member->attendances()->where('attendance_status', 'excused')->count();
            $memberTotalContributions = (float) \App\Models\NjangiContribution::where('contributor_member_id', $member->id)->sum('amount');
            $memberLoanRepaidTotal = (float) \App\Models\LoanRepayment::whereHas('loanRequest', fn($q) => $q->where('member_id', $member->id))->sum('amount');
            $memberTotalBorrowed = (float) $member->loanRequests()->whereIn('status', ['active', 'repaid', 'defaulted'])->sum('amount');
            $activeGuarantorCount = \App\Models\LoanGuarantor::where('guarantor_member_id', $member->id)
                ->where('status', 'accepted')
                ->whereHas('loanRequest', fn($q) => $q->whereIn('status', ['active', 'approved_pending_disbursement']))
                ->count();
            $totalGuaranteedAmount = (float) \App\Models\LoanRequest::where('status', 'active')
                ->whereHas('guarantors', function ($q) use ($member) {
                    $q->where('guarantor_member_id', $member->id)
                        ->where('status', 'accepted');
                })
                ->get()
                ->sum(fn($loan) => $loan->remaining_balance);
        } else {
            $personalActivity = collect();
            $totalActiveLoanDebt = 0.0;
            $netFinancialPosition = 0.0;
            $memberPresentCount = 0;
            $memberAbsentCount = 0;
            $memberExcusedCount = 0;
            $memberTotalContributions = 0.0;
            $memberLoanRepaidTotal = 0.0;
            $memberTotalBorrowed = 0.0;
            $activeGuarantorCount = 0;
            $totalGuaranteedAmount = 0.0;
        }

        $appSettings = \App\Models\Setting::first();
        $nfuhTerms = config('nfuh.terms');

        return view('dashboard.member', compact(
            'member',
            'activeCycle',
            'cycleMember',
            'benefitOrder',
            'hasBenefited',
            'benefitSession',
            'activeSession',
            'sessions',
            'memberCycles',
            'contributionsMade',
            'contributionsReceived',
            'refundSummary',
            'activeLoans',
            'pendingGuarantees',
            'pendingSavingsRequests',
            'pendingRepayRequests',
            'pendingLoanRequests',
            'attendanceRate',
            'verifiedAttendanceCount',
            'upcomingSession',
            'upcomingAttendance',
            'personalActivity',
            'totalActiveLoanDebt',
            'netFinancialPosition',
            'memberPresentCount',
            'memberAbsentCount',
            'memberExcusedCount',
            'memberTotalContributions',
            'memberLoanRepaidTotal',
            'memberTotalBorrowed',
            'activeGuarantorCount',
            'totalGuaranteedAmount',
            'appSettings',
            'nfuhTerms'
        ));
    }

    /**
     * Switch between Officer and Member portal views for dual-role users.
     */
    public function switchPortalMode(Request $request, string $mode)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $officerRoles = ['admin', 'Treasurer', 'Financial Secretary', 'Loan Committee', 'General Secretary'];
        $isOfficer = method_exists($user, 'hasAnyRole') && $user->hasAnyRole($officerRoles);
        $member = MemberResolver::fromUser($user);

        if (!$isOfficer && $member) {
            $adminRoles = ['Secretary', 'Treasurer', 'Financial Secretary', 'Loan Officer', 'Lead Nformi', 'Loan Committee', 'General Secretary'];
            $isOfficer = $member->roles()->whereIn('name', $adminRoles)->exists();
        }

        if ($mode === 'officer' && $isOfficer) {
            session(['portal_mode' => 'officer']);
        } elseif ($mode === 'member' && $member) {
            session(['portal_mode' => 'member']);
        }

        return redirect()->route('dashboard');
    }
}
