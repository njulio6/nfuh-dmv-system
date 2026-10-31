<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\LoanRequest;
use App\Models\LoanRepayment;
use App\Models\LoanRepaymentRequest;
use App\Models\SavingsTransaction;
use App\Models\SavingsDepositRequest;
use App\Models\SavingsWithdrawalRequest;
use App\Models\NjangiContribution;
use App\Models\NjangiPaymentSubmission;
use App\Models\User;
use App\Models\MemberRank;
use App\Models\LoanSubStatus;
use App\Models\MeetingAttendance;
use App\Support\MemberResolver;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    /**
     * Helper to verify if the authenticated user has administrative or officer rights
     */
    protected function resolveOfficerStatus($user): array
    {
        $member = MemberResolver::fromUser($user);
        $adminRoles = ['admin', 'super-admin', 'Treasurer', 'Financial Secretary', 'Loan Committee', 'General Secretary'];
        
        $isOfficer = $user->hasAnyRole($adminRoles);
        if (!$isOfficer && $member) {
            $memberAdminRoles = ['Secretary', 'Treasurer', 'Financial Secretary', 'Loan Officer', 'Lead Nformi', 'Loan Committee', 'General Secretary'];
            $isOfficer = $member->roles()->whereIn('name', $memberAdminRoles)->exists();
        }

        return [$isOfficer, $member];
    }

    /**
     * Export loans to CSV format with unpaginated filter enforcement
     */
    public function exportLoansCsv(Request $request)
    {
        $user = auth()->user();
        [$isOfficer, $member] = $this->resolveOfficerStatus($user);

        $fileName = 'loans_export_' . date('Y-m-d_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $query = LoanRequest::with(['member', 'repayments']);

        // Data isolation: Regular members can only export their own loans
        if (!$isOfficer) {
            if (!$member) {
                abort(403, 'Unauthorized.');
            }
            $query->where('member_id', $member->id);
        } elseif ($request->filled('member_id')) {
            $query->where('member_id', $request->get('member_id'));
        }

        // Filter by Status
        if ($request->filled('status')) {
            $status = $request->get('status');
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Filter by Search (Borrower name, code, loan ID, purpose, notes)
        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->orWhere('id', $search);
                }
                $q->where('purpose', 'like', "%{$search}%")
                  ->orWhere('admin_notes', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($mq) use ($search) {
                      $mq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('member_code', 'like', "%{$search}%")
                         ->orWhereHas('user', function ($uq) use ($search) {
                             $uq->where('email', 'like', "%{$search}%");
                         });
                  });
            });
        }

        // Unpaginated fetch: exports all matching records
        $loans = $query->orderBy('id', 'desc')->get();

        $callback = function () use ($loans) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Loan ID', 
                'Member Code', 
                'Borrower Name', 
                'Amount ($)', 
                'Duration (Months)', 
                'Repayment Frequency', 
                'Status', 
                'Outstanding Balance ($)', 
                'Repayment Due Date', 
                'Purpose',
                'Disbursed At',
                'Payout Method',
                'Payout Details',
                'Disbursement Details',
                'Date Created'
            ]);

            foreach ($loans as $loan) {
                fputcsv($file, [
                    $loan->id,
                    $loan->member ? $loan->member->member_code : '-',
                    $loan->member ? $loan->member->name : 'N/A',
                    number_format($loan->amount, 2, '.', ''),
                    $loan->duration_months,
                    $loan->repayment_frequency === 'end_of_term' ? 'End of Term' : ucfirst($loan->repayment_frequency ?? 'monthly'),
                    ucfirst(str_replace('_', ' ', $loan->status)),
                    number_format($loan->remaining_balance, 2, '.', ''),
                    $loan->repayment_due_date ? $loan->repayment_due_date->format('Y-m-d') : '-',
                    $loan->purpose ?: '-',
                    $loan->disbursed_at ? $loan->disbursed_at->format('Y-m-d H:i:s') : '-',
                    $loan->payout_method ? ucfirst(str_replace('_', ' ', $loan->payout_method)) : '-',
                    $loan->payout_details ?: '-',
                    $loan->disbursement_details ?: '-',
                    $loan->created_at ? $loan->created_at->format('Y-m-d H:i:s') : '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export loan repayments to CSV format with unpaginated filter enforcement
     */
    public function exportRepaymentsCsv(Request $request)
    {
        $user = auth()->user();
        [$isOfficer, $member] = $this->resolveOfficerStatus($user);

        $fileName = 'loan_repayments_' . date('Y-m-d_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $query = LoanRepayment::with(['loan.member.user']);

        // Data isolation: Regular members can only export repayments on their own loans
        if (!$isOfficer) {
            if (!$member) {
                abort(403, 'Unauthorized.');
            }
            $query->whereHas('loan', fn ($lq) => $lq->where('member_id', $member->id));
        } elseif ($request->filled('member_id')) {
            $query->whereHas('loan', fn ($lq) => $lq->where('member_id', $request->get('member_id')));
        }

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('loan.member', function ($mq) use ($search) {
                      $mq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('member_code', 'like', "%{$search}%")
                         ->orWhereHas('user', function ($uq) use ($search) {
                             $uq->where('email', 'like', "%{$search}%");
                         });
                  });
            });
        }

        $repayments = $query->orderBy('payment_date', 'desc')->orderBy('id', 'desc')->get();

        $callback = function () use ($repayments) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Repayment ID',
                'Loan ID',
                'Member Code',
                'Borrower Name',
                'Amount Paid ($)',
                'Remaining Balance ($)',
                'Payment Method',
                'Reference #',
                'Repayment Date',
                'Notes'
            ]);

            foreach ($repayments as $r) {
                $loan = $r->loan;
                fputcsv($file, [
                    $r->id,
                    $r->loan_request_id,
                    $loan && $loan->member ? $loan->member->member_code : '-',
                    $loan && $loan->member ? $loan->member->name : 'N/A',
                    number_format($r->amount, 2, '.', ''),
                    $loan ? number_format($loan->remaining_balance ?? 0, 2, '.', '') : '0.00',
                    ucfirst(str_replace('_', ' ', $r->payment_method ?? 'cash')),
                    $r->reference_number ?: '-',
                    $r->payment_date ? $r->payment_date->format('Y-m-d') : '-',
                    $r->notes ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export savings transactions or savings balances to CSV format with unpaginated filter enforcement
     */
    public function exportSavingsCsv(Request $request)
    {
        $user = auth()->user();
        [$isOfficer, $member] = $this->resolveOfficerStatus($user);

        // If requesting balances directory roster view (from /savings)
        if ($request->get('view') === 'balances') {
            return $this->exportSavingsBalancesCsv($request, $isOfficer, $member);
        }

        $fileName = 'savings_export_' . date('Y-m-d_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $query = SavingsTransaction::with(['member', 'reviewer']);

        // Data isolation: Regular members can only export their own transactions
        if (!$isOfficer) {
            if (!$member) {
                abort(403, 'Unauthorized.');
            }
            $query->where('member_id', $member->id);
        } elseif ($request->filled('member_id')) {
            $query->where('member_id', $request->get('member_id'));
        }

        // Filter by Transaction Type (deposit, withdrawal, adjustment)
        if ($request->filled('type') && $request->get('type') !== 'all') {
            $query->where('type', $request->get('type'));
        }

        // Filter by Status (approved, pending, rejected)
        if ($request->filled('status') && $request->get('status') !== 'all') {
            $query->where('status', $request->get('status'));
        } elseif (!$request->has('include_all_status') && !$request->filled('status')) {
            // Default to approved if viewing normal transactions ledger
            if (!$request->filled('type') || $request->get('type') === 'all') {
                $query->where('status', 'approved');
            }
        }

        // Filter by Search (Member name, Member code, or Notes)
        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($mq) use ($search) {
                      $mq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('member_code', 'like', "%{$search}%")
                         ->orWhereHas('user', function ($uq) use ($search) {
                             $uq->where('email', 'like', "%{$search}%");
                         });
                  });
            });
        }

        // Unpaginated fetch: exports all matching records
        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Transaction ID', 
                'Member Code', 
                'Member Name', 
                'Amount ($)', 
                'Previous Balance ($)',
                'New Balance ($)',
                'Type', 
                'Status',
                'Transaction Date', 
                'Officer / Reviewed By',
                'Notes',
                'Date Recorded'
            ]);

            foreach ($transactions as $t) {
                $officer = $t->reviewer ? $t->reviewer->name : ($t->officer_name ?: 'System Officer');

                fputcsv($file, [
                    $t->id,
                    $t->member ? $t->member->member_code : '-',
                    $t->member ? $t->member->name : 'N/A',
                    number_format($t->amount, 2, '.', ''),
                    $t->previous_balance !== null ? number_format($t->previous_balance, 2, '.', '') : '0.00',
                    $t->new_balance !== null ? number_format($t->new_balance, 2, '.', '') : '0.00',
                    ucfirst($t->type),
                    ucfirst($t->status ?? 'approved'),
                    $t->transaction_date ? $t->transaction_date->format('Y-m-d') : '-',
                    $officer,
                    $t->notes ?? '',
                    $t->created_at ? $t->created_at->format('Y-m-d H:i:s') : '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export savings balances directory to CSV format
     */
    protected function exportSavingsBalancesCsv(Request $request, bool $isOfficer, $member)
    {
        $fileName = 'savings_balances_' . date('Y-m-d_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $query = Member::with('user')->where('participates_in_savings', true);

        if (!$isOfficer) {
            if (!$member) {
                abort(403, 'Unauthorized.');
            }
            $query->where('id', $member->id);
        }

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('member_code', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('state_code')) {
            $query->where('state_code', $request->get('state_code'));
        }

        if ($request->filled('member_id')) {
            $query->where('members.id', $request->get('member_id'));
        }

        // Dynamic balance calculation in SQL matching SavingsController
        $query->select('members.*')
            ->selectSub(function ($q) {
                $q->selectRaw("COALESCE(SUM(CASE WHEN type IN ('deposit', 'adjustment') AND status = 'approved' THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN type = 'withdrawal' AND status = 'approved' THEN amount ELSE 0 END), 0)")
                  ->from('savings_transactions')
                  ->whereColumn('savings_transactions.member_id', 'members.id');
            }, 'db_savings_balance');

        $minSavings = \App\Models\Setting::first()?->min_savings_for_loan ?? 500.00;

        if ($request->filled('eligibility')) {
            if ($request->get('eligibility') === 'eligible') {
                $query->having('db_savings_balance', '>=', $minSavings);
            } elseif ($request->get('eligibility') === 'under_limit') {
                $query->having('db_savings_balance', '<', $minSavings);
            }
        }

        $members = $query->orderBy('first_name', 'asc')->orderBy('last_name', 'asc')->get();

        $callback = function () use ($members, $minSavings) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Member Code',
                'Name',
                'Email',
                'Phone',
                'Status',
                'Current Savings Balance ($)',
                'Eligibility Status',
                'State'
            ]);

            foreach ($members as $m) {
                $balance = (float) ($m->db_savings_balance ?? $m->savings_balance ?? 0);
                $isEligible = $balance >= $minSavings;
                $email = $m->user ? $m->user->email : '-';

                fputcsv($file, [
                    $m->member_code,
                    $m->name,
                    $email,
                    $m->phone ?: '-',
                    ucfirst($m->status),
                    number_format($balance, 2, '.', ''),
                    $isEligible ? 'Eligible' : 'Under Limit',
                    $m->state_code ?: '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export Njangi rotational contributions to CSV format with unpaginated filter enforcement
     */
    public function exportNjangiCsv(Request $request)
    {
        $user = auth()->user();
        [$isOfficer, $member] = $this->resolveOfficerStatus($user);

        $fileName = 'njangi_contributions_' . date('Y-m-d_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $query = NjangiContribution::with(['cycle', 'session', 'contributor', 'beneficiary']);

        // Data isolation: Regular members can only export contributions where they contributed or were beneficiary
        if (!$isOfficer) {
            if (!$member) {
                abort(403, 'Unauthorized.');
            }
            $query->where(function ($q) use ($member) {
                $q->where('contributor_member_id', $member->id)
                  ->orWhere('beneficiary_member_id', $member->id);
            });
        } elseif ($request->filled('member_id')) {
            $mid = $request->get('member_id');
            $query->where(function ($q) use ($mid) {
                $q->where('contributor_member_id', $mid)
                  ->orWhere('beneficiary_member_id', $mid);
            });
        }

        if ($request->filled('cycle_id')) {
            $query->where('njangi_cycle_id', $request->get('cycle_id'));
        }

        if ($request->filled('session_id')) {
            $query->where('njangi_session_id', $request->get('session_id'));
        }

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('contributor', function ($mq) use ($search) {
                      $mq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('member_code', 'like', "%{$search}%")
                         ->orWhereHas('user', function ($uq) use ($search) {
                             $uq->where('email', 'like', "%{$search}%");
                         });
                  });
            });
        }

        $contributions = $query->orderBy('payment_date', 'desc')->orderBy('id', 'desc')->get();

        $callback = function () use ($contributions) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Contribution ID',
                'Cycle Name',
                'Session',
                'Contributor Code',
                'Contributor Name',
                'Beneficiary Name',
                'Amount ($)',
                'Payment Method',
                'Reference Number',
                'Payment Date',
                'Notes',
                'Date Recorded'
            ]);

            foreach ($contributions as $c) {
                fputcsv($file, [
                    $c->id,
                    $c->cycle ? $c->cycle->name : 'N/A',
                    $c->session ? ($c->session->title ?? 'Session #' . $c->session->session_number) : 'N/A',
                    $c->contributor ? $c->contributor->member_code : '-',
                    $c->contributor ? $c->contributor->name : 'N/A',
                    $c->beneficiary ? $c->beneficiary->name : 'Rotational Pool',
                    number_format($c->amount, 2, '.', ''),
                    ucfirst(str_replace('_', ' ', $c->payment_method ?? 'cash')),
                    $c->reference_number ?: '-',
                    $c->payment_date ? $c->payment_date->format('Y-m-d') : '-',
                    $c->notes ?? '',
                    $c->created_at ? $c->created_at->format('Y-m-d H:i:s') : '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export meeting attendance records to CSV format with unpaginated filter enforcement
     */
    public function exportAttendanceCsv(Request $request)
    {
        $user = auth()->user();
        [$isOfficer, $member] = $this->resolveOfficerStatus($user);

        $fileName = 'attendance_export_' . date('Y-m-d_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $query = MeetingAttendance::with(['session', 'member', 'verifier']);

        // Data isolation: Regular members can only export their own roll-call history
        if (!$isOfficer) {
            if (!$member) {
                abort(403, 'Unauthorized.');
            }
            $query->where('member_id', $member->id);
        } elseif ($request->filled('member_id')) {
            $query->where('member_id', $request->get('member_id'));
        }

        if ($request->filled('session_id')) {
            $query->where('njangi_session_id', $request->get('session_id'));
        }

        $statusFilter = $request->get('status') ?: $request->get('attendance_filter');
        if (!empty($statusFilter) && $statusFilter !== 'all') {
            if ($statusFilter === 'qr_checkin') {
                $query->where('check_in_source', 'like', '%qr%');
            } elseif ($statusFilter === 'unverified') {
                $query->whereNull('verified_at');
            } else {
                $query->where('attendance_status', $statusFilter);
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->whereHas('member', function ($mq) use ($search) {
                $mq->where('first_name', 'like', "%{$search}%")
                   ->orWhere('last_name', 'like', "%{$search}%")
                   ->orWhere('member_code', 'like', "%{$search}%");
            });
        }

        $records = $query->orderBy('id', 'desc')->get();

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Attendance ID',
                'Session Title',
                'Meeting Date',
                'Member Code',
                'Member Name',
                'Attendance Status',
                'Check-In Source',
                'Check-In Timestamp',
                'Verified By',
                'Verified Timestamp',
                'Officer Notes'
            ]);

            foreach ($records as $r) {
                $mDate = '-';
                if ($r->session) {
                    $mDate = $r->session->meeting_date ? $r->session->meeting_date->format('Y-m-d') : ($r->session->session_date ? $r->session->session_date->format('Y-m-d') : '-');
                }

                fputcsv($file, [
                    $r->id,
                    $r->session ? ($r->session->title ?? 'Session #' . $r->session->session_number) : 'General Meeting',
                    $mDate,
                    $r->member ? $r->member->member_code : '-',
                    $r->member ? $r->member->name : 'N/A',
                    ucfirst($r->attendance_status ?? 'unrecorded'),
                    ucwords(str_replace('_', ' ', $r->check_in_source ?? 'manual_desk')),
                    $r->check_in_at ? $r->check_in_at->format('Y-m-d H:i:s') : '-',
                    $r->verifier ? $r->verifier->name : '-',
                    $r->verified_at ? $r->verified_at->format('Y-m-d H:i:s') : '-',
                    $r->officer_notes ?: '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export members directory to CSV format with unpaginated filter enforcement (Officer only)
     */
    public function exportMembersCsv(Request $request)
    {
        $user = auth()->user();
        [$isOfficer] = $this->resolveOfficerStatus($user);

        if (!$isOfficer) {
            abort(403, 'Unauthorized.');
        }

        $fileName = 'members_directory_' . date('Y-m-d_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $query = Member::with(['roles', 'rank', 'user']);

        if ($request->filled('status') && $request->get('status') !== 'all') {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('state_code')) {
            $query->where('state_code', $request->get('state_code'));
        }

        if ($request->filled('title_id')) {
            $query->where('rank_id', $request->get('title_id'));
        }

        $members = $query->orderBy('first_name', 'asc')->orderBy('last_name', 'asc')->get();

        $callback = function () use ($members) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Member Code',
                'Full Name',
                'Email',
                'Phone',
                'Status',
                'Role(s)',
                'Traditional Title',
                'Savings Balance ($)',
                'State Code',
                'Date Joined'
            ]);

            foreach ($members as $m) {
                $roles = $m->roles->pluck('name')->join(', ') ?: 'Member';
                $title = $m->rank ? $m->rank->name : ($m->traditional_title ?: '-');
                $email = $m->user ? $m->user->email : '-';

                fputcsv($file, [
                    $m->member_code,
                    $m->name,
                    $email,
                    $m->phone ?: '-',
                    ucfirst($m->status),
                    $roles,
                    $title,
                    number_format($m->savings_balance ?? 0, 2, '.', ''),
                    $m->state_code ?: '-',
                    $m->created_at ? $m->created_at->format('Y-m-d') : '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export traditional titles to CSV format with unpaginated filter enforcement
     */
    public function exportTitlesCsv(Request $request)
    {
        $user = auth()->user();
        [$isOfficer] = $this->resolveOfficerStatus($user);

        if (!$isOfficer) {
            abort(403, 'Unauthorized.');
        }

        $fileName = 'traditional_titles_' . date('Y-m-d_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $query = MemberRank::withCount('members');

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where('name', 'like', "%{$search}%");
        }

        $titles = $query->orderBy('level', 'desc')->orderBy('name', 'asc')->get();

        $callback = function () use ($titles) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Title ID',
                'Title Name',
                'Hierarchy Level',
                'Assigned Members Count',
                'Date Created'
            ]);

            foreach ($titles as $t) {
                fputcsv($file, [
                    $t->id,
                    $t->name,
                    $t->level,
                    $t->members_count ?? 0,
                    $t->created_at ? $t->created_at->format('Y-m-d H:i:s') : '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export loan sub-statuses to CSV format with unpaginated filter enforcement
     */
    public function exportSubStatusesCsv(Request $request)
    {
        $user = auth()->user();
        [$isOfficer] = $this->resolveOfficerStatus($user);

        if (!$isOfficer) {
            abort(403, 'Unauthorized.');
        }

        $fileName = 'loan_sub_statuses_' . date('Y-m-d_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $query = LoanSubStatus::withCount('loanRequests');

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where('name', 'like', "%{$search}%");
        }

        $subStatuses = $query->orderBy('name', 'asc')->get();

        $callback = function () use ($subStatuses) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Sub-Status ID',
                'Name',
                'Badge Color',
                'Associated Loans Count',
                'Date Created'
            ]);

            foreach ($subStatuses as $ss) {
                fputcsv($file, [
                    $ss->id,
                    $ss->name,
                    ucfirst($ss->color ?? 'slate'),
                    $ss->loan_requests_count ?? 0,
                    $ss->created_at ? $ss->created_at->format('Y-m-d H:i:s') : '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export user role assignments to CSV format with unpaginated filter enforcement
     */
    public function exportUserRolesCsv(Request $request)
    {
        $user = auth()->user();
        [$isOfficer] = $this->resolveOfficerStatus($user);

        if (!$isOfficer) {
            abort(403, 'Unauthorized.');
        }

        $fileName = 'user_roles_assignments_' . date('Y-m-d_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $query = User::with(['roles', 'member']);

        if ($request->filled('role_filter')) {
            $roleFilter = $request->get('role_filter');
            $query->whereHas('roles', function ($rq) use ($roleFilter) {
                $rq->where('name', $roleFilter);
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($mq) use ($search) {
                      $mq->where('member_code', 'like', "%{$search}%")
                         ->orWhere('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $users = $query->orderBy('name', 'asc')->get();

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'User ID',
                'Name',
                'Email',
                'Linked Member Code',
                'Assigned Roles',
                'Date Registered'
            ]);

            foreach ($users as $u) {
                fputcsv($file, [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->member ? $u->member->member_code : '-',
                    $u->roles->pluck('name')->join(', ') ?: 'None',
                    $u->created_at ? $u->created_at->format('Y-m-d H:i:s') : '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export savings deposit requests to CSV format with unpaginated filter enforcement
     */
    public function exportSavingsDepositRequestsCsv(Request $request)
    {
        $user = auth()->user();
        [$isOfficer, $member] = $this->resolveOfficerStatus($user);

        $fileName = 'deposit_requests_' . date('Y-m-d_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $query = SavingsDepositRequest::with(['member', 'reviewer']);

        if (!$isOfficer) {
            if (!$member) {
                abort(403, 'Unauthorized.');
            }
            $query->where('member_id', $member->id);
        } elseif ($request->filled('member_id')) {
            $query->where('member_id', $request->get('member_id'));
        }

        if ($request->filled('status') && $request->get('status') !== 'all') {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($mq) use ($search) {
                      $mq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('member_code', 'like', "%{$search}%");
                  });
            });
        }

        $requests = $query->orderBy('submitted_at', 'desc')->orderBy('id', 'desc')->get();

        $callback = function () use ($requests) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Request ID',
                'Member Code',
                'Member Name',
                'Amount ($)',
                'Status',
                'Date Submitted',
                'Reviewed By',
                'Date Reviewed',
                'Review Notes',
                'Member Notes'
            ]);

            foreach ($requests as $r) {
                fputcsv($file, [
                    $r->id,
                    $r->member ? $r->member->member_code : '-',
                    $r->member ? $r->member->name : 'N/A',
                    number_format($r->amount, 2, '.', ''),
                    ucfirst($r->status),
                    $r->submitted_at ? $r->submitted_at->format('Y-m-d H:i:s') : ($r->created_at ? $r->created_at->format('Y-m-d H:i:s') : '-'),
                    $r->reviewer ? $r->reviewer->name : '-',
                    $r->reviewed_at ? $r->reviewed_at->format('Y-m-d H:i:s') : '-',
                    $r->review_note ?: '-',
                    $r->notes ?: '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export savings withdrawal requests to CSV format with unpaginated filter enforcement
     */
    public function exportSavingsWithdrawalRequestsCsv(Request $request)
    {
        $user = auth()->user();
        [$isOfficer, $member] = $this->resolveOfficerStatus($user);

        $fileName = 'withdrawal_requests_' . date('Y-m-d_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $query = SavingsWithdrawalRequest::with(['member', 'reviewer', 'disbursedBy']);

        if (!$isOfficer) {
            if (!$member) {
                abort(403, 'Unauthorized.');
            }
            $query->where('member_id', $member->id);
        } elseif ($request->filled('member_id')) {
            $query->where('member_id', $request->get('member_id'));
        }

        if ($request->filled('status') && $request->get('status') !== 'all') {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('payout_details', 'like', "%{$search}%")
                  ->orWhere('gross_amount', 'like', "%{$search}%")
                  ->orWhere('payout_method', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($mq) use ($search) {
                      $mq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('member_code', 'like', "%{$search}%");
                  });
            });
        }

        $requests = $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get();

        $callback = function () use ($requests) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Request ID',
                'Member Code',
                'Member Name',
                'Gross Amount ($)',
                'Fee Amount ($)',
                'Net Amount ($)',
                'Payout Method',
                'Payout Details',
                'Status',
                'Date Requested',
                'Reviewed By',
                'Date Reviewed',
                'Disbursed By',
                'Disbursed Date',
                'Disbursement Method',
                'Disbursement Reference',
                'Admin Notes / Rejection Reason'
            ]);

            foreach ($requests as $r) {
                fputcsv($file, [
                    $r->id,
                    $r->member ? $r->member->member_code : '-',
                    $r->member ? $r->member->name : 'N/A',
                    number_format($r->gross_amount, 2, '.', ''),
                    number_format($r->fee_amount ?? 0, 2, '.', ''),
                    number_format($r->net_amount ?? $r->gross_amount, 2, '.', ''),
                    ucfirst(str_replace('_', ' ', $r->payout_method ?? '-')),
                    $r->masked_payout_details ?? ($r->payout_details ?: '-'),
                    ucfirst($r->status),
                    $r->created_at ? $r->created_at->format('Y-m-d H:i:s') : '-',
                    $r->reviewer ? $r->reviewer->name : '-',
                    $r->reviewed_at ? $r->reviewed_at->format('Y-m-d H:i:s') : '-',
                    $r->disbursedBy ? $r->disbursedBy->name : '-',
                    $r->disbursed_date ? $r->disbursed_date->format('Y-m-d') : ($r->disbursed_at ? $r->disbursed_at->format('Y-m-d') : '-'),
                    $r->disbursement_method ? ucfirst(str_replace('_', ' ', $r->disbursement_method)) : '-',
                    $r->disbursement_reference ?: '-',
                    $r->rejection_reason ?: ($r->admin_notes ?: ($r->notes ?: '-'))
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export loan repayment requests to CSV format with unpaginated filter enforcement
     */
    public function exportLoanRepaymentRequestsCsv(Request $request)
    {
        $user = auth()->user();
        [$isOfficer, $member] = $this->resolveOfficerStatus($user);

        $fileName = 'loan_repayment_requests_' . date('Y-m-d_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $query = LoanRepaymentRequest::with(['member', 'loanRequest', 'reviewer']);

        if (!$isOfficer) {
            if (!$member) {
                abort(403, 'Unauthorized.');
            }
            $query->where('member_id', $member->id);
        } elseif ($request->filled('member_id')) {
            $query->where('member_id', $request->get('member_id'));
        }

        if ($request->filled('status') && $request->get('status') !== 'all') {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($mq) use ($search) {
                      $mq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('member_code', 'like', "%{$search}%");
                  });
            });
        }

        $requests = $query->orderBy('submitted_at', 'desc')->orderBy('id', 'desc')->get();

        $callback = function () use ($requests) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Request ID',
                'Loan ID',
                'Member Code',
                'Borrower Name',
                'Amount ($)',
                'Payment Method',
                'Reference #',
                'Payment Date',
                'Status',
                'Date Submitted',
                'Reviewed By',
                'Review Notes',
                'Member Notes'
            ]);

            foreach ($requests as $r) {
                fputcsv($file, [
                    $r->id,
                    $r->loan_request_id,
                    $r->member ? $r->member->member_code : '-',
                    $r->member ? $r->member->name : 'N/A',
                    number_format($r->amount, 2, '.', ''),
                    ucfirst(str_replace('_', ' ', $r->payment_method ?? 'cash')),
                    $r->reference_number ?: '-',
                    $r->payment_date ? $r->payment_date->format('Y-m-d') : '-',
                    ucfirst($r->status),
                    $r->submitted_at ? $r->submitted_at->format('Y-m-d H:i:s') : ($r->created_at ? $r->created_at->format('Y-m-d H:i:s') : '-'),
                    $r->reviewer ? $r->reviewer->name : '-',
                    $r->review_note ?: '-',
                    $r->notes ?: '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export njangi payment submissions to CSV format with unpaginated filter enforcement
     */
    public function exportNjangiSubmissionsCsv(Request $request)
    {
        $user = auth()->user();
        [$isOfficer, $member] = $this->resolveOfficerStatus($user);

        $fileName = 'njangi_submissions_' . date('Y-m-d_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $query = NjangiPaymentSubmission::with(['cycle', 'session', 'member', 'reviewer']);

        if (!$isOfficer) {
            if (!$member) {
                abort(403, 'Unauthorized.');
            }
            $query->where('member_id', $member->id);
        } elseif ($request->filled('member_id')) {
            $query->where('member_id', $request->get('member_id'));
        }

        if ($request->filled('status') && $request->get('status') !== 'all') {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('cycle_id')) {
            $query->where('njangi_cycle_id', $request->get('cycle_id'));
        }

        if ($request->filled('session_id')) {
            $query->where('njangi_session_id', $request->get('session_id'));
        }

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('member_note', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($mq) use ($search) {
                      $mq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('member_code', 'like', "%{$search}%");
                  });
            });
        }

        $submissions = $query->orderBy('submitted_at', 'desc')->orderBy('id', 'desc')->get();

        $callback = function () use ($submissions) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Submission ID',
                'Cycle',
                'Session',
                'Member Code',
                'Member Name',
                'Amount ($)',
                'Attendance Status',
                'Status',
                'Date Submitted',
                'Reviewed By',
                'Review Notes',
                'Member Notes'
            ]);

            foreach ($submissions as $s) {
                fputcsv($file, [
                    $s->id,
                    $s->cycle ? $s->cycle->name : 'N/A',
                    $s->session ? ($s->session->title ?? 'Session #' . $s->session->session_number) : 'N/A',
                    $s->member ? $s->member->member_code : '-',
                    $s->member ? $s->member->name : 'N/A',
                    number_format($s->amount, 2, '.', ''),
                    $s->is_attending ? 'Attending' : 'Not Attending',
                    ucfirst($s->status),
                    $s->submitted_at ? $s->submitted_at->format('Y-m-d H:i:s') : ($s->created_at ? $s->created_at->format('Y-m-d H:i:s') : '-'),
                    $s->reviewer ? $s->reviewer->name : '-',
                    $s->review_note ?: '-',
                    $s->member_note ?: '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
