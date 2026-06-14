<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\LoanRequest;
use App\Models\LoanGuarantor;
use App\Models\LoanRepayment;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanController extends Controller
{
    /**
     * Admin Dashboard for Loans
     */
    public function index(Request $request)
    {
        $query = LoanRequest::with(['member', 'guarantors.guarantorMember', 'repayments']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('member', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 30, 50])) {
            $perPage = 10;
        }

        $loans = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Metrics
        $totalActiveAmount = LoanRequest::where('status', 'active')->sum('amount');
        
        $totalRemainingBalance = 0;
        $activeLoans = LoanRequest::where('status', 'active')->with('repayments')->get();
        foreach ($activeLoans as $al) {
            $totalRemainingBalance += $al->remaining_balance;
        }

        $pendingReviewsCount = LoanRequest::where('status', 'pending_committee')->count();

        // Get members for dropdown filters / manual logging
        $allMembers = Member::orderBy('first_name')->get();

        return view('loans.index', compact('loans', 'totalActiveAmount', 'totalRemainingBalance', 'pendingReviewsCount', 'allMembers'));
    }

    /**
     * Admin Approve Loan (transitions from pending_committee to approved)
     */
    public function approve(Request $request, LoanRequest $loan)
    {
        if ($loan->status !== 'pending_committee') {
            return redirect()->back()->with('error', 'Loan is not in pending committee review status.');
        }

        $loan->update([
            'status' => 'approved',
            'admin_notes' => $request->input('notes'),
        ]);

        return redirect()->route('loans.index')->with('success', 'Loan request approved. Ready for disbursement.');
    }

    /**
     * Admin Disburse Loan (transitions from approved to active)
     */
    public function disburse(Request $request, LoanRequest $loan)
    {
        if ($loan->status !== 'approved') {
            return redirect()->back()->with('error', 'Loan must be approved before disbursement.');
        }

        $loan->update([
            'status' => 'active',
            'disbursed_at' => now(),
            'repayment_due_date' => now()->addMonths($loan->duration_months)->toDateString(),
        ]);

        return redirect()->route('loans.index')->with('success', 'Loan funds marked as disbursed and active.');
    }

    /**
     * Admin Reject Loan
     */
    public function reject(Request $request, LoanRequest $loan)
    {
        if (!in_array($loan->status, ['pending_guarantors', 'pending_committee', 'approved'])) {
            return redirect()->back()->with('error', 'Cannot reject loan at this stage.');
        }

        $loan->update([
            'status' => 'rejected',
            'admin_notes' => $request->input('notes'),
        ]);

        return redirect()->route('loans.index')->with('success', 'Loan request has been rejected.');
    }

    /**
     * Admin Record Manual Repayment
     */
    public function repay(Request $request, LoanRequest $loan)
    {
        if ($loan->status !== 'active') {
            return redirect()->back()->with('error', 'Can only record payments on active loans.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $remaining = $loan->remaining_balance;
        if ($validated['amount'] > $remaining + 0.01) {
            return redirect()->back()->with('error', 'Repayment amount exceeds the outstanding loan balance.');
        }

        LoanRepayment::create([
            'loan_request_id' => $loan->id,
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'],
            'payment_method' => $validated['payment_method'],
            'reference_number' => $validated['reference_number'],
            'notes' => $validated['notes'],
        ]);

        // If loan fully paid off, mark as completed
        if ($loan->fresh()->remaining_balance <= 0.01) {
            $loan->update(['status' => 'completed']);
        }

        return redirect()->route('loans.index')->with('success', 'Loan repayment recorded successfully.');
    }

    /**
     * Member personal loan portal
     */
    public function myLoans(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $member = Member::where('email', $user->email)->first();
        if (!$member) {
            abort(403, 'Unauthorized action. User profile is not linked to a member record.');
        }

        $loansQuery = $member->loanRequests()->with(['guarantors.guarantorMember', 'repayments']);

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 30, 50])) {
            $perPage = 10;
        }

        $loans = $loansQuery->orderBy('created_at', 'desc')->paginate($perPage);

        // Incoming guarantor requests
        $guarantorRequests = LoanGuarantor::where('guarantor_member_id', $member->id)
            ->where('status', 'pending')
            ->with(['loanRequest.member'])
            ->get();

        // Other members to select as guarantors
        $otherMembers = Member::where('id', '!=', $member->id)
            ->where('status', 'Active')
            ->orderBy('first_name')
            ->get();

        $appSettings = Setting::first();
        $minSavings = $appSettings?->min_savings_for_loan ?? 500.00;

        return view('loans.member', compact('member', 'loans', 'guarantorRequests', 'otherMembers', 'minSavings'));
    }

    /**
     * Member personal loan applications queue
     */
    public function myApplications(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $member = Member::where('email', $user->email)->first();
        if (!$member) {
            abort(403, 'Unauthorized action. User profile is not linked to a member record.');
        }

        $query = $member->loanRequests()->with(['guarantors.guarantorMember', 'repayments']);

        // Search amount or purpose
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('purpose', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 30, 50])) {
            $perPage = 10;
        }

        $loans = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Other members to select as guarantors
        $otherMembers = Member::where('id', '!=', $member->id)
            ->where('status', 'Active')
            ->orderBy('first_name')
            ->get();

        $appSettings = Setting::first();
        $minSavings = $appSettings?->min_savings_for_loan ?? 500.00;

        return view('loans.member_applications', compact('member', 'loans', 'otherMembers', 'minSavings'));
    }



    /**
     * Member Request Loan
     */
    public function requestLoan(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $member = Member::where('email', $user->email)->first();
        if (!$member) {
            abort(403, 'Unauthorized action. User profile is not linked to a member record.');
        }

        // Eligibility Check
        $appSettings = Setting::first();
        $minSavings = $appSettings?->min_savings_for_loan ?? 500.00;

        if ($member->savings_balance < $minSavings) {
            return redirect()->back()->with('error', "You must have at least $" . number_format($minSavings, 2) . " in savings to apply for a loan. Current savings: $" . number_format($member->savings_balance, 2));
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:60'],
            'purpose' => ['nullable', 'string', 'max:1000'],
            'guarantors' => ['required', 'array', 'min:1', 'max:3'],
            'guarantors.*' => ['required', 'exists:members,id', 'different:member_id'],
        ]);

        // Ensure member doesn't select themselves
        if (in_array($member->id, $validated['guarantors'])) {
            return redirect()->back()->with('error', 'You cannot select yourself as a guarantor.');
        }

        // Create Loan request
        $loan = LoanRequest::create([
            'member_id' => $member->id,
            'organization_id' => $member->organization_id,
            'amount' => $validated['amount'],
            'duration_months' => $validated['duration_months'],
            'purpose' => $validated['purpose'],
            'status' => 'pending_guarantors',
        ]);

        // Attach guarantors
        foreach ($validated['guarantors'] as $guarantorId) {
            LoanGuarantor::create([
                'loan_request_id' => $loan->id,
                'guarantor_member_id' => $guarantorId,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('member.loans.applications')->with('success', 'Loan request submitted successfully. Awaiting guarantor approvals.');
    }

    /**
     * Guarantor approve request
     */
    public function approveGuarantee(Request $request, LoanGuarantor $guarantor)
    {
        $user = Auth::user();
        $member = Member::where('email', $user->email)->first();

        if (!$member || $guarantor->guarantor_member_id !== $member->id) {
            abort(403, 'Unauthorized guarantor response.');
        }

        if ($guarantor->status !== 'pending') {
            return redirect()->back()->with('error', 'You have already responded to this guarantor request.');
        }

        $guarantor->update([
            'status' => 'approved',
            'responded_at' => now(),
            'notes' => $request->input('notes'),
        ]);

        // Check if all guarantors have approved
        $loan = $guarantor->loanRequest;
        $totalGuarantors = $loan->guarantors()->count();
        $approvedGuarantors = $loan->guarantors()->where('status', 'approved')->count();

        if ($totalGuarantors === $approvedGuarantors) {
            $loan->update(['status' => 'pending_committee']);
        }

        return redirect()->route('member.loans')->with('success', 'Guarantor request approved successfully.');
    }

    /**
     * Guarantor decline request
     */
    public function declineGuarantee(Request $request, LoanGuarantor $guarantor)
    {
        $user = Auth::user();
        $member = Member::where('email', $user->email)->first();

        if (!$member || $guarantor->guarantor_member_id !== $member->id) {
            abort(403, 'Unauthorized guarantor response.');
        }

        if ($guarantor->status !== 'pending') {
            return redirect()->back()->with('error', 'You have already responded to this guarantor request.');
        }

        $guarantor->update([
            'status' => 'declined',
            'responded_at' => now(),
            'notes' => $request->input('notes'),
        ]);

        // If any guarantor declines, transition loan to rejected
        $loan = $guarantor->loanRequest;
        $loan->update(['status' => 'rejected']);

        return redirect()->route('member.loans')->with('success', 'Guarantor request declined. The loan request has been automatically rejected.');
    }

    /**
     * Printable member statement for loans
     */
    public function memberStatement(Member $member)
    {
        $member->load(['rank', 'organization']);

        $loans = $member->loanRequests()
            ->with(['repayments', 'guarantors.guarantorMember'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('loans.statement', compact('member', 'loans'));
    }

    /**
     * Member views their own loan statement (member portal)
     */
    public function myStatement()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $member = Member::where('email', $user->email)->first();
        if (!$member) {
            abort(403, 'Unauthorized action. User profile is not linked to a member record.');
        }

        $member->load(['rank', 'organization']);

        $loans = $member->loanRequests()
            ->with(['repayments', 'guarantors.guarantorMember'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('loans.statement', compact('member', 'loans'));
    }
}

