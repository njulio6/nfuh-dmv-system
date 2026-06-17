<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\LoanRequest;
use App\Models\LoanGuarantor;
use App\Models\LoanRepayment;
use App\Models\Setting;
use App\Models\LoanSubStatus;
use App\Models\LoanRepaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanController extends Controller
{
    /**
     * Admin Dashboard for Loans (Option B - Card Grid)
     */
    public function index()
    {
        // Counts for all loan request statuses
        $counts = [
            'pending_guarantors' => LoanRequest::where('status', 'pending_guarantors')->count(),
            'pending_committee'  => LoanRequest::where('status', 'pending_committee')->count(),
            'approved'           => LoanRequest::where('status', 'approved')->count(),
            'active'             => LoanRequest::where('status', 'active')->count(),
            'defaulted'          => LoanRequest::where('status', 'defaulted')->count(),
            'completed'          => LoanRequest::where('status', 'completed')->count(),
            'rejected'           => LoanRequest::where('status', 'rejected')->count(),
            'repayments'         => LoanRepayment::count(),
        ];

        // Metrics
        $totalActiveAmount = LoanRequest::whereIn('status', ['active', 'defaulted'])->sum('amount');
        
        $totalRemainingBalance = 0;
        $activeLoans = LoanRequest::whereIn('status', ['active', 'defaulted'])->with('repayments')->get();
        foreach ($activeLoans as $al) {
            $totalRemainingBalance += $al->remaining_balance;
        }

        $totalRepaymentsCollected = LoanRepayment::sum('amount');

        $totalDefaultedBalance = 0;
        $defaultedLoans = LoanRequest::where('status', 'defaulted')->with('repayments')->get();
        foreach ($defaultedLoans as $dl) {
            $totalDefaultedBalance += $dl->remaining_balance;
        }

        return view('loans.index', compact(
            'counts', 
            'totalActiveAmount', 
            'totalRemainingBalance',
            'totalRepaymentsCollected',
            'totalDefaultedBalance'
        ));
    }

    /**
     * Display a specific status queue list of loan requests.
     */
    public function statusList(Request $request, $status)
    {
        $validStatuses = ['pending_guarantors', 'pending_committee', 'approved', 'active', 'completed', 'rejected', 'defaulted'];
        if (!in_array($status, $validStatuses)) {
            abort(404, 'Status queue not found.');
        }

        $query = LoanRequest::where('status', $status)
            ->with(['member', 'guarantors.guarantorMember', 'repayments', 'subStatus']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('member', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 30, 50])) {
            $perPage = 10;
        }

        $loans = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Fetch all custom sub-statuses for layout rendering / assigning tags
        $subStatuses = LoanSubStatus::orderBy('name')->get();
        
        // Needed for Log Repay modal dropdowns
        $allMembers = Member::orderBy('first_name')->get();

        return view('loans.status_list', compact('loans', 'status', 'subStatuses', 'allMembers'));
    }

    /**
     * Display a table of verified repayment transactions.
     */
    public function repaymentsLog(Request $request)
    {
        $query = LoanRepayment::with(['loanRequest.member']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('loanRequest.member', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%");
            })->orWhere('notes', 'like', "%{$search}%")
              ->orWhere('reference_number', 'like', "%{$search}%");
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 30, 50])) {
            $perPage = 10;
        }

        $repayments = $query->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return view('loans.repayments_log', compact('repayments'));
    }

    /**
     * Admin Approve Loan (transitions from pending_committee to approved)
     */
    public function approve(Request $request, LoanRequest $loan)
    {
        if ($loan->status !== 'pending_committee') {
            return redirect()->back()->with('error', 'Loan is not in pending committee review status.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
            'duration_months' => ['nullable', 'integer', 'min:1'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'interest_type' => ['required', 'string', 'in:flat,duration_based'],
        ]);

        $updateData = [
            'status' => 'approved',
            'admin_notes' => $validated['notes'] ?? null,
            'interest_rate' => (float) $validated['interest_rate'],
            'interest_type' => $validated['interest_type'],
        ];

        if (!empty($validated['duration_months'])) {
            $updateData['duration_months'] = (int) $validated['duration_months'];
        }

        $loan->update($updateData);

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
        if (!in_array($loan->status, ['active', 'defaulted'])) {
            return redirect()->back()->with('error', 'Can only record payments on active or defaulted loans.');
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

        $loansQuery = $member->loanRequests()->with(['guarantors.guarantorMember', 'repayments', 'subStatus']);

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

        $query = $member->loanRequests()->with(['guarantors.guarantorMember', 'repayments', 'subStatus']);

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

        // Fetch statistics across ALL of the member's loan requests
        $allLoans = $member->loanRequests()->with('repayments')->get();
        $activePrincipal      = (float) $allLoans->sum('amount');
        $activeTotalRepayable = (float) $allLoans->sum('total_repayable');
        $outstandingBalance   = (float) $allLoans->sum('remaining_balance');
        $totalRepaid          = (float) $allLoans->sum(function ($loan) {
            return $loan->repayments->sum('amount');
        });

        // Other members to select as guarantors
        $otherMembers = Member::where('id', '!=', $member->id)
            ->where('status', 'Active')
            ->orderBy('first_name')
            ->get();

        $appSettings = Setting::first();
        $minSavings = $appSettings?->min_savings_for_loan ?? 500.00;
        $minGuarantors = $appSettings?->loan_guarantor_min ?? 1;
        $maxGuarantors = $appSettings?->loan_guarantor_max ?? 3;

        return view('loans.member_applications', compact(
            'member', 
            'loans', 
            'otherMembers', 
            'minSavings', 
            'minGuarantors', 
            'maxGuarantors',
            'activePrincipal',
            'activeTotalRepayable',
            'outstandingBalance',
            'totalRepaid'
        ));
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

        $guarantorMin = $appSettings?->loan_guarantor_min ?? 1;
        $guarantorMax = $appSettings?->loan_guarantor_max ?? 3;

        $validated = $request->validate([
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:60'],
            'purpose'         => ['nullable', 'string', 'max:1000'],
            'guarantors'      => ['required', 'array', 'min:' . $guarantorMin, 'max:' . $guarantorMax],
            'guarantors.*'    => ['required', 'exists:members,id', 'different:member_id'],
        ], [
            'guarantors.min' => "You must select at least {$guarantorMin} guarantor(s) for your loan request.",
            'guarantors.max' => "You may select no more than {$guarantorMax} guarantor(s) for your loan request.",
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
    public function memberStatement(LoanRequest $loan)
    {
        $member = $loan->member;
        $member->load(['rank', 'organization']);

        $loans = [$loan->load(['repayments', 'guarantors.guarantorMember'])];

        return view('loans.statement', compact('member', 'loans'));
    }

    /**
     * Member views their own loan statement (member portal)
     */
    public function myStatement(LoanRequest $loan)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $member = Member::where('email', $user->email)->first();
        if (!$member) {
            abort(403, 'Unauthorized action. User profile is not linked to a member record.');
        }

        // Security check: ensure the logged-in member owns this loan
        if ($loan->member_id !== $member->id) {
            abort(403, 'Unauthorized action. You do not own this loan.');
        }

        $member->load(['rank', 'organization']);

        $loans = [$loan->load(['repayments', 'guarantors.guarantorMember'])];

        return view('loans.statement', compact('member', 'loans'));
    }

    /**
     * Update the operational sub-status of a loan.
     */
    public function updateSubStatus(Request $request, LoanRequest $loan)
    {
        $validated = $request->validate([
            'sub_status_id' => ['nullable', 'exists:loan_sub_statuses,id'],
        ]);

        $loan->update([
            'sub_status_id' => $validated['sub_status_id'],
        ]);

        return redirect()->back()->with('success', 'Loan sub-status updated successfully.');
    }

    /**
     * Store a new custom sub-status (admin only, managed in Settings).
     */
    public function storeSubStatus(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:loan_sub_statuses,name'],
            'color' => ['required', 'string', 'max:30'],
        ]);

        LoanSubStatus::create([
            'name' => $validated['name'],
            'color' => $validated['color'],
        ]);

        return redirect()->back()->with('success', 'Custom loan sub-status created successfully.');
    }

    /**
     * Display the list of custom loan sub-statuses for management on a separate page.
     */
    public function subStatusesIndex(Request $request)
    {
        $query = LoanSubStatus::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 30, 50])) {
            $perPage = 10;
        }

        $subStatuses = $query->orderBy('name')->paginate($perPage);

        return view('loans.sub_statuses', compact('subStatuses'));
    }

    /**
     * Update a custom sub-status (admin only).
     */
    public function updateSubStatusDefinition(Request $request, LoanSubStatus $subStatus)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:loan_sub_statuses,name,' . $subStatus->id],
            'color' => ['required', 'string', 'max:30'],
        ]);

        $subStatus->update([
            'name' => $validated['name'],
            'color' => $validated['color'],
        ]);

        return redirect()->back()->with('success', 'Custom loan sub-status updated successfully.');
    }

    /**
     * Delete a custom sub-status (admin only, managed in Settings).
     */
    public function destroySubStatus(LoanSubStatus $subStatus)
    {
        $subStatus->delete();
        return redirect()->back()->with('success', 'Custom loan sub-status deleted.');
    }

    /**
     * Mark a loan request as defaulted (transitions core status from active to defaulted).
     */
    public function markAsDefaulted(LoanRequest $loan)
    {
        if ($loan->status !== 'active') {
            return redirect()->back()->with('error', 'Only active loans can be marked as defaulted.');
        }

        $loan->update([
            'status' => 'defaulted',
        ]);

        return redirect()->back()->with('success', 'Loan status marked as Defaulted.');
    }

    /**
     * Mark a defaulted loan request back as active.
     */
    public function markAsActive(LoanRequest $loan)
    {
        if ($loan->status !== 'defaulted') {
            return redirect()->back()->with('error', 'Only defaulted loans can be marked as active.');
        }

        $loan->update([
            'status' => 'active',
        ]);

        return redirect()->back()->with('success', 'Loan status restored to Active.');
    }

    /**
     * Submit a loan repayment request (member only).
     */
    public function requestRepayment(Request $request, LoanRequest $loan)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $member = Member::where('email', $user->email)->first();
        if (!$member) {
            abort(403, 'Unauthorized action. User profile is not linked to a member record.');
        }

        // Verify the loan belongs to the member and is active/defaulted
        if ($loan->member_id !== $member->id) {
            abort(403, 'Unauthorized action. You do not own this loan.');
        }

        if (!in_array($loan->status, ['active', 'defaulted'])) {
            return redirect()->back()->with('error', 'Can only submit repayments on active or defaulted loans.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:50'],
            'screenshot' => ['required', 'image', 'max:2048'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $remaining = $loan->remaining_balance;
        if ($validated['amount'] > $remaining + 0.01) {
            return redirect()->back()->with('error', 'Repayment amount exceeds the outstanding loan balance.');
        }

        $path = $request->file('screenshot')->store('repayment_proofs', 'public');

        LoanRepaymentRequest::create([
            'loan_request_id' => $loan->id,
            'member_id' => $member->id,
            'organization_id' => $member->organization_id,
            'amount' => $validated['amount'],
            'status' => 'pending',
            'screenshot_path' => $path,
            'payment_date' => $validated['payment_date'],
            'payment_method' => $validated['payment_method'],
            'reference_number' => $validated['reference_number'],
            'notes' => $validated['notes'],
            'submitted_at' => now(),
        ]);

        return redirect()
            ->route('member.loans.repayment-requests')
            ->with('success', 'Repayment request submitted successfully and is pending review.');
    }

    /**
     * Display member's own loan repayment requests.
     */
    public function myRepaymentRequests(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $member = Member::where('email', $user->email)->first();
        if (!$member) {
            abort(403, 'Unauthorized action. User profile is not linked to a member record.');
        }

        $query = LoanRepaymentRequest::where('member_id', $member->id)->with('loanRequest');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 30, 50])) {
            $perPage = 10;
        }

        $requests = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return view('loans.member_repayment_requests', compact('member', 'requests'));
    }

    /**
     * Display all loan repayment requests for admin approval/rejection.
     */
    public function adminRepaymentRequests(Request $request)
    {
        $query = LoanRepaymentRequest::with(['member', 'loanRequest']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('member', function ($mq) use ($search) {
                    $mq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('member_code', 'like', "%{$search}%");
                })->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 30, 50])) {
            $perPage = 10;
        }

        $requests = $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('submitted_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        $pendingCount = LoanRepaymentRequest::where('status', 'pending')->count();
        $allMembers = Member::orderBy('first_name')->orderBy('last_name')->get();

        return view('loans.repayment_requests', compact('requests', 'pendingCount', 'allMembers'));
    }

    /**
     * Approve a pending loan repayment request (admin only).
     */
    public function approveRepayment(Request $request, LoanRepaymentRequest $repaymentRequest)
    {
        if ($repaymentRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'This request is not pending.');
        }

        $loan = $repaymentRequest->loanRequest;
        $remaining = $loan->remaining_balance;
        if ($repaymentRequest->amount > $remaining + 0.01) {
            return redirect()->back()->with('error', 'Approved amount exceeds the outstanding loan balance.');
        }

        $repaymentRequest->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => $request->input('review_note'),
        ]);

        // Create actual financial ledger entry on approval
        LoanRepayment::create([
            'loan_request_id' => $repaymentRequest->loan_request_id,
            'amount' => $repaymentRequest->amount,
            'payment_date' => $repaymentRequest->payment_date,
            'payment_method' => $repaymentRequest->payment_method,
            'reference_number' => $repaymentRequest->reference_number,
            'notes' => 'Approved request. ' . $repaymentRequest->notes,
        ]);

        // If loan fully paid off, mark as completed
        if ($loan->fresh()->remaining_balance <= 0.01) {
            $loan->update(['status' => 'completed']);
        }

        return redirect()
            ->route('loans.repayment-requests')
            ->with('success', 'Loan repayment request approved successfully.');
    }

    /**
     * Reject a pending loan repayment request (admin only).
     */
    public function rejectRepayment(Request $request, LoanRepaymentRequest $repaymentRequest)
    {
        if ($repaymentRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'This request is not pending.');
        }

        $request->validate([
            'review_note' => ['required', 'string', 'max:1000'],
        ]);

        $repaymentRequest->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => $request->input('review_note'),
        ]);

        return redirect()
            ->route('loans.repayment-requests')
            ->with('success', 'Loan repayment request rejected.');
    }
}


