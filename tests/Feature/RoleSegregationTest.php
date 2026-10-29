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

function createTestMember(?User $user = null, array $attributes = []): Member
{
    $org = Organization::firstOrCreate(
        ['name' => 'NFUH DMV'],
        ['code' => 'NFUH-DMV', 'status' => 'active']
    );

    return Member::create(array_merge([
        'organization_id' => $org->id,
        'user_id' => $user?->id,
        'member_code' => 'MEM' . rand(10000, 99999),
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => $user?->email ?? ('test' . rand(1000, 9999) . '@example.com'),
        'phone' => '240555' . rand(1000, 9999),
        'status' => 'active',
        'participates_in_savings' => true,
        'participates_in_njangi' => true,
    ], $attributes));
}

test('officer cannot approve their own loan application', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $member = createTestMember($admin);

    $loan = LoanRequest::create([
        'organization_id' => $member->organization_id,
        'member_id' => $member->id,
        'amount' => 1000,
        'duration_months' => 6,
        'purpose' => 'Business inventory',
        'status' => 'pending_committee',
    ]);

    $response = $this->actingAs($admin)->post(route('loans.approve', $loan), [
        'approved_amount' => 500,
        'duration_months' => 6,
        'interest_rate' => 1.0,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('Segregation of Duties Violation');
    expect($loan->fresh()->status)->toBe('pending_committee');
});

test('officer cannot approve their own savings deposit request', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $member = createTestMember($admin, ['participates_in_savings' => true]);

    $depositRequest = \App\Models\SavingsDepositRequest::create([
        'member_id' => $member->id,
        'organization_id' => $member->organization_id,
        'amount' => 100,
        'payment_method' => 'zelle',
        'screenshot_path' => 'receipts/test.png',
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($admin)->post(route('savings.approve', $depositRequest));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('Segregation of Duties Violation');
    expect($depositRequest->fresh()->status)->toBe('pending');
});

test('officer cannot approve or disburse their own savings withdrawal request', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $member = createTestMember($admin, ['participates_in_savings' => true]);

    // Give member initial savings balance
    SavingsTransaction::create([
        'member_id' => $member->id,
        'organization_id' => $member->organization_id,
        'amount' => 1000,
        'previous_balance' => 0,
        'new_balance' => 1000,
        'type' => 'deposit',
        'status' => 'approved',
        'transaction_date' => now()->toDateString(),
    ]);

    $withdrawalRequest = SavingsWithdrawalRequest::create([
        'member_id' => $member->id,
        'organization_id' => $member->organization_id,
        'gross_amount' => 200,
        'fee_amount' => 2,
        'fee_rate' => 1,
        'net_amount' => 198,
        'payout_method' => 'zelle',
        'payout_details' => 'zelle: test@example.com',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->post(route('savings.withdrawal-requests.approve', $withdrawalRequest));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('Segregation of Duties Violation');
    expect($withdrawalRequest->fresh()->status)->toBe('pending');
});

test('officer cannot approve their own njangi payment submission', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $member = createTestMember($admin);

    $cycle = \App\Models\NjangiCycle::create([
        'organization_id' => $member->organization_id,
        'name' => '2026 Test Cycle',
        'year' => 2026,
        'status' => 'active',
    ]);

    $session = \App\Models\NjangiSession::create([
        'organization_id' => $member->organization_id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 1,
        'title' => 'Session 1',
        'session_date' => now()->toDateString(),
        'status' => 'open',
    ]);

    $submission = \App\Models\NjangiPaymentSubmission::create([
        'njangi_cycle_id' => $cycle->id,
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'organization_id' => $member->organization_id,
        'amount' => 300,
        'status' => 'pending',
        'payment_method' => 'zelle',
        'screenshot_path' => 'receipts/njangi.png',
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($admin)->post(route('njangi-submissions.approve', $submission));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(session('error'))->toContain('Segregation of Duties Violation');
    expect($submission->fresh()->status)->toBe('pending');
});

test('savings store requires notes on adjustment and records audit balance snapshots', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $member = createTestMember(null, ['participates_in_savings' => true]);

    // Initial deposit
    $init = $this->actingAs($admin)->post(route('savings.store'), [
        'member_id' => $member->id,
        'amount' => 500,
        'type' => 'deposit',
        'transaction_date' => now()->toDateString(),
        'notes' => 'Initial deposit',
    ]);
    $init->assertRedirect(route('savings.index'));

    $firstTx = SavingsTransaction::where('member_id', $member->id)->first();
    expect((float)$firstTx->previous_balance)->toBe(0.0);
    expect((float)$firstTx->new_balance)->toBe(500.0);
    expect($firstTx->reviewed_by)->toBe($admin->id);

    // Adjustment without notes should fail
    $invalidAdjustment = $this->actingAs($admin)->post(route('savings.store'), [
        'member_id' => $member->id,
        'amount' => 50,
        'type' => 'adjustment',
        'transaction_date' => now()->toDateString(),
        'notes' => '',
    ]);
    $invalidAdjustment->assertSessionHasErrors('notes');

    // Adjustment with notes should succeed
    $validAdjustment = $this->actingAs($admin)->post(route('savings.store'), [
        'member_id' => $member->id,
        'amount' => 50,
        'type' => 'adjustment',
        'transaction_date' => now()->toDateString(),
        'notes' => 'Correcting rounding error from previous cycle',
    ]);
    $validAdjustment->assertRedirect(route('savings.index'));

    $secondTx = SavingsTransaction::where('member_id', $member->id)->where('type', 'adjustment')->first();
    expect((float)$secondTx->previous_balance)->toBe(500.0);
    expect((float)$secondTx->new_balance)->toBe(550.0);
    expect($secondTx->reviewed_by)->toBe($admin->id);

    // Manual withdrawal queues for dual approval under SavingsWithdrawalRequest
    $withdrawalRes = $this->actingAs($admin)->post(route('savings.store'), [
        'member_id' => $member->id,
        'amount' => 100,
        'type' => 'withdrawal',
        'transaction_date' => now()->toDateString(),
        'notes' => 'Manual withdrawal for member',
    ]);
    $withdrawalRes->assertRedirect(route('savings.withdrawal-requests'));
    $queuedRequest = \App\Models\SavingsWithdrawalRequest::where('member_id', $member->id)->first();
    expect($queuedRequest)->not->toBeNull();
    expect($queuedRequest->status)->toBe('pending');
    expect((float)$queuedRequest->gross_amount)->toBe(100.0);
});

test('dual-role user can switch portal view mode between officer and member', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $member = createTestMember($admin);

    // Switch to member mode
    $switchResponse = $this->actingAs($admin)->get(route('portal.switch-mode', 'member'));
    $switchResponse->assertRedirect(route('dashboard'));
    expect(session('portal_mode'))->toBe('member');

    // Access dashboard in member mode
    $dashboardResponse = $this->actingAs($admin)->get(route('dashboard'));
    $dashboardResponse->assertStatus(200);

    // Switch back to officer mode
    $switchBack = $this->actingAs($admin)->get(route('portal.switch-mode', 'officer'));
    $switchBack->assertRedirect(route('dashboard'));
    expect(session('portal_mode'))->toBe('officer');
});

test('member cannot apply for loans if loans.apply permission is revoked', function () {
    $user = User::factory()->create();
    $user->assignRole('member');

    $member = createTestMember($user, ['participates_in_savings' => true]);

    // Give savings balance so threshold is met
    SavingsTransaction::create([
        'member_id' => $member->id,
        'organization_id' => $member->organization_id,
        'amount' => 1000,
        'previous_balance' => 0,
        'new_balance' => 1000,
        'type' => 'deposit',
        'status' => 'approved',
        'transaction_date' => now()->toDateString(),
    ]);

    // Ensure member role initially has loans.apply
    $memberRole = Role::findByName('member');
    expect($user->can('loans.apply'))->toBeTrue();

    // Revoke loans.apply from member role
    $memberRole->revokePermissionTo('loans.apply');
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    expect($user->fresh()->can('loans.apply'))->toBeFalse();

    // Try applying for loan -> should be 403 Forbidden
    $response = $this->actingAs($user)->post(route('member.loans.request'), [
        'amount' => 500,
        'duration_months' => 6,
        'purpose' => 'Test business loan',
        'repayment_frequency' => 'monthly',
    ]);
    $response->assertStatus(403);

    // Now re-grant loans.apply permission
    $memberRole->givePermissionTo('loans.apply');
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    expect($user->fresh()->can('loans.apply'))->toBeTrue();

    // Create a guarantor member
    $guarantor = createTestMember(null, ['participates_in_savings' => true]);

    $successResponse = $this->actingAs($user)->post(route('member.loans.request'), [
        'amount' => 500,
        'duration_months' => 6,
        'purpose' => 'Test business loan',
        'repayment_frequency' => 'monthly',
        'payout_method' => 'zelle',
        'payout_details' => 'zelle: 2405551234',
        'guarantors' => [$guarantor->id],
    ]);
    $successResponse->assertRedirect();
    expect(LoanRequest::where('member_id', $member->id)->exists())->toBeTrue();
});

test('member cannot submit savings deposit if savings.deposit_request is revoked', function () {
    $user = User::factory()->create();
    $user->assignRole('member');
    $member = createTestMember($user);

    $memberRole = Role::findByName('member');
    $memberRole->revokePermissionTo('savings.deposit_request');
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    Storage::fake('public');
    $file = UploadedFile::fake()->image('receipt.jpg');

    $response = $this->actingAs($user)->post(route('member.savings.request'), [
        'amount' => 200,
        'payment_method' => 'zelle',
        'receipt' => $file,
        'notes' => 'Monthly deposit',
    ]);
    $response->assertStatus(403);
});

test('member cannot submit savings withdrawal if savings.withdraw_request is revoked', function () {
    $user = User::factory()->create();
    $user->assignRole('member');
    $member = createTestMember($user);

    // Initial deposit
    SavingsTransaction::create([
        'member_id' => $member->id,
        'organization_id' => $member->organization_id,
        'amount' => 1000,
        'previous_balance' => 0,
        'new_balance' => 1000,
        'type' => 'deposit',
        'status' => 'approved',
        'transaction_date' => now()->toDateString(),
    ]);

    $memberRole = Role::findByName('member');
    $memberRole->revokePermissionTo('savings.withdraw_request');
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $response = $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => 100,
        'payout_method' => 'zelle',
        'zelle_name' => 'John Doe',
        'zelle_contact' => 'john@example.com',
    ]);
    $response->assertStatus(403);
});

test('member cannot submit njangi payment if njangi.submit_payment is revoked', function () {
    $user = User::factory()->create();
    $user->assignRole('member');
    $member = createTestMember($user);

    $cycle = \App\Models\NjangiCycle::create([
        'organization_id' => $member->organization_id,
        'name' => 'Cycle 2026',
        'year' => 2026,
        'status' => 'active',
    ]);

    $session = \App\Models\NjangiSession::create([
        'organization_id' => $member->organization_id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 1,
        'title' => 'Session 1',
        'session_date' => now()->toDateString(),
        'status' => 'open',
    ]);

    $memberRole = Role::findByName('member');
    $memberRole->revokePermissionTo('njangi.submit_payment');
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    Storage::fake('public');
    $file = UploadedFile::fake()->image('payment.jpg');

    $response = $this->actingAs($user)->post(route('member.submissions.store'), [
        'njangi_session_id' => $session->id,
        'amount' => 100,
        'is_attending' => 1,
        'screenshot' => $file,
    ]);
    $response->assertStatus(403);
});

test('user without member role has full general member portal access by default', function () {
    $user = User::factory()->create();
    // Do NOT assign any roles!
    expect($user->roles()->count())->toBe(0);

    // General self-service permissions should all be granted automatically
    expect($user->can('loans.apply'))->toBeTrue();
    expect($user->can('loans.view_own'))->toBeTrue();
    expect($user->can('savings.deposit_request'))->toBeTrue();
    expect($user->can('savings.withdraw_request'))->toBeTrue();
    expect($user->can('savings.view_own'))->toBeTrue();
    expect($user->can('njangi.submit_payment'))->toBeTrue();
    expect($user->can('njangi.view_own'))->toBeTrue();
    expect($user->can('attendance.rsvp'))->toBeTrue();
    expect($user->can('attendance.view_own'))->toBeTrue();

    // Administrative permissions should NOT be granted
    expect($user->can('roles.manage'))->toBeFalse();
    expect($user->can('settings.manage'))->toBeFalse();
    expect($user->can('tools.manage'))->toBeFalse();
    expect($user->can('members.manage'))->toBeFalse();
    expect($user->can('loans.approve'))->toBeFalse();
    expect($user->can('loans.disburse'))->toBeFalse();
    expect($user->can('savings.record_deposit'))->toBeFalse();
    expect($user->can('attendance.clear_records'))->toBeFalse();
});

test('assigning member role allows admin to enforce specific restrictions on that user', function () {
    $user = User::factory()->create();
    $member = createTestMember($user);

    // Without member role, user can apply for loans
    expect($user->can('loans.apply'))->toBeTrue();

    // Admin explicitly assigns 'member' role to apply restrictions
    $user->assignRole('member');
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Remove loans.apply from member role
    $memberRole = Role::findByName('member');
    $memberRole->revokePermissionTo('loans.apply');
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Now the user with 'member' role is restricted from applying for loans!
    expect($user->fresh()->can('loans.apply'))->toBeFalse();
    expect($user->fresh()->can('savings.deposit_request'))->toBeTrue(); // other permissions still work
});

test('member role matrix tab only renders realistic member permissions and hides administrative permissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.roles.index', ['tab' => 'matrix', 'role' => 'member']));
    $response->assertStatus(200);

    // Realistic self-service permissions are present
    $response->assertSee('Apply for Loans');
    $response->assertSee('Deposit Request');
    $response->assertSee('Submit Payment');
    $response->assertSee('Submit RSVP');

    // Member policy banner is displayed
    $response->assertSee('Member Role Access', false);
    $response->assertSee('all general members have full self-service portal access');
});
