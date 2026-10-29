<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define all permissions grouped by module
        $permissions = [
            // Attendance
            'attendance.rsvp',
            'attendance.view_own',
            'attendance.mark',
            'attendance.view_all',

            // Loans
            'loans.apply',
            'loans.view_own',
            'loans.committee_review',
            'loans.approve',
            'loans.reject',
            'loans.disburse',
            'loans.manage_sub_statuses',

            // Savings
            'savings.deposit_request',
            'savings.withdraw_request',
            'savings.view_own',
            'savings.record_deposit',
            'savings.approve_withdrawal',
            'savings.disburse_withdrawal',
            'savings.view_all',

            // Njangi
            'njangi.submit_payment',
            'njangi.view_own',
            'njangi.audit_submissions',
            'njangi.manage_cycles',
            'njangi.disburse',

            // Administration & Roles
            'roles.manage',
            'members.manage',
            'settings.manage',
            'tools.manage',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // 2. Define standard organizational roles
        // Member role
        $memberRole = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
        $memberRole->syncPermissions([
            'attendance.rsvp',
            'attendance.view_own',
            'loans.apply',
            'loans.view_own',
            'savings.deposit_request',
            'savings.withdraw_request',
            'savings.view_own',
            'njangi.submit_payment',
            'njangi.view_own',
        ]);

        // General Secretary role
        $secRole = Role::firstOrCreate(['name' => 'General Secretary', 'guard_name' => 'web']);
        $secRole->syncPermissions([
            'attendance.rsvp',
            'attendance.view_own',
            'attendance.mark',
            'attendance.view_all',
            'members.manage',
            'savings.view_all',
        ]);

        // Loan Committee role (Reviews and Approves/Rejects, but cannot disburse)
        $loanCommRole = Role::firstOrCreate(['name' => 'Loan Committee', 'guard_name' => 'web']);
        $loanCommRole->syncPermissions([
            'loans.committee_review',
            'loans.approve',
            'loans.reject',
            'loans.manage_sub_statuses',
            'loans.view_own',
            'attendance.rsvp',
            'attendance.view_own',
            'savings.view_all',
        ]);

        // Financial Secretary role (Records collections and deposits, audits Njangi, but cannot disburse)
        $finSecRole = Role::firstOrCreate(['name' => 'Financial Secretary', 'guard_name' => 'web']);
        $finSecRole->syncPermissions([
            'savings.record_deposit',
            'savings.view_all',
            'njangi.audit_submissions',
            'njangi.manage_cycles',
            'attendance.rsvp',
            'attendance.view_own',
            'attendance.view_all',
        ]);

        // Treasurer role (Official Custodian & Disburser of funds)
        $treasurerRole = Role::firstOrCreate(['name' => 'Treasurer', 'guard_name' => 'web']);
        $treasurerRole->syncPermissions([
            'loans.disburse',
            'savings.disburse_withdrawal',
            'savings.view_all',
            'njangi.disburse',
            'attendance.rsvp',
            'attendance.view_own',
            'attendance.view_all',
        ]);

        // Admin role (Superuser with all permissions)
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());
    }
}
