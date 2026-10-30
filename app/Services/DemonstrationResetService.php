<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\ComprehensiveDemonstrationSeeder;
use Spatie\Permission\PermissionRegistrar;

class DemonstrationResetService
{
    public const SECRET_CODE = 'Mou_Amar_Bou_Ektu_Santo_Hou';

    /**
     * Check if the provided confirmation code matches the exact secret string.
     */
    public function verifySecretCode(?string $input): bool
    {
        return trim((string) $input) === self::SECRET_CODE;
    }

    /**
     * Completely wipe all application database tables and freshly seed the demonstration data.
     */
    public function wipeAndSeedFresh(): array
    {
        $driver = DB::getDriverName();

        // 1. Disable Foreign Keys Safely
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        // 2. Truncate All Application Tables
        $tablesToTruncate = [
            'attendance_audit_logs',
            'meeting_guests',
            'meeting_attendances',
            'loan_repayment_requests',
            'loan_repayments',
            'loan_guarantors',
            'loan_requests',
            'loan_sub_statuses',
            'savings_withdrawal_requests',
            'savings_deposit_requests',
            'savings_transactions',
            'njangi_disbursements',
            'njangi_contributions',
            'njangi_payment_submissions',
            'njangi_session_beneficiaries',
            'njangi_sessions',
            'njangi_cycle_members',
            'njangi_cycles',
            'member_role_member',
            'members',
            'member_roles',
            'member_ranks',
            'royal_authorities',
            'model_has_roles',
            'model_has_permissions',
            'role_has_permissions',
            'roles',
            'permissions',
            'users',
            'settings',
            'organizations',
        ];

        foreach ($tablesToTruncate as $tableName) {
            if (Schema::hasTable($tableName)) {
                DB::table($tableName)->truncate();
            }
        }

        // 3. Re-enable Foreign Keys
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        // 4. Reset Spatie Cached Permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 5. Run Comprehensive Demonstration Seeder
        $seeder = new ComprehensiveDemonstrationSeeder();
        $seeder->run();

        // 6. Gather Final Data Statistics
        return $this->gatherSummaryStatistics();
    }

    /**
     * Gather count of all seeded records to report to the user.
     */
    public function gatherSummaryStatistics(): array
    {
        return [
            'users_count'                  => \App\Models\User::count(),
            'members_count'                => \App\Models\Member::count(),
            'ranks_count'                  => \App\Models\MemberRank::count(),
            'member_roles_count'           => \App\Models\MemberRole::count(),
            'spatie_roles_count'           => \Spatie\Permission\Models\Role::count(),
            'cycles_count'                 => \App\Models\NjangiCycle::count(),
            'sessions_count'               => \App\Models\NjangiSession::count(),
            'contributions_count'          => \App\Models\NjangiContribution::count(),
            'payment_submissions_count'   => \App\Models\NjangiPaymentSubmission::count(),
            'savings_transactions_count'   => \App\Models\SavingsTransaction::count(),
            'savings_deposits_req_count'   => \App\Models\SavingsDepositRequest::count(),
            'savings_withdrawals_req_count'=> \App\Models\SavingsWithdrawalRequest::count(),
            'loans_count'                  => \App\Models\LoanRequest::count(),
            'loan_repayments_count'        => \App\Models\LoanRepayment::count(),
            'loan_repayment_req_count'     => \App\Models\LoanRepaymentRequest::count(),
            'attendances_count'            => \App\Models\MeetingAttendance::count(),
            'guests_count'                 => \App\Models\MeetingGuest::count(),
        ];
    }
}
