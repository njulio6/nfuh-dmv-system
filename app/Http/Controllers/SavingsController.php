<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\SavingsTransaction;
use App\Models\SavingsDepositRequest;
use App\Models\SavingsWithdrawalRequest;
use App\Models\Setting;
use App\Support\MemberResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SavingsController extends Controller
{
    /**
     * Display the admin savings control panel.
     */
    public function index(Request $request)
    {
        $query = Member::where('participates_in_savings', true);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('member_code', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('state_code')) {
            $query->where('state_code', $request->state_code);
        }

        if ($request->filled('member_id')) {
            $query->where('members.id', $request->member_id);
        }

        // Calculate dynamic savings balance in SQL to support having clause
        $query->select('members.*')
            ->selectSub(function ($q) {
                $q->selectRaw("COALESCE(SUM(CASE WHEN type IN ('deposit', 'adjustment') AND status = 'approved' THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN type = 'withdrawal' AND status = 'approved' THEN amount ELSE 0 END), 0)")
                  ->from('savings_transactions')
                  ->whereColumn('savings_transactions.member_id', 'members.id');
            }, 'db_savings_balance');

        if ($request->filled('eligibility')) {
            $minSavings = \App\Models\Setting::first()?->min_savings_for_loan ?? 500.00;
            if ($request->eligibility === 'eligible') {
                $query->having('db_savings_balance', '>=', $minSavings);
            } elseif ($request->eligibility === 'under_limit') {
                $query->having('db_savings_balance', '<', $minSavings);
            }
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 30, 50])) {
            $perPage = 10;
        }

        $members = $query->orderBy('first_name')->paginate($perPage);

        // Fetch all members for the transaction entry dropdown
        $allMembers = Member::orderBy('first_name')->get();

        // Fetch pending deposit requests for review
        $pendingRequests = SavingsDepositRequest::with('member')
            ->where('status', 'pending')
            ->orderBy('submitted_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Calculate Stats
        $totalSavingsPool = SavingsTransaction::where('status', 'approved')
            ->whereIn('type', ['deposit', 'adjustment'])
            ->sum('amount')
            - SavingsTransaction::where('status', 'approved')
            ->where('type', 'withdrawal')
            ->sum('amount');

        $activeDepositorsCount = Member::where('participates_in_savings', true)->count();

        $totalWithdrawals = SavingsTransaction::where('status', 'approved')
            ->where('type', 'withdrawal')
            ->sum('amount');

        $pendingRequestsCount = SavingsDepositRequest::where('status', 'pending')->count();

        return view('savings.index', compact(
            'members',
            'allMembers',
            'pendingRequests',
            'totalSavingsPool',
            'activeDepositorsCount',
            'totalWithdrawals',
            'pendingRequestsCount'
        ));
    }

    /**
     * Store a new savings transaction (admin only).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'in:deposit,withdrawal,adjustment'],
            'transaction_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $member = Member::findOrFail($validated['member_id']);

        // Auto-enroll member in savings if not already done
        if (!$member->participates_in_savings) {
            $member->update(['participates_in_savings' => true]);
        }

        SavingsTransaction::create([
            'member_id' => $member->id,
            'organization_id' => $member->organization_id,
            'amount' => $validated['amount'],
            'type' => $validated['type'],
            'status' => 'approved',
            'transaction_date' => $validated['transaction_date'],
            'notes' => $validated['notes'],
        ]);

        return redirect()
            ->route('savings.index')
            ->with('success', 'Savings transaction recorded successfully.');
    }

    /**
     * Approve a pending deposit request (admin only).
     */
    public function approve(Request $request, SavingsDepositRequest $depositRequest)
    {
        if ($depositRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'This request is not pending.');
        }

        $depositRequest->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => $request->input('review_note'),
        ]);

        // Create actual financial ledger entry on approval
        SavingsTransaction::create([
            'member_id' => $depositRequest->member_id,
            'organization_id' => $depositRequest->organization_id,
            'amount' => $depositRequest->amount,
            'type' => 'deposit',
            'status' => 'approved',
            'transaction_date' => $depositRequest->submitted_at->toDateString(),
            'notes' => 'Approved deposit request. ' . $depositRequest->notes,
        ]);

        return redirect()
            ->route('savings.requests')
            ->with('success', 'Savings deposit request approved successfully.');
    }

    /**
     * Reject a pending deposit request (admin only).
     */
    public function reject(Request $request, SavingsDepositRequest $depositRequest)
    {
        if ($depositRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'This request is not pending.');
        }

        $request->validate([
            'review_note' => ['required', 'string', 'max:1000'],
        ]);

        $depositRequest->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => $request->input('review_note'),
        ]);

        return redirect()
            ->route('savings.requests')
            ->with('success', 'Savings deposit request rejected.');
    }

    /**
     * Display the logged-in member's savings statements.
     */
    public function mySavings()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $member = MemberResolver::fromUser($user);
        if (!$member) {
            abort(403, 'Unauthorized action. User profile is not linked to a member record.');
        }

        // Approved active ledger transactions and pending withdrawal payouts
        $transactions = SavingsTransaction::where('member_id', $member->id)
            ->whereIn('status', ['approved', 'pending'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        // Pending and Rejected tracking requests
        $requests = SavingsDepositRequest::where('member_id', $member->id)
            ->whereIn('status', ['pending', 'rejected'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Member's recent withdrawal requests
        $withdrawalRequests = SavingsWithdrawalRequest::where('member_id', $member->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $appSettings = Setting::first();

        return view('savings.member', compact('member', 'transactions', 'requests', 'withdrawalRequests', 'appSettings'));
    }

    /**
     * Submit a savings deposit request (member only).
     */
    public function requestDeposit(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $member = MemberResolver::fromUser($user);
        if (!$member) {
            abort(403, 'Unauthorized action. User profile is not linked to a member record.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'screenshot' => ['required', 'image', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $path = $request->file('screenshot')->store('savings_proofs', 'public');

        SavingsDepositRequest::create([
            'member_id' => $member->id,
            'organization_id' => $member->organization_id,
            'amount' => $validated['amount'],
            'status' => 'pending',
            'screenshot_path' => $path,
            'notes' => $validated['notes'],
            'submitted_at' => now(),
        ]);

        return redirect()
            ->route('member.savings.requests')
            ->with('success', 'Savings deposit request submitted successfully and is pending review.');
    }

    /**
     * Display a paginated ledger of all savings transactions (admin only).
     */
    public function transactions(Request $request)
    {
        $query = SavingsTransaction::with('member');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('member', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 30, 50])) {
            $perPage = 10;
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        // Fetch all members for the searchable filter dropdown
        $allMembers = Member::orderBy('first_name')->get();

        return view('savings.transactions', compact('transactions', 'allMembers'));
    }

    /**
     * Display the logged-in member's savings deposit requests (dedicated page).
     */
    public function mySavingsRequests(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $member = MemberResolver::fromUser($user);
        if (!$member) {
            abort(403, 'Unauthorized action. User profile is not linked to a member record.');
        }

        $query = SavingsDepositRequest::where('member_id', $member->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhere('submitted_at', 'like', "%{$search}%");
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

        return view('savings.member_requests', compact('member', 'requests'));
    }

    /**
     * Display all savings deposit requests for admin approval/rejection.
     */
    public function adminRequests(Request $request)
    {
        $query = SavingsDepositRequest::with('member');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('member', function ($mq) use ($search) {
                    $mq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('member_code', 'like', "%{$search}%");
                })->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%");
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

        // Fetch counts for badges
        $pendingCount = SavingsDepositRequest::where('status', 'pending')->count();

        // Fetch all members for the searchable member filter dropdown
        $allMembers = Member::orderBy('first_name')->orderBy('last_name')->get();

        return view('savings.admin_requests', compact('requests', 'pendingCount', 'allMembers'));
    }

    /**
     * Submit a savings withdrawal request (member only).
     */
    public function requestWithdrawal(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $member = MemberResolver::fromUser($user);
        if (!$member) {
            abort(403, 'Unauthorized action. User profile is not linked to a member record.');
        }

        $availableBalance = (float) $member->available_savings_balance;

        $rules = [
            'gross_amount' => ['required', 'numeric', 'min:1', 'max:' . $availableBalance],
            'payout_method' => ['required', 'string', 'in:zelle,bank_transfer,check'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        $payoutMethod = $request->input('payout_method');
        $hasStructuredFields = false;

        if ($payoutMethod === 'zelle') {
            if ($request->hasAny(['zelle_name', 'zelle_contact']) || !$request->has('payout_details')) {
                $hasStructuredFields = true;
                $rules['zelle_name'] = ['required', 'string', 'min:2', 'max:255'];
                $rules['zelle_contact'] = ['required', 'string', 'min:4', 'max:255'];
            } else {
                $rules['payout_details'] = ['required', 'string', 'min:4', 'max:1000'];
            }
        } elseif ($payoutMethod === 'bank_transfer') {
            if ($request->hasAny(['bank_account_holder', 'bank_name', 'bank_routing_number', 'bank_account_number']) || !$request->has('payout_details')) {
                $hasStructuredFields = true;
                $rules['bank_account_holder'] = ['required', 'string', 'min:2', 'max:255'];
                $rules['bank_name'] = ['required', 'string', 'min:2', 'max:255'];
                $rules['bank_routing_number'] = ['required', 'digits:9'];
                $rules['bank_account_number'] = ['required', 'string', 'min:4', 'max:30'];
                $rules['bank_account_type'] = ['required', 'string', 'in:checking,savings'];
            } else {
                $rules['payout_details'] = ['required', 'string', 'min:5', 'max:1000'];
            }
        } elseif ($payoutMethod === 'check') {
            if ($request->hasAny(['check_payee_name', 'check_delivery_address']) || !$request->has('payout_details')) {
                $hasStructuredFields = true;
                $rules['check_payee_name'] = ['required', 'string', 'min:2', 'max:255'];
                $rules['check_delivery_address'] = ['required', 'string', 'min:5', 'max:1000'];
            } else {
                $rules['payout_details'] = ['required', 'string', 'min:5', 'max:1000'];
            }
        }

        $validated = $request->validate($rules, [
            'gross_amount.max' => 'The withdrawal amount cannot exceed your available savings balance of $' . number_format($availableBalance, 2) . '.',
            'gross_amount.min' => 'The minimum withdrawal amount is $1.00.',
            'zelle_name.required' => 'Recipient full name is required for Zelle payouts.',
            'zelle_contact.required' => 'Registered Zelle phone number or email address is required.',
            'bank_account_holder.required' => 'Account holder full name is required for bank transfers.',
            'bank_name.required' => 'Bank name is required.',
            'bank_routing_number.required' => 'A valid 9-digit routing number is required.',
            'bank_routing_number.digits' => 'Routing number must be exactly 9 digits.',
            'bank_account_number.required' => 'Account number is required.',
            'bank_account_type.required' => 'Account type (Checking or Savings) is required.',
            'check_payee_name.required' => 'Payee name is required for check issuance.',
            'check_delivery_address.required' => 'Delivery mailing address or pickup instructions are required.',
            'payout_details.required' => 'Payout destination details are required and cannot be empty.',
        ]);

        $finalPayoutDetails = $validated['payout_details'] ?? '';
        if ($hasStructuredFields) {
            if ($payoutMethod === 'zelle') {
                $finalPayoutDetails = "Recipient: {$validated['zelle_name']} | Contact: {$validated['zelle_contact']}";
            } elseif ($payoutMethod === 'bank_transfer') {
                $typeStr = ucfirst($validated['bank_account_type'] ?? 'Checking');
                $finalPayoutDetails = "Holder: {$validated['bank_account_holder']} | Bank: {$validated['bank_name']} | Routing: {$validated['bank_routing_number']} | Account: {$validated['bank_account_number']} | Type: {$typeStr}";
            } elseif ($payoutMethod === 'check') {
                $finalPayoutDetails = "Payee: {$validated['check_payee_name']} | Delivery: {$validated['check_delivery_address']}";
            }
        }

        $feePercent = (float) (Setting::first()?->savings_withdrawal_fee_percent ?? 2.00);
        $grossAmount = (float) $validated['gross_amount'];
        $feeAmount = round($grossAmount * ($feePercent / 100), 2);
        $netAmount = round($grossAmount - $feeAmount, 2);

        SavingsWithdrawalRequest::create([
            'member_id' => $member->id,
            'organization_id' => $member->organization_id,
            'gross_amount' => $grossAmount,
            'fee_rate' => $feePercent,
            'fee_amount' => $feeAmount,
            'net_amount' => $netAmount,
            'payout_method' => $validated['payout_method'],
            'payout_details' => $finalPayoutDetails,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('member.savings.withdrawals')
            ->with('success', 'Savings withdrawal request submitted successfully and is pending review.');
    }

    /**
     * Display the logged-in member's savings withdrawal requests.
     */
    public function myWithdrawalRequests(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $member = MemberResolver::fromUser($user);
        if (!$member) {
            abort(403, 'Unauthorized action. User profile is not linked to a member record.');
        }

        $query = SavingsWithdrawalRequest::where('member_id', $member->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payout_details', 'like', "%{$search}%")
                  ->orWhere('gross_amount', 'like', "%{$search}%")
                  ->orWhere('payout_method', 'like', "%{$search}%");
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

        $appSettings = Setting::first();

        return view('savings.member_withdrawal_requests', compact('member', 'requests', 'appSettings'));
    }

    /**
     * Display all savings withdrawal requests for admin approval/rejection.
     */
    public function adminWithdrawalRequests(Request $request)
    {
        $query = SavingsWithdrawalRequest::with('member');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('member', function ($mq) use ($search) {
                    $mq->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('member_code', 'like', "%{$search}%");
                })->orWhere('payout_details', 'like', "%{$search}%")
                  ->orWhere('gross_amount', 'like', "%{$search}%");
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

        $requests = $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'approved' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        $pendingCount = SavingsWithdrawalRequest::where('status', 'pending')->count();
        $pendingDisbursementCount = SavingsWithdrawalRequest::where('status', 'approved')->count();
        $allMembers = Member::orderBy('first_name')->orderBy('last_name')->get();

        return view('savings.admin_withdrawal_requests', compact('requests', 'pendingCount', 'pendingDisbursementCount', 'allMembers'));
    }

    /**
     * Approve a pending withdrawal request (admin only).
     */
    public function approveWithdrawal(Request $request, SavingsWithdrawalRequest $withdrawalRequest)
    {
        if ($withdrawalRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'This withdrawal request is not pending.');
        }

        $member = $withdrawalRequest->member;
        if ($member->available_savings_balance < (float) $withdrawalRequest->gross_amount) {
            return redirect()->back()->with('error', 'Member does not have sufficient available savings balance ($' . number_format($member->available_savings_balance, 2) . ') to approve this withdrawal.');
        }

        DB::transaction(function () use ($withdrawalRequest, $member, $request) {
            // Create pending ledger transaction to distinguish pending payouts from completed withdrawals
            $transaction = SavingsTransaction::create([
                'member_id' => $member->id,
                'organization_id' => $member->organization_id,
                'amount' => $withdrawalRequest->gross_amount,
                'type' => 'withdrawal',
                'status' => 'pending', // Pending payout until disbursed!
                'transaction_date' => now()->toDateString(),
                'notes' => "Member withdrawal payout ($" . number_format($withdrawalRequest->net_amount, 2) . ") + " . number_format($withdrawalRequest->fee_rate, 2) . "% Admin Fee ($" . number_format($withdrawalRequest->fee_amount, 2) . ") via " . ucfirst(str_replace('_', ' ', $withdrawalRequest->payout_method)) . " (Pending Disbursement)",
            ]);

            $withdrawalRequest->update([
                'status' => 'approved', // Approved – Pending Disbursement
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'admin_notes' => $request->input('admin_notes'),
                'savings_transaction_id' => $transaction->id,
            ]);
        });

        return redirect()->back()->with('success', 'Savings withdrawal request approved and is now pending disbursement.');
    }

    /**
     * Confirm disbursement for an approved savings withdrawal (Treasurer / Admin only - M1-003 & M1-004).
     */
    public function disburseWithdrawal(Request $request, SavingsWithdrawalRequest $withdrawalRequest)
    {
        if ($withdrawalRequest->status !== 'approved') {
            return redirect()->back()->with('error', 'Withdrawal request must be approved before disbursement.');
        }

        $rules = [
            'disbursement_method' => ['required', 'string', 'max:50'],
            'disbursement_destination' => ['required', 'string', 'min:3', 'max:1000'],
            'disbursement_date' => ['required', 'date', 'before_or_equal:today'],
            'disbursement_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],
            'disbursement_reference' => ['nullable', 'string', 'max:255'],
            'disbursement_details' => ['nullable', 'string', 'max:2000'],
        ];

        $messages = [
            'disbursement_method.required' => 'A payment method is mandatory to confirm disbursement.',
            'disbursement_destination.required' => 'Recipient / payout destination details are mandatory.',
            'disbursement_date.required' => 'Disbursement date is mandatory.',
            'disbursement_date.before_or_equal' => 'Disbursement date cannot be a future date.',
            'disbursement_proof.required' => 'Supporting payment receipt or transfer screenshot is mandatory.',
            'disbursement_proof.mimes' => 'Receipt proof document must be a JPG, PNG, WEBP, or PDF file.',
            'disbursement_proof.max' => 'Receipt proof document cannot exceed 5MB.',
        ];

        $validated = $request->validate($rules, $messages);

        $proofPath = $request->file('disbursement_proof')->store('savings_disbursements', 'public');

        $disbursedDate = \Carbon\Carbon::parse($validated['disbursement_date']);
        $disbursedAt = $disbursedDate->isToday() 
            ? now() 
            : $disbursedDate->copy()->setTime(now()->hour, now()->minute, now()->second);

        $ref = !empty($validated['disbursement_reference']) ? trim($validated['disbursement_reference']) : null;

        DB::transaction(function () use ($withdrawalRequest, $validated, $proofPath, $disbursedDate, $disbursedAt, $ref) {
            $refNote = $ref ? " (Ref: {$ref})" : "";

            $notes = "Member withdrawal payout ($" . number_format($withdrawalRequest->net_amount, 2) . ") + " . number_format($withdrawalRequest->fee_rate, 2) . "% Admin Fee ($" . number_format($withdrawalRequest->fee_amount, 2) . ") via " . ucfirst(str_replace('_', ' ', $validated['disbursement_method'])) . $refNote;

            // Update or create linked SavingsTransaction and mark as approved (completed withdrawal)
            if ($withdrawalRequest->savings_transaction_id) {
                $transaction = SavingsTransaction::find($withdrawalRequest->savings_transaction_id);
                if ($transaction) {
                    $transaction->update([
                        'status' => 'approved',
                        'screenshot_path' => $proofPath,
                        'transaction_date' => $validated['disbursement_date'],
                        'notes' => $notes,
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => now(),
                    ]);
                }
            } else {
                $transaction = SavingsTransaction::create([
                    'member_id' => $withdrawalRequest->member_id,
                    'organization_id' => $withdrawalRequest->organization_id,
                    'amount' => $withdrawalRequest->gross_amount,
                    'type' => 'withdrawal',
                    'status' => 'approved',
                    'screenshot_path' => $proofPath,
                    'transaction_date' => $validated['disbursement_date'],
                    'notes' => $notes,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                ]);
                $withdrawalRequest->savings_transaction_id = $transaction->id;
            }

            $withdrawalRequest->update([
                'status' => 'disbursed',
                'disbursed_at' => $disbursedAt,
                'disbursed_date' => $validated['disbursement_date'],
                'disbursed_by_user_id' => Auth::id(),
                'disbursement_method' => $validated['disbursement_method'],
                'disbursement_destination' => $validated['disbursement_destination'],
                'disbursement_reference' => $ref,
                'no_transaction_reference' => empty($ref),
                'disbursement_reference_reason' => null,
                'disbursement_proof' => $proofPath,
                'disbursement_details' => $validated['disbursement_details'] ?? null,
            ]);
        });

        return redirect()
            ->route('savings.withdrawal-requests')
            ->with('success', 'Savings withdrawal marked as Disbursed / Completed with verified payment receipt.');
    }

    /**
     * Reject a pending withdrawal request (admin only).
     */
    public function rejectWithdrawal(Request $request, SavingsWithdrawalRequest $withdrawalRequest)
    {
        if (!in_array($withdrawalRequest->status, ['pending', 'approved'])) {
            return redirect()->back()->with('error', 'Cannot reject request at this stage.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        DB::transaction(function () use ($withdrawalRequest, $validated) {
            // Delete or reject pending ledger transaction if one was created
            if ($withdrawalRequest->savings_transaction_id) {
                SavingsTransaction::where('id', $withdrawalRequest->savings_transaction_id)->delete();
            }

            $withdrawalRequest->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'admin_notes' => $validated['rejection_reason'],
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
        });

        return redirect()->back()->with('success', 'Savings withdrawal request rejected.');
    }
}
