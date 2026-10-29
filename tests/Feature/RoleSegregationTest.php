<?php

use App\Models\LoanRequest;
use App\Models\Member;
use App\Models\Organization;
use App\Models\SavingsTransaction;
use App\Models\SavingsWithdrawalRequest;
use App\Models\User;
use App\Services\RoleComplianceService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed standard roles and permissions
    (new RolesAndPermissionsSeeder())->run();
});

test('spatie roles and permissions are correctly seeded and assigned', function () {
    expect(Role::where('name', 'admin')->exists())->toBeTrue();
    expect(Role::where('name', 'Treasurer')->exists())->toBeTrue();
    expect(Role::where('name', 'Loan Committee')->exists())->toBeTrue();
    expect(Role::where('name', 'Financial Secretary')->exists())->toBeTrue();
    expect(Role::where('name', 'General Secretary')->exists())->toBeTrue();
    expect(Role::where('name', 'member')->exists())->toBeTrue();

    $treasurer = Role::findByName('Treasurer');
    expect($treasurer->hasPermissionTo('loans.disburse'))->toBeTrue();
    expect($treasurer->hasPermissionTo('loans.approve'))->toBeFalse();

    $committee = Role::findByName('Loan Committee');
    expect($committee->hasPermissionTo('loans.approve'))->toBeTrue();
    expect($committee->hasPermissionTo('loans.disburse'))->toBeFalse();
});

test('admin can view and update permissions assigned to roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.roles.index'));
    $response->assertStatus(200);
    $response->assertSee('Roles');
    $response->assertSee('Segregation of Duties');
    $response->assertSee('Treasurer');
    $response->assertSee('Loan Committee');

    $treasurer = Role::findByName('Treasurer');
    $updateResponse = $this->actingAs($admin)->put(route('admin.roles.update-permissions', $treasurer->id), [
        'permissions' => ['loans.disburse', 'savings.view_all'],
    ]);
    $updateResponse->assertRedirect();
    $updateResponse->assertSessionHas('success');
    expect($treasurer->fresh()->hasPermissionTo('loans.disburse'))->toBeTrue();
});

test('compliance engine detects conflicting roles (Loan Committee + Treasurer) and generates advisory warning', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create();

    // Assigning both Treasurer and Loan Committee without confirm_override should return warning in session
    $response = $this->actingAs($admin)->put(route('admin.users.assign-roles', $targetUser->id), [
        'roles' => ['Treasurer', 'Loan Committee'],
        'confirm_override' => 0,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('compliance_warning', true);
    $response->assertSessionHas('conflicts');
});

test('compliance engine detects conflicting roles (Financial Secretary + Treasurer) and generates advisory warning', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create();

    $service = new RoleComplianceService();
    $result = $service->checkRoleConflicts($targetUser, ['Financial Secretary', 'Treasurer']);
    expect($result['has_conflict'])->toBeTrue();
    expect($result['warning_message'])->toContain('Bookkeeper & Custodian Overlap');
});

test('admin can acknowledge warning and proceed with assignment if authorized', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create();

    $response = $this->actingAs($admin)->put(route('admin.users.assign-roles', $targetUser->id), [
        'roles' => ['Treasurer', 'Loan Committee'],
        'confirm_override' => 1,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($targetUser->fresh()->hasRole('Treasurer'))->toBeTrue();
    expect($targetUser->fresh()->hasRole('Loan Committee'))->toBeTrue();
});

test('user with Loan Committee role can approve loans but is forbidden from disbursing them (403)', function () {
    Storage::fake('public');
    $org = Organization::create(['name' => 'Org 1']);

    $committeeUser = User::factory()->create(['organization_id' => $org->id]);
    $committeeUser->assignRole('Loan Committee');

    $borrowerUser = User::factory()->create(['organization_id' => $org->id]);
    $borrower = Member::create([
        'organization_id' => $org->id,
        'user_id' => $borrowerUser->id,
        'member_code' => 'BOR-101',
        'first_name' => 'Borrower',
        'last_name' => 'One',
        'phone' => '12345678',
        'status' => 'active',
    ]);

    $loan = LoanRequest::create([
        'organization_id' => $org->id,
        'member_id' => $borrower->id,
        'amount' => 1000.00,
        'duration_months' => 6,
        'purpose' => 'Business inventory',
        'status' => 'pending_committee',
    ]);

    // Committee approves loan -> Should succeed
    $approveResponse = $this->actingAs($committeeUser)->post(route('loans.approve', $loan->id), [
        'notes' => 'Approved by committee',
        'interest_rate' => 5.0,
        'interest_type' => 'flat',
    ]);
    $approveResponse->assertRedirect();
    expect($loan->fresh()->status)->toBe('approved');

    // Committee attempts disbursement -> Must be blocked (403 Forbidden)!
    $disburseResponse = $this->actingAs($committeeUser)->post(route('loans.disburse', $loan->id), [
        'disbursement_method' => 'bank_transfer',
        'disbursement_destination' => 'Chase Bank checking ending in 1234',
        'disbursement_date' => now()->toDateString(),
        'disbursement_proof' => UploadedFile::fake()->create('proof.pdf', 100),
    ]);
    $disburseResponse->assertStatus(403);
});

test('user with Treasurer role can disburse loans and withdrawals but is forbidden from approving committee loans (403)', function () {
    Storage::fake('public');
    $org = Organization::create(['name' => 'Org 1']);

    $treasurerUser = User::factory()->create(['organization_id' => $org->id]);
    $treasurerUser->assignRole('Treasurer');

    $borrowerUser = User::factory()->create(['organization_id' => $org->id]);
    $borrower = Member::create([
        'organization_id' => $org->id,
        'user_id' => $borrowerUser->id,
        'member_code' => 'BOR-102',
        'first_name' => 'Borrower',
        'last_name' => 'Two',
        'phone' => '12345679',
        'status' => 'active',
    ]);

    $loan = LoanRequest::create([
        'organization_id' => $org->id,
        'member_id' => $borrower->id,
        'amount' => 1000.00,
        'duration_months' => 6,
        'purpose' => 'Business inventory',
        'status' => 'pending_committee',
    ]);

    // Treasurer attempts to approve loan -> Must be blocked (403 Forbidden)!
    $approveResponse = $this->actingAs($treasurerUser)->post(route('loans.approve', $loan->id), [
        'notes' => 'Treasurer self-approval',
        'interest_rate' => 5.0,
        'interest_type' => 'flat',
    ]);
    $approveResponse->assertStatus(403);

    // Transition loan to approved by an admin
    $loan->update(['status' => 'approved', 'interest_rate' => 5.0, 'interest_type' => 'flat']);

    // Treasurer disburses loan -> Should succeed!
    $disburseResponse = $this->actingAs($treasurerUser)->post(route('loans.disburse', $loan->id), [
        'disbursement_method' => 'bank_transfer',
        'disbursement_destination' => 'Bank of America checking ending in 9876',
        'disbursement_date' => now()->toDateString(),
        'disbursement_proof' => UploadedFile::fake()->create('receipt.pdf', 100),
    ]);
    $disburseResponse->assertRedirect();
    expect($loan->fresh()->status)->toBe('active');
    expect($loan->fresh()->disbursed_by_user_id)->toBe($treasurerUser->id);
});

test('user with Financial Secretary role can record deposits but cannot execute disbursements (403)', function () {
    Storage::fake('public');
    $org = Organization::create(['name' => 'Org 1']);

    $finSecUser = User::factory()->create(['organization_id' => $org->id]);
    $finSecUser->assignRole('Financial Secretary');

    $memberUser = User::factory()->create(['organization_id' => $org->id]);
    $member = Member::create([
        'organization_id' => $org->id,
        'user_id' => $memberUser->id,
        'member_code' => 'MEM-500',
        'first_name' => 'Savings',
        'last_name' => 'Member',
        'phone' => '999888777',
        'status' => 'active',
    ]);

    $withdrawal = SavingsWithdrawalRequest::create([
        'organization_id' => $org->id,
        'member_id' => $member->id,
        'gross_amount' => 500.00,
        'fee_rate' => 2.0,
        'fee_amount' => 10.0,
        'net_amount' => 490.00,
        'payout_method' => 'zelle',
        'payout_details' => 'test@zelle.com',
        'status' => 'approved',
    ]);

    // Financial Secretary attempts payout disbursement -> Must be blocked (403 Forbidden)!
    $response = $this->actingAs($finSecUser)->post(route('savings.withdrawal-requests.disburse', $withdrawal->id), [
        'disbursement_method' => 'zelle',
        'disbursement_destination' => 'test@zelle.com',
        'disbursement_date' => now()->toDateString(),
        'disbursement_proof' => UploadedFile::fake()->create('proof.png', 50),
    ]);
    $response->assertStatus(403);
});

test('regular member cannot access any admin or committee routes', function () {
    $memberUser = User::factory()->create();
    $memberUser->assignRole('member');

    $this->actingAs($memberUser)->get(route('admin.roles.index'))->assertStatus(403);
    $this->actingAs($memberUser)->get(route('attendance.desk'))->assertStatus(403);
});

test('unauthorized user cannot reject loans (403)', function () {
    $org = Organization::create(['name' => 'Org Reject']);
    $memberUser = User::factory()->create(['organization_id' => $org->id]);
    $memberUser->assignRole('member');

    $borrower = Member::create([
        'organization_id' => $org->id,
        'user_id' => $memberUser->id,
        'member_code' => 'BOR-999',
        'first_name' => 'Test',
        'last_name' => 'Borrower',
        'phone' => '111222333',
        'status' => 'active',
    ]);

    $loan = LoanRequest::create([
        'organization_id' => $org->id,
        'member_id' => $borrower->id,
        'amount' => 500.00,
        'duration_months' => 3,
        'purpose' => 'Test Loan',
        'status' => 'pending_committee',
    ]);

    $response = $this->actingAs($memberUser)->post(route('loans.reject', $loan->id), [
        'rejection_reason' => 'Unauthorized attempt',
    ]);
    $response->assertStatus(403);
});

test('unauthorized user cannot reject withdrawal requests (403)', function () {
    $org = Organization::create(['name' => 'Org Withdraw Reject']);
    $memberUser = User::factory()->create(['organization_id' => $org->id]);
    $memberUser->assignRole('member');

    $member = Member::create([
        'organization_id' => $org->id,
        'user_id' => $memberUser->id,
        'member_code' => 'MEM-888',
        'first_name' => 'Savings',
        'last_name' => 'Applicant',
        'phone' => '444555666',
        'status' => 'active',
    ]);

    $withdrawal = SavingsWithdrawalRequest::create([
        'organization_id' => $org->id,
        'member_id' => $member->id,
        'gross_amount' => 100.00,
        'fee_rate' => 2.0,
        'fee_amount' => 2.0,
        'net_amount' => 98.00,
        'payout_method' => 'zelle',
        'payout_details' => 'test@zelle.com',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($memberUser)->post(route('savings.withdrawal-requests.reject', $withdrawal->id), [
        'rejection_reason' => 'Unauthorized attempt',
    ]);
    $response->assertStatus(403);
});

test('admin can safely synchronize default roles and permissions without wiping custom permissions or user assignments', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Create a user with a customized role assignment
    $user = User::factory()->create();
    $user->assignRole('Treasurer');

    // Customize the Treasurer role (remove a permission to test preservation)
    $treasurer = Role::findByName('Treasurer');
    $treasurer->revokePermissionTo('loans.disburse');
    expect($treasurer->hasPermissionTo('loans.disburse'))->toBeFalse();

    // Trigger sync
    $response = $this->actingAs($admin)->post(route('admin.roles.sync-defaults'));
    $response->assertRedirect(route('admin.roles.index', ['tab' => 'matrix']));
    $response->assertSessionHas('success');

    // Verify user assignment was NOT lost
    expect($user->fresh()->hasRole('Treasurer'))->toBeTrue();

    // Verify custom permission revocation was NOT overwritten
    expect($treasurer->fresh()->hasPermissionTo('loans.disburse'))->toBeFalse();
});

test('syncing multiple times is completely idempotent and safe', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Run sync twice
    $this->actingAs($admin)->post(route('admin.roles.sync-defaults'))->assertRedirect();
    $secondResponse = $this->actingAs($admin)->post(route('admin.roles.sync-defaults'));

    $secondResponse->assertRedirect(route('admin.roles.index', ['tab' => 'matrix']));
    $secondResponse->assertSessionHas('success');
    expect(Role::where('name', 'admin')->exists())->toBeTrue();
    expect(Role::where('name', 'Treasurer')->exists())->toBeTrue();
});

test('unauthorized user cannot call sync-defaults route (403)', function () {
    $member = User::factory()->create();
    $member->assignRole('member');

    $response = $this->actingAs($member)->post(route('admin.roles.sync-defaults'));
    $response->assertStatus(403);
});

test('non-admin roles (Treasurer, FS, GS, Loan Committee, Member) receive 403 on settings, tools, and roles', function () {
    $rolesToTest = ['Treasurer', 'Financial Secretary', 'General Secretary', 'Loan Committee', 'member'];

    foreach ($rolesToTest as $roleName) {
        $user = User::factory()->create();
        $user->assignRole($roleName);

        // System Settings
        $this->actingAs($user)->get(route('settings.edit'))->assertStatus(403);
        $this->actingAs($user)->post(route('settings.update'), ['app_name' => 'Hacked'])->assertStatus(403);

        // System Tools
        $this->actingAs($user)->get(route('admin.tools'))->assertStatus(403);
        $this->actingAs($user)->post(route('admin.tools.migrate'))->assertStatus(403);
        $this->actingAs($user)->post(route('admin.tools.clear-cache'))->assertStatus(403);
        $this->actingAs($user)->post(route('admin.tools.storage-link'))->assertStatus(403);

        // Roles & Permissions Matrix
        $this->actingAs($user)->get(route('admin.roles.index'))->assertStatus(403);
    }
});

test('admin role has full access to settings, tools, and roles matrix', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get(route('settings.edit'))->assertStatus(200);
    $this->actingAs($admin)->get(route('admin.tools'))->assertStatus(200);
    $this->actingAs($admin)->get(route('admin.roles.index'))->assertStatus(200);
});

test('unauthorized access renders the friendly branded 403 error page', function () {
    $treasurer = User::factory()->create();
    $treasurer->assignRole('Treasurer');

    $response = $this->actingAs($treasurer)->get(route('settings.edit'));
    $response->assertStatus(403);
    $response->assertSee('Not Permitted');
    $response->assertSee('You do not have access to this feature.');
    $response->assertSee('Return to Dashboard');
});

test('non-admin user with settings.manage permission can access and update settings', function () {
    $secretary = User::factory()->create();
    $secretary->assignRole('General Secretary');
    $secretary->givePermissionTo('settings.manage');

    $response = $this->actingAs($secretary)->get(route('settings.edit'));
    $response->assertStatus(200);

    $update = $this->actingAs($secretary)->post(route('settings.update'), [
        'app_name' => 'NFUH DMV Updated',
        'beneficiary_count' => 4,
        'min_savings_for_loan' => 500,
        'loan_guarantor_min' => 1,
        'loan_guarantor_max' => 3,
        'default_loan_interest' => 1.0,
        'savings_withdrawal_fee_percent' => 2.0,
    ]);
    $update->assertRedirect();
});

test('non-admin user with tools.manage permission can access tools dashboard', function () {
    $treasurer = User::factory()->create();
    $treasurer->assignRole('Treasurer');
    $treasurer->givePermissionTo('tools.manage');

    $response = $this->actingAs($treasurer)->get(route('admin.tools'));
    $response->assertStatus(200);
});

test('non-admin user with roles.manage permission can access roles & duties matrix', function () {
    $secretary = User::factory()->create();
    $secretary->assignRole('General Secretary');
    $secretary->givePermissionTo('roles.manage');

    $response = $this->actingAs($secretary)->get(route('admin.roles.index'));
    $response->assertStatus(200);
});



