<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberRole;
use App\Models\Setting;
use App\Models\User;
use App\Services\RoleComplianceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    protected RoleComplianceService $complianceService;

    public function __construct(RoleComplianceService $complianceService)
    {
        $this->complianceService = $complianceService;
    }

    /**
     * Display Roles & Permissions Matrix and User Role Assignments.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user || (!$user->hasRole('admin') && !$user->can('roles.manage'))) {
            abort(403, 'Unauthorized action. Only administrators can manage roles and permissions.');
        }

        // Fetch all roles with their assigned permissions
        $roles = Role::with('permissions')->get();

        // Fetch all permissions grouped by domain module
        $allPermissions = Permission::orderBy('name')->get();
        $permissionsByModule = $allPermissions->groupBy(function ($perm) {
            $parts = explode('.', $perm->name);
            return ucfirst($parts[0] ?? 'General');
        });

        // Query users with their roles and linked member profiles
        $usersQuery = User::with(['roles', 'member.rank'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($mq) use ($search) {
                      $mq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('member_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('role_filter')) {
            $roleFilter = $request->role_filter;
            $usersQuery->whereHas('roles', function ($q) use ($roleFilter) {
                $q->where('name', $roleFilter);
            });
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 30, 50])) {
            $perPage = 10;
        }

        $users = $usersQuery->paginate($perPage)->withQueryString();
        $totalUsersCount = User::count();

        $conflictRules = RoleComplianceService::getConflictRules();
        $permissionMeta = RoleComplianceService::getPermissionDefinitions();
        $roleDefinitions = RoleComplianceService::getRoleDefinitions();
        $appSettings = Setting::first();

        return view('admin.roles.index', compact(
            'roles',
            'allPermissions',
            'permissionsByModule',
            'users',
            'conflictRules',
            'permissionMeta',
            'roleDefinitions',
            'totalUsersCount',
            'perPage',
            'appSettings'
        ));
    }

    /**
     * Update permissions assigned to a role.
     */
    public function updateRolePermissions(Request $request, Role $role)
    {
        $user = Auth::user();
        if (!$user || (!$user->hasRole('admin') && !$user->can('roles.manage'))) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('admin.roles.index', ['tab' => 'matrix', 'role' => $role->name])->with('success', "Permissions for role '{$role->name}' updated successfully.");
    }

    /**
     * Assign or update roles for a user with Segregation of Duties advisory validation.
     */
    public function assignUserRoles(Request $request, User $user)
    {
        $admin = Auth::user();
        if (!$admin || (!$admin->hasRole('admin') && !$admin->can('roles.manage'))) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
            'confirm_override' => 'nullable|boolean',
        ]);

        $proposedRoles = $request->roles ?? [];

        // Check for segregation of duties conflicts
        $compliance = $this->complianceService->checkRoleConflicts($user, $proposedRoles);

        if ($compliance['has_conflict'] && !$request->boolean('confirm_override')) {
            return redirect()->route('admin.roles.index', ['tab' => 'users'])->with([
                'compliance_warning' => true,
                'target_user_id' => $user->id,
                'target_user_name' => $user->name,
                'proposed_roles' => $proposedRoles,
                'conflicts' => $compliance['conflicts'],
                'warning_message' => $compliance['warning_message'],
            ]);
        }

        // Sync Spatie roles
        $user->syncRoles($proposedRoles);

        // Also sync legacy MemberRole records for backwards-compatibility if linked
        $linkedMember = \App\Support\MemberResolver::fromUser($user);
        if ($linkedMember) {
            $legacyRoleIds = MemberRole::whereIn('name', $proposedRoles)->pluck('id');
            $linkedMember->roles()->sync($legacyRoleIds);
        }

        $msg = "Roles for user '{$user->name}' updated successfully.";
        if ($compliance['has_conflict']) {
            $msg .= " (Note: Segregation of Duties advisory warning acknowledged by Administrator).";
        }

        return redirect()->route('admin.roles.index', ['tab' => 'users'])->with('success', $msg);
    }

    /**
     * Safely synchronize and seed default organizational roles & permissions.
     * 
     * SAFE & NON-DESTRUCTIVE:
     * - Only creates missing system permissions; never deletes existing ones.
     * - Only creates missing roles; never removes existing custom roles.
     * - Preserves customized role permissions; does not reset roles that have custom permissions.
     * - Never detaches or deletes user role assignments.
     */
    public function syncDefaultRolesAndPermissions(Request $request)
    {
        $user = Auth::user();
        $isSuper = $user && (!$user->member || $user->hasRole('admin') || $user->can('roles.manage'));
        if (!$user || !$isSuper) {
            abort(403, 'Unauthorized action. Only administrators can synchronize roles and permissions.');
        }

        // Flush cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Master catalog of all standard system permissions
        $standardPermissions = [
            // Attendance
            'attendance.rsvp',
            'attendance.view_own',
            'attendance.mark',
            'attendance.view_all',
            'attendance.clear_records',

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
        ];

        $newPermissionsCreated = 0;
        $allCreatedPermissions = [];
        foreach ($standardPermissions as $permName) {
            $perm = Permission::where('name', $permName)->where('guard_name', 'web')->first();
            if (!$perm) {
                Permission::create(['name' => $permName, 'guard_name' => 'web']);
                $newPermissionsCreated++;
                $allCreatedPermissions[] = $permName;
            }
        }

        // 2. Default baseline role profiles
        $defaultRolesConfig = [
            'member' => [
                'permissions' => [
                    'attendance.rsvp',
                    'attendance.view_own',
                    'loans.apply',
                    'loans.view_own',
                    'savings.deposit_request',
                    'savings.withdraw_request',
                    'savings.view_own',
                    'njangi.submit_payment',
                    'njangi.view_own',
                ],
            ],
            'General Secretary' => [
                'permissions' => [
                    'attendance.rsvp',
                    'attendance.view_own',
                    'attendance.mark',
                    'attendance.view_all',
                    'members.manage',
                    'savings.view_all',
                ],
            ],
            'Loan Committee' => [
                'permissions' => [
                    'loans.committee_review',
                    'loans.approve',
                    'loans.reject',
                    'loans.manage_sub_statuses',
                    'loans.view_own',
                    'attendance.rsvp',
                    'attendance.view_own',
                    'savings.view_all',
                ],
            ],
            'Financial Secretary' => [
                'permissions' => [
                    'savings.record_deposit',
                    'savings.view_all',
                    'njangi.audit_submissions',
                    'njangi.manage_cycles',
                    'attendance.rsvp',
                    'attendance.view_own',
                    'attendance.view_all',
                ],
            ],
            'Treasurer' => [
                'permissions' => [
                    'loans.disburse',
                    'savings.disburse_withdrawal',
                    'savings.view_all',
                    'njangi.disburse',
                    'attendance.rsvp',
                    'attendance.view_own',
                    'attendance.view_all',
                ],
            ],
            'admin' => [
                'permissions' => 'ALL',
            ],
        ];

        $newRolesCreated = 0;
        $rolesInitialized = 0;

        foreach ($defaultRolesConfig as $roleName => $config) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if (!$role) {
                // Role is completely missing from database: create it and assign default permissions
                $role = Role::create(['name' => $roleName, 'guard_name' => 'web']);
                $newRolesCreated++;

                if ($config['permissions'] === 'ALL') {
                    $role->syncPermissions(Permission::all());
                } else {
                    $role->syncPermissions($config['permissions']);
                }
            } else {
                // Role already exists in DB!
                // CONDITION: If the role has 0 permissions (e.g. empty role after migration), assign baseline defaults
                if ($role->permissions()->count() === 0) {
                    if ($config['permissions'] === 'ALL') {
                        $role->syncPermissions(Permission::all());
                    } else {
                        $role->syncPermissions($config['permissions']);
                    }
                    $rolesInitialized++;
                } else {
                    // Role already has custom permissions: PRESERVE THEM!
                    // For admin, ensure any newly added system permissions are given to admin
                    if ($roleName === 'admin' && count($allCreatedPermissions) > 0) {
                        $role->givePermissionTo($allCreatedPermissions);
                    }
                }
            }
        }

        // 3. Ensure current acting administrator has the admin role
        if (!$user->hasRole('admin')) {
            $user->assignRole('admin');
        }

        // Flush permissions cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Build human-friendly message
        $details = [];
        if ($newPermissionsCreated > 0) $details[] = "{$newPermissionsCreated} missing permission(s) created";
        if ($newRolesCreated > 0) $details[] = "{$newRolesCreated} missing role(s) created";
        if ($rolesInitialized > 0) $details[] = "{$rolesInitialized} empty role(s) initialized";

        if (count($details) > 0) {
            $feedback = "Roles & permissions synchronized successfully: " . implode(', ', $details) . ". Existing user assignments and custom permissions were fully preserved.";
        } else {
            $feedback = "All standard system roles and permissions are already verified and intact. No custom permissions or user assignments were changed.";
        }

        return redirect()->route('admin.roles.index', ['tab' => 'matrix'])
            ->with('success', $feedback);
    }
}
