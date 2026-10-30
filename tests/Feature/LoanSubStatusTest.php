<?php

use App\Models\User;
use App\Models\Member;
use App\Models\Organization;
use App\Models\LoanRequest;
use App\Models\LoanSubStatus;
use App\Models\Setting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('admin can create, list, and delete loan sub-statuses', function () {
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Store custom sub-status
    $response = $this->actingAs($admin)
        ->post(route('admin.settings.store-sub-status'), [
            'name' => 'Grace Period Test',
            'color' => 'amber'
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('loan_sub_statuses', [
        'name' => 'Grace Period Test',
        'color' => 'amber'
    ]);

    $subStatus = LoanSubStatus::where('name', 'Grace Period Test')->first();

    // Verify it is passed to the sub-statuses index view
    $response = $this->actingAs($admin)
        ->get(route('loans.sub-statuses'));
    $response->assertStatus(200);
    $response->assertSee('Grace Period Test');

    // Delete custom sub-status
    $response = $this->actingAs($admin)
        ->delete(route('admin.settings.destroy-sub-status', $subStatus->id));

    $response->assertRedirect();
    $this->assertDatabaseMissing('loan_sub_statuses', [
        'id' => $subStatus->id
    ]);
});

test('non-admin user cannot manage sub-statuses', function () {
    $user = User::factory()->create();

    // Verify GET route is blocked for non-admin
    $response = $this->actingAs($user)
        ->get(route('loans.sub-statuses'));
    $response->assertStatus(403);

    $response = $this->actingAs($user)
        ->post(route('admin.settings.store-sub-status'), [
            'name' => 'Grace Period Attempt',
            'color' => 'amber'
        ]);

    $response->assertStatus(403);
});

test('admin can update a loan request sub-status and deleting the sub-status resets the loan relation to null', function () {
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $org = Organization::create(['name' => 'Test Org']);
    $borrower = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-BORR',
        'first_name' => 'Loan',
        'last_name' => 'Applicant',
        'email' => 'borrower@example.com',
        'phone' => '12345',
        'participates_in_savings' => true,
    ]);

    $loan = LoanRequest::create([
        'member_id' => $borrower->id,
        'organization_id' => $org->id,
        'amount' => 1000.00,
        'duration_months' => 12,
        'status' => 'active'
    ]);

    $subStatus = LoanSubStatus::create([
        'name' => 'Legal Action',
        'color' => 'red'
    ]);

    // Update sub status
    $response = $this->actingAs($admin)
        ->post(route('loans.update-sub-status', $loan->id), [
            'sub_status_id' => $subStatus->id
        ]);

    $response->assertRedirect();
    expect($loan->fresh()->sub_status_id)->toEqual($subStatus->id);

    // Assert deleting custom sub-status sets the loan request's sub_status_id to null
    $subStatus->delete();
    expect($loan->fresh()->sub_status_id)->toBeNull();
});

test('admin can edit a custom sub-status', function () {
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $subStatus = LoanSubStatus::create([
        'name' => 'Original Name',
        'color' => 'blue'
    ]);

    // Edit sub-status definition
    $response = $this->actingAs($admin)
        ->patch(route('admin.settings.update-sub-status', $subStatus->id), [
            'name' => 'Updated Name',
            'color' => 'emerald'
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('loan_sub_statuses', [
        'id' => $subStatus->id,
        'name' => 'Updated Name',
        'color' => 'emerald'
    ]);
});

test('admin can transition a loan status to defaulted and back to active', function () {
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $org = Organization::create(['name' => 'Test Org']);
    $borrower = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-BORR-2',
        'first_name' => 'John',
        'last_name' => 'Default',
        'email' => 'default@example.com',
        'phone' => '123456',
        'participates_in_savings' => true,
    ]);

    $loan = LoanRequest::create([
        'member_id' => $borrower->id,
        'organization_id' => $org->id,
        'amount' => 1000.00,
        'duration_months' => 12,
        'status' => 'active'
    ]);

    // Mark as defaulted
    $response = $this->actingAs($admin)
        ->post(route('loans.mark-defaulted', $loan->id));
    
    $response->assertRedirect();
    expect($loan->fresh()->status)->toEqual('defaulted');

    // Restore to active
    $response = $this->actingAs($admin)
        ->post(route('loans.mark-active', $loan->id));
    
    $response->assertRedirect();
    expect($loan->fresh()->status)->toEqual('active');
});

test('treasurer sees sub-statuses in read-only mode and cannot submit new or modified sub-statuses', function () {
    (new RolesAndPermissionsSeeder())->run();

    $treasurerUser = User::factory()->create();
    $treasurerUser->assignRole('Treasurer');

    $subStatus = LoanSubStatus::create([
        'name' => 'Restructured',
        'color' => 'blue',
    ]);

    $response = $this->actingAs($treasurerUser)->get(route('loans.sub-statuses'));
    $response->assertStatus(200);
    $response->assertSee('Restructured');
    $response->assertSee('Read-only');
    $response->assertDontSee('Add Sub-Status');
    $response->assertDontSee('title="Edit Sub-Status"', false);
    $response->assertDontSee('title="Delete Sub-Status"', false);

    // Server-side check blocks creation
    $storeResponse = $this->actingAs($treasurerUser)->post(route('admin.settings.store-sub-status'), [
        'name' => 'Unauthorized Status',
        'color' => 'red',
    ]);
    $storeResponse->assertStatus(403);

    // Server-side check blocks update
    $updateResponse = $this->actingAs($treasurerUser)->patch(route('admin.settings.update-sub-status', $subStatus->id), [
        'name' => 'Hacked Name',
        'color' => 'red',
    ]);
    $updateResponse->assertStatus(403);

    // Server-side check blocks destroy
    $destroyResponse = $this->actingAs($treasurerUser)->delete(route('admin.settings.destroy-sub-status', $subStatus->id));
    $destroyResponse->assertStatus(403);
});

test('user with loans.manage_sub_statuses can view controls and create sub-status', function () {
    (new RolesAndPermissionsSeeder())->run();

    $committeeUser = User::factory()->create();
    $committeeUser->assignRole('Loan Committee');

    $response = $this->actingAs($committeeUser)->get(route('loans.sub-statuses'));
    $response->assertStatus(200);
    $response->assertSee('Add Sub-Status');

    $storeResponse = $this->actingAs($committeeUser)->post(route('admin.settings.store-sub-status'), [
        'name' => 'Under Legal Review',
        'color' => 'indigo',
    ]);
    $storeResponse->assertRedirect();
    $this->assertDatabaseHas('loan_sub_statuses', [
        'name' => 'Under Legal Review',
    ]);
});

test('attendance desk link visibility is governed strictly by attendance.mark permission', function () {
    (new RolesAndPermissionsSeeder())->run();

    $treasurerUser = User::factory()->create();
    $treasurerUser->assignRole('Treasurer');

    // Treasurer does not see Attendance Desk link in sidebar
    $treasurerResponse = $this->actingAs($treasurerUser)->get(route('loans.index'));
    $treasurerResponse->assertStatus(200);
    $treasurerResponse->assertDontSee('Attendance Desk');
    $treasurerResponse->assertDontSee(route('attendance.desk'));

    // Accessing desk directly returns 403
    $this->actingAs($treasurerUser)->get(route('attendance.desk'))->assertStatus(403);

    // User with attendance.mark permission (e.g. General Secretary) sees the link and can access desk
    $secretaryUser = User::factory()->create();
    $secretaryUser->assignRole('General Secretary');

    $secretaryResponse = $this->actingAs($secretaryUser)->get(route('loans.index'));
    $secretaryResponse->assertStatus(200);
    $secretaryResponse->assertSee('Attendance Desk');

    $this->actingAs($secretaryUser)->get(route('attendance.desk'))->assertStatus(200);
});

