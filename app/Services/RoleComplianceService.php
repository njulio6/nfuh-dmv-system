<?php

namespace App\Services;

use App\Models\User;

class RoleComplianceService
{
    /**
     * Incompatible role pairs representing segregation of duties conflicts.
     */
    protected static array $conflictPairs = [
        [
            'roles' => ['Treasurer', 'Loan Committee'],
            'title' => 'Approver & Disburser Overlap (Loan Governance)',
            'description' => 'The Loan Committee evaluates and approves member loan applications, while the Treasurer disburses organization cash. Combining both roles on one user bypasses independent financial dual-control.',
        ],
        [
            'roles' => ['Treasurer', 'Financial Secretary'],
            'title' => 'Bookkeeper & Custodian Overlap (Financial Governance)',
            'description' => 'The Financial Secretary records deposits and audits session contributions, while the Treasurer holds and pays out organization funds. Combining record-keeping with cash custody violates segregation of duties.',
        ],
    ];

    /**
     * Check if a proposed set of roles contains segregation of duties conflicts.
     */
    public function checkRoleConflicts(User $user, array $roleNames): array
    {
        $detectedConflicts = [];

        foreach (self::$conflictPairs as $pair) {
            $required = $pair['roles'];
            // Check if both roles are present in the proposed role list
            $intersect = array_intersect($required, $roleNames);
            if (count($intersect) === count($required)) {
                $detectedConflicts[] = [
                    'roles' => $pair['roles'],
                    'title' => $pair['title'],
                    'description' => $pair['description'],
                ];
            }
        }

        $hasConflict = !empty($detectedConflicts);

        $warningMessage = '';
        if ($hasConflict) {
            $titles = array_column($detectedConflicts, 'title');
            $warningMessage = 'Advisory Warning: ' . implode('; ', $titles) . '. While you may proceed as administrator, assigning these overlapping financial duties to a single user reduces internal financial checks and balances.';
        }

        return [
            'has_conflict' => $hasConflict,
            'conflicts' => $detectedConflicts,
            'warning_message' => $warningMessage,
        ];
    }

    /**
     * Get all documented segregation of duties rules.
     */
    public static function getConflictRules(): array
    {
        return self::$conflictPairs;
    }

    /**
     * Human-readable permission metadata dictionary with titles, descriptions, and icons.
     */
    public static function getPermissionDefinitions(): array
    {
        return [
            // Attendance
            'attendance.rsvp' => [
                'label' => 'Submit RSVP',
                'description' => 'Members indicate planned attendance before meetings.',
                'icon' => 'calendar-check',
            ],
            'attendance.view_own' => [
                'label' => 'View Own Attendance',
                'description' => 'Members view their personal attendance record.',
                'icon' => 'user-check',
            ],
            'attendance.mark' => [
                'label' => 'Mark Attendance',
                'description' => 'Officers conduct roll-call and mark attendance.',
                'icon' => 'clipboard-check',
            ],
            'attendance.view_all' => [
                'label' => 'View All Attendance',
                'description' => 'Officers view attendance records of all members.',
                'icon' => 'users',
            ],
            'attendance.clear_records' => [
                'label' => 'Clear Session Attendance',
                'description' => 'Reset or clear recorded attendance statuses for a session with mandatory confirmation.',
                'icon' => 'trash-2',
            ],

            // Loans
            'loans.apply' => [
                'label' => 'Apply for Loans',
                'description' => 'Members submit loan applications and guarantors.',
                'icon' => 'file-text',
            ],
            'loans.view_own' => [
                'label' => 'View Own Loans',
                'description' => 'Members view their own loans and repayment history.',
                'icon' => 'trending-up',
            ],
            'loans.committee_review' => [
                'label' => 'Committee Review',
                'description' => 'Committee evaluates pending loan applications.',
                'icon' => 'check-square',
            ],
            'loans.approve' => [
                'label' => 'Approve Loans',
                'description' => 'Loan Committee approves loan requests and terms.',
                'icon' => 'thumbs-up',
            ],
            'loans.reject' => [
                'label' => 'Reject Loans',
                'description' => 'Loan Committee rejects loan requests with reasons.',
                'icon' => 'thumbs-down',
            ],
            'loans.disburse' => [
                'label' => 'Disburse Loans',
                'description' => 'Treasurer disburses approved loan funds with receipts.',
                'icon' => 'send',
            ],
            'loans.manage_sub_statuses' => [
                'label' => 'Manage Sub-Statuses',
                'description' => 'Configure custom tags, delinquent, and recovery statuses.',
                'icon' => 'tag',
            ],

            // Savings
            'savings.deposit_request' => [
                'label' => 'Deposit Request',
                'description' => 'Members submit savings deposit requests with payment proofs.',
                'icon' => 'plus-circle',
            ],
            'savings.withdraw_request' => [
                'label' => 'Withdraw Request',
                'description' => 'Members submit savings withdrawal requests.',
                'icon' => 'arrow-down-circle',
            ],
            'savings.view_own' => [
                'label' => 'View Own Savings',
                'description' => 'Members view their savings statements and history.',
                'icon' => 'piggy-bank',
            ],
            'savings.record_deposit' => [
                'label' => 'Record Deposit',
                'description' => 'Financial Secretary records and verifies direct deposits.',
                'icon' => 'check-circle-2',
            ],
            'savings.approve_withdrawal' => [
                'label' => 'Approve Withdrawal',
                'description' => 'Officers review and approve withdrawal requests.',
                'icon' => 'check',
            ],
            'savings.disburse_withdrawal' => [
                'label' => 'Disburse Withdrawal',
                'description' => 'Treasurer executes and confirms withdrawal payouts.',
                'icon' => 'credit-card',
            ],
            'savings.view_all' => [
                'label' => 'View All Savings',
                'description' => 'Officers view all member savings balances and transactions.',
                'icon' => 'landmark',
            ],

            // Njangi
            'njangi.submit_payment' => [
                'label' => 'Submit Payment',
                'description' => 'Members submit rotational session payment slips.',
                'icon' => 'receipt',
            ],
            'njangi.view_own' => [
                'label' => 'View Own Njangi',
                'description' => 'Members view their round turns, status, and payouts.',
                'icon' => 'award',
            ],
            'njangi.audit_submissions' => [
                'label' => 'Audit Submissions',
                'description' => 'Financial Secretary audits and verifies contribution slips.',
                'icon' => 'file-check',
            ],
            'njangi.manage_cycles' => [
                'label' => 'Manage Cycles',
                'description' => 'Officers configure rotational cycles, rosters, and rounds.',
                'icon' => 'refresh-cw',
            ],
            'njangi.disburse' => [
                'label' => 'Disburse Pot',
                'description' => 'Treasurer disburses collected funds to session beneficiaries.',
                'icon' => 'gift',
            ],

            // Administration & Roles
            'roles.manage' => [
                'label' => 'Manage Roles',
                'description' => 'Full access to role assignments and permission matrix.',
                'icon' => 'shield-alert',
            ],
            'members.manage' => [
                'label' => 'Manage Members',
                'description' => 'Create, edit, and manage member profiles and titles.',
                'icon' => 'user-cog',
            ],
            'settings.manage' => [
                'label' => 'Manage Settings',
                'description' => 'Update branding, themes, logos, and financial limits.',
                'icon' => 'settings',
            ],
            'tools.manage' => [
                'label' => 'Manage System Tools',
                'description' => 'Run migrations, clear system caches, and link storage.',
                'icon' => 'wrench',
            ],
        ];
    }

    /**
     * Role definitions with descriptions, colors, and badge metadata.
     */
    public static function getRoleDefinitions(): array
    {
        return [
            'admin' => [
                'name' => 'admin',
                'display_name' => 'Super Administrator',
                'badge_color' => 'purple',
                'description' => 'Full unconstrained system authority over all organizational modules and administrative tools.',
                'icon' => 'shield-check',
            ],
            'Treasurer' => [
                'name' => 'Treasurer',
                'display_name' => 'Treasurer (Cash Custodian)',
                'badge_color' => 'emerald',
                'description' => 'Sole disburser of organization funds (loans, savings withdrawals, and Njangi pots). Requires dual-control with approvers.',
                'icon' => 'wallet',
            ],
            'Financial Secretary' => [
                'name' => 'Financial Secretary',
                'display_name' => 'Financial Secretary (Bookkeeper)',
                'badge_color' => 'blue',
                'description' => 'Records member deposits, audits Njangi contributions, and manages rotational ledgers. Cannot disburse funds.',
                'icon' => 'file-text',
            ],
            'Loan Committee' => [
                'name' => 'Loan Committee',
                'display_name' => 'Loan Committee Approver',
                'badge_color' => 'indigo',
                'description' => 'Evaluates creditworthiness, approves and rejects loans. Forbidden from disbursing loans to prevent self-dealing.',
                'icon' => 'landmark',
            ],
            'General Secretary' => [
                'name' => 'General Secretary',
                'display_name' => 'General Secretary (Roster & Records)',
                'badge_color' => 'amber',
                'description' => 'Conducts official meeting roll-call, manages attendance desk records, and maintains active member registers.',
                'icon' => 'clipboard-check',
            ],
            'member' => [
                'name' => 'member',
                'display_name' => 'General Member',
                'badge_color' => 'zinc',
                'description' => 'Used by administrators to enforce custom portal access restrictions. When unassigned, members enjoy full general portal access by default.',
                'icon' => 'user',
            ],
        ];
    }
}
