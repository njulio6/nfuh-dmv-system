<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\NjangiCycle;
use App\Models\NjangiCycleMember;
use App\Models\NjangiSession;
use App\Models\NjangiSessionBeneficiary;
use App\Models\NjangiPaymentSubmission;
use App\Models\NjangiDisbursement;
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

        // Find member matching the user's email
        $member = Member::where('email', $user->email)->first();

        // Admin check: Explicitly check for Spatie admin role OR linked member admin roles
        $isAdmin = $user->hasRole('admin');
        
        if (!$isAdmin && $member) {
            $adminRoles = ['Secretary', 'Treasurer', 'Financial Secretary', 'Loan Officer', 'Lead Nformi'];
            $isAdmin = $member->roles()->whereIn('name', $adminRoles)->exists();
        }

        if ($isAdmin) {
            return view('dashboard');
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
        }

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
            'pendingLoanRequests'
        ));
    }
}
