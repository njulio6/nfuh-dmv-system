<?php

use App\Models\User;
use App\Models\Member;
use App\Models\Organization;
use App\Models\SavingsTransaction;
use App\Models\SavingsDepositRequest;
use App\Models\Setting;

test('admin can view savings admin page and post transaction', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $user = User::factory()->create();
    $user->assignRole('admin');
    $org = Organization::create(['name' => 'Test Org']);

    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-SAV',
        'first_name' => 'Sav',
        'last_name' => 'User',
        'email' => 'sav@user.com',
        'phone' => '12345',
        'participates_in_savings' => true,
    ]);

    $response = $this->actingAs($user)
        ->get(route('savings.index'));
    $response->assertOk();

    // Post savings deposit
    $response = $this->actingAs($user)
        ->post(route('savings.store'), [
            'member_id' => $member->id,
            'amount' => 600.00,
            'type' => 'deposit',
            'transaction_date' => now()->toDateString(),
            'notes' => 'First deposit',
        ]);
    $response->assertRedirect(route('savings.index'));

    expect((float)$member->fresh()->savings_balance)->toEqual(600.00);
});

test('auto-enrolls member in savings if transaction is logged', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $user = User::factory()->create();
    $user->assignRole('admin');
    $org = Organization::create(['name' => 'Test Org']);

    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-SAV2',
        'first_name' => 'Sav2',
        'last_name' => 'User2',
        'email' => 'sav2@user.com',
        'phone' => '123452',
        'participates_in_savings' => false,
    ]);

    $response = $this->actingAs($user)
        ->post(route('savings.store'), [
            'member_id' => $member->id,
            'amount' => 100.00,
            'type' => 'deposit',
            'transaction_date' => now()->toDateString(),
            'notes' => 'Test auto-enroll',
        ]);
    $response->assertRedirect(route('savings.index'));

    expect($member->fresh()->participates_in_savings)->toBeTrue();
    expect((float)$member->fresh()->savings_balance)->toEqual(100.00);
});

test('member can view their own savings statements', function () {
    $org = Organization::create(['name' => 'Test Org']);
    
    $memberUser = User::create([
        'name' => 'Member User',
        'email' => 'member@example.com',
        'password' => bcrypt('password'),
    ]);

    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-SAV3',
        'first_name' => 'Sav3',
        'last_name' => 'User3',
        'email' => 'member@example.com',
        'phone' => '99999',
        'participates_in_savings' => true,
    ]);

    // Create a transaction
    SavingsTransaction::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 550.00,
        'type' => 'deposit',
        'transaction_date' => now()->toDateString(),
        'notes' => 'Manual credit',
    ]);

    $response = $this->actingAs($memberUser)
        ->get(route('member.savings'));
    $response->assertOk();
    $response->assertSee('$550.00');
    $response->assertSee('Loan Eligible');
});

test('admin can update min_savings_for_loan and is reflected in member settings', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $user = User::factory()->create();
    $user->assignRole('admin');

    $settings = Setting::firstOrCreate([], [
        'app_name' => 'NFUH DMV',
        'beneficiary_count' => 4,
        'single_benefit_constraint' => true,
        'min_savings_for_loan' => 500.00,
    ]);

    // Update settings
    $response = $this->actingAs($user)
        ->post(route('settings.update'), [
            'app_name' => 'Updated DMV Name',
            'beneficiary_count' => 3,
            'single_benefit_constraint' => 1,
            'min_savings_for_loan' => 750.00,
        ]);
    $response->assertRedirect(route('settings.edit'));

    expect((float)Setting::first()->min_savings_for_loan)->toEqual(750.00);

    // Verify member view displays dynamic limit
    $org = Organization::create(['name' => 'Test Org']);
    $memberUser = User::create([
        'name' => 'Member User',
        'email' => 'member@example.com',
        'password' => bcrypt('password'),
    ]);

    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-SAV4',
        'first_name' => 'Sav4',
        'last_name' => 'User4',
        'email' => 'member@example.com',
        'phone' => '99999',
        'participates_in_savings' => true,
    ]);

    // Give member 600.00 savings balance
    SavingsTransaction::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 600.00,
        'type' => 'deposit',
        'transaction_date' => now()->toDateString(),
        'notes' => 'Deposit',
    ]);

    // Since the limit is 750.00, 600.00 should now show as Under Limit ($750)
    $response = $this->actingAs($memberUser)
        ->get(route('member.savings'));
    $response->assertOk();
    $response->assertSee('Under Limit ($750)');
});

test('admin can view savings transactions page and filter by type', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $user = User::factory()->create();
    $user->assignRole('admin');

    $org = Organization::create(['name' => 'Test Org']);
    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-SAV5',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@doe.com',
        'phone' => '112233',
        'participates_in_savings' => true,
    ]);

    // Create deposit
    SavingsTransaction::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 300.00,
        'type' => 'deposit',
        'transaction_date' => now()->toDateString(),
        'notes' => 'Member deposit',
    ]);

    // Create withdrawal
    SavingsTransaction::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 50.00,
        'type' => 'withdrawal',
        'transaction_date' => now()->toDateString(),
        'notes' => 'Member withdrawal',
    ]);

    // View all transactions
    $response = $this->actingAs($user)
        ->get(route('savings.transactions'));
    $response->assertOk();
    $response->assertSee('Member deposit');
    $response->assertSee('Member withdrawal');

    // Filter by deposit
    $response = $this->actingAs($user)
        ->get(route('savings.transactions', ['type' => 'deposit']));
    $response->assertOk();
    $response->assertSee('Member deposit');
    $response->assertDontSee('Member withdrawal');
});

test('member can submit a savings deposit request with a screenshot proof', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    $org = Organization::create(['name' => 'Test Org']);
    $memberUser = User::create([
        'name' => 'Member User',
        'email' => 'member@example.com',
        'password' => bcrypt('password'),
    ]);

    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-REQ1',
        'first_name' => 'Req',
        'last_name' => 'User',
        'email' => 'member@example.com',
        'phone' => '12345',
        'participates_in_savings' => true,
    ]);

    $file = \Illuminate\Http\UploadedFile::fake()->image('receipt.png');

    $response = $this->actingAs($memberUser)
        ->post(route('member.savings.request'), [
            'amount' => 250.00,
            'transaction_date' => now()->toDateString(),
            'screenshot' => $file,
            'notes' => 'Submitted Zelle payment receipt',
        ]);

    $response->assertRedirect(route('member.savings.requests'));

    // Check request exists in DB
    $depositRequest = SavingsDepositRequest::firstWhere('member_id', $member->id);
    expect($depositRequest)->not->toBeNull();
    expect($depositRequest->amount)->toEqual(250.00);
    expect($depositRequest->status)->toEqual('pending');
    expect($depositRequest->notes)->toEqual('Submitted Zelle payment receipt');
    expect($depositRequest->screenshot_path)->not->toBeNull();

    // Verify storage
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($depositRequest->screenshot_path);

    // Verify savings balance is still 0 (pending requests do not count toward active balance)
    expect((float)$member->fresh()->savings_balance)->toEqual(0.00);
});

test('admin can approve a pending savings deposit request', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');

    $org = Organization::create(['name' => 'Test Org']);
    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-REQ2',
        'first_name' => 'Req2',
        'last_name' => 'User2',
        'email' => 'req2@example.com',
        'phone' => '12345',
        'participates_in_savings' => true,
    ]);

    $depositRequest = SavingsDepositRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 300.00,
        'status' => 'pending',
        'screenshot_path' => 'savings_proofs/receipt.png',
        'notes' => 'Zelle proof',
        'submitted_at' => now(),
    ]);

    // Savings balance should be 0 before approval
    expect((float)$member->fresh()->savings_balance)->toEqual(0.00);

    $response = $this->actingAs($adminUser)
        ->post(route('savings.approve', $depositRequest), [
            'review_note' => 'Looks good, approved.',
        ]);

    $response->assertRedirect(route('savings.requests'));

    $depositRequest->refresh();
    expect($depositRequest->status)->toEqual('approved');
    expect($depositRequest->reviewed_by)->toEqual($adminUser->id);
    expect($depositRequest->reviewed_at)->not->toBeNull();
    expect($depositRequest->review_note)->toEqual('Looks good, approved.');

    // Savings balance should now include the 300.00
    expect((float)$member->fresh()->savings_balance)->toEqual(300.00);
});

test('admin can reject a pending savings deposit request with a reason', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');

    $org = Organization::create(['name' => 'Test Org']);
    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-REQ3',
        'first_name' => 'Req3',
        'last_name' => 'User3',
        'email' => 'req3@example.com',
        'phone' => '12345',
        'participates_in_savings' => true,
    ]);

    $depositRequest = SavingsDepositRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 450.00,
        'status' => 'pending',
        'screenshot_path' => 'savings_proofs/receipt.png',
        'notes' => 'Zelle proof',
        'submitted_at' => now(),
    ]);

    // Rejecting without a review note should fail validation
    $response = $this->actingAs($adminUser)
        ->post(route('savings.reject', $depositRequest), []);
    $response->assertSessionHasErrors(['review_note']);

    // Rejecting with reason
    $response = $this->actingAs($adminUser)
        ->post(route('savings.reject', $depositRequest), [
            'review_note' => 'Screenshot is blurry. Please upload a clear receipt.',
        ]);

    $response->assertRedirect(route('savings.requests'));

    $depositRequest->refresh();
    expect($depositRequest->status)->toEqual('rejected');
    expect($depositRequest->reviewed_by)->toEqual($adminUser->id);
    expect($depositRequest->reviewed_at)->not->toBeNull();
    expect($depositRequest->review_note)->toEqual('Screenshot is blurry. Please upload a clear receipt.');

    // Savings balance should remain 0
    expect((float)$member->fresh()->savings_balance)->toEqual(0.00);
});

test('member can view dedicated savings deposit requests page', function () {
    $org = Organization::create(['name' => 'Test Org']);
    $memberUser = User::create([
        'name' => 'Member User',
        'email' => 'member@example.com',
        'password' => bcrypt('password'),
    ]);

    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-REQ4',
        'first_name' => 'Req4',
        'last_name' => 'User4',
        'email' => 'member@example.com',
        'phone' => '12345',
        'participates_in_savings' => true,
    ]);

    SavingsDepositRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 120.00,
        'status' => 'pending',
        'screenshot_path' => 'savings_proofs/receipt.png',
        'notes' => 'Zelle proof requested',
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($memberUser)
        ->get(route('member.savings.requests'));

    $response->assertOk();
    $response->assertSee('Zelle proof requested');
    $response->assertSee('$120.00');
});

test('admin can view dedicated savings deposit requests queue page', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');

    $org = Organization::create(['name' => 'Test Org']);
    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-REQ5',
        'first_name' => 'Req5',
        'last_name' => 'User5',
        'email' => 'req5@example.com',
        'phone' => '12345',
        'participates_in_savings' => true,
    ]);

    SavingsDepositRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 500.00,
        'status' => 'pending',
        'screenshot_path' => 'savings_proofs/receipt.png',
        'notes' => 'Awaiting confirmation',
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($adminUser)
        ->get(route('savings.requests'));

    $response->assertOk();
    $response->assertSee('Awaiting confirmation');
    $response->assertSee('$500.00');
});

test('admin can filter savings deposit requests queue page by member', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');

    $org = Organization::create(['name' => 'Test Org']);
    
    // Create member 1
    $member1 = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-REQ6',
        'first_name' => 'Req6',
        'last_name' => 'User6',
        'email' => 'req6@example.com',
        'phone' => '12345',
        'participates_in_savings' => true,
    ]);

    // Create member 2
    $member2 = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-REQ7',
        'first_name' => 'Req7',
        'last_name' => 'User7',
        'email' => 'req7@example.com',
        'phone' => '12346',
        'participates_in_savings' => true,
    ]);

    // Deposit request for member 1
    SavingsDepositRequest::create([
        'member_id' => $member1->id,
        'organization_id' => $org->id,
        'amount' => 500.00,
        'status' => 'pending',
        'screenshot_path' => 'savings_proofs/receipt1.png',
        'notes' => 'Member One Request',
        'submitted_at' => now(),
    ]);

    // Deposit request for member 2
    SavingsDepositRequest::create([
        'member_id' => $member2->id,
        'organization_id' => $org->id,
        'amount' => 600.00,
        'status' => 'pending',
        'screenshot_path' => 'savings_proofs/receipt2.png',
        'notes' => 'Member Two Request',
        'submitted_at' => now(),
    ]);

    // Get filtered by member 1
    $response = $this->actingAs($adminUser)
        ->get(route('savings.requests', ['member_id' => $member1->id]));

    $response->assertOk();
    $response->assertSee('Member One Request');
    $response->assertSee('$500.00');
    $response->assertDontSee('Member Two Request');
    $response->assertDontSee('$600.00');

    // Get filtered by member 2
    $response2 = $this->actingAs($adminUser)
        ->get(route('savings.requests', ['member_id' => $member2->id]));

    $response2->assertOk();
    $response2->assertSee('Member Two Request');
    $response2->assertSee('$600.00');
    $response2->assertDontSee('Member One Request');
    $response2->assertDontSee('$500.00');
});

test('direct admin deposit transactions are not listed in deposit requests queue', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');

    $org = Organization::create(['name' => 'Test Org']);
    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-DIR1',
        'first_name' => 'Direct',
        'last_name' => 'User',
        'email' => 'direct@example.com',
        'phone' => '12345',
        'participates_in_savings' => true,
    ]);

    // Admin posts transaction directly (no request submission step)
    $this->actingAs($adminUser)
        ->post(route('savings.store'), [
            'member_id' => $member->id,
            'amount' => 350.00,
            'type' => 'deposit',
            'transaction_date' => now()->toDateString(),
            'notes' => 'Directly posted by admin',
        ]);

    // Now request queue should be empty (not contain this transaction)
    $response = $this->actingAs($adminUser)
        ->get(route('savings.requests'));

    $response->assertOk();
    $response->assertDontSee('Directly posted by admin');
    $response->assertDontSee('$350.00');

    // And member requests page should be empty as well
    $memberUser = User::create([
        'name' => 'Direct User',
        'email' => 'direct@example.com',
        'password' => bcrypt('password'),
    ]);
    
    $response2 = $this->actingAs($memberUser)
        ->get(route('member.savings.requests'));

    $response2->assertOk();
    $response2->assertDontSee('Directly posted by admin');
    $response2->assertDontSee('$350.00');
});
