<?php

use App\Models\User;
use App\Models\Member;
use App\Models\Organization;
use App\Models\SavingsTransaction;
use App\Models\SavingsWithdrawalRequest;
use App\Models\Setting;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'member']);
});

function createMemberWithUser(float $savingsBalance = 1000.00): array {
    $org = Organization::firstOrCreate(['name' => 'Test Org']);
    $user = User::factory()->create();
    $user->assignRole('member');

    $member = Member::create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'member_code' => 'M-' . rand(1000, 9999),
        'first_name' => 'Test',
        'last_name' => 'Member',
        'phone' => '123456789',
        'participates_in_savings' => true,
    ]);

    if ($savingsBalance > 0) {
        SavingsTransaction::create([
            'member_id' => $member->id,
            'organization_id' => $org->id,
            'amount' => $savingsBalance,
            'type' => 'deposit',
            'status' => 'approved',
            'transaction_date' => now()->toDateString(),
            'notes' => 'Initial balance seed',
        ]);
    }

    return [$user, $member, $org];
}

function createAdminUser(): User {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    return $admin;
}

test('savings withdrawal fee percent is loaded dynamically from settings, not hardcoded', function () {
    [$user, $member] = createMemberWithUser(1000.00);

    // Set fee in settings to 2.00%
    if (Setting::count() === 0) {
        Setting::create(['savings_withdrawal_fee_percent' => 2.00]);
    } else {
        Setting::query()->update(['savings_withdrawal_fee_percent' => 2.00]);
    }

    $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => 100.00,
        'payout_method' => 'zelle',
        'payout_details' => 'zelle@member.com',
    ]);

    $req1 = SavingsWithdrawalRequest::where('member_id', $member->id)->latest()->first();
    expect((float)$req1->fee_rate)->toEqual(2.00)
        ->and((float)$req1->fee_amount)->toEqual(2.00)
        ->and((float)$req1->net_amount)->toEqual(98.00);

    // Update dynamic setting to 3.50%
    Setting::query()->update(['savings_withdrawal_fee_percent' => 3.50]);

    $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => 200.00,
        'payout_method' => 'bank_transfer',
        'payout_details' => 'Chase Bank Acct 123456',
    ]);

    $req2 = SavingsWithdrawalRequest::where('member_id', $member->id)->latest('id')->first();
    expect((float)$req2->fee_rate)->toEqual(3.50)
        ->and((float)$req2->fee_amount)->toEqual(7.00)
        ->and((float)$req2->net_amount)->toEqual(193.00);
});

test('member can submit valid savings withdrawal request with gross, fee, and net calculation', function () {
    [$user, $member] = createMemberWithUser(1000.00);
    Setting::updateOrCreate([], ['savings_withdrawal_fee_percent' => 2.00]);

    $response = $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => 500.00,
        'payout_method' => 'zelle',
        'payout_details' => '555-123-4567',
        'notes' => 'Medical emergency payout',
    ]);

    $response->assertRedirect(route('member.savings.withdrawals'));

    $this->assertDatabaseHas('savings_withdrawal_requests', [
        'member_id' => $member->id,
        'gross_amount' => 500.00,
        'fee_rate' => 2.00,
        'fee_amount' => 10.00,
        'net_amount' => 490.00,
        'payout_method' => 'zelle',
        'payout_details' => '555-123-4567',
        'notes' => 'Medical emergency payout',
        'status' => 'pending',
    ]);
});

test('fee calculation rounds accurately on fractional amounts', function () {
    [$user, $member] = createMemberWithUser(1000.00);
    Setting::updateOrCreate([], ['savings_withdrawal_fee_percent' => 2.00]);

    $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => 123.45,
        'payout_method' => 'zelle',
        'payout_details' => 'test@member.org',
    ]);

    $req = SavingsWithdrawalRequest::where('member_id', $member->id)->latest()->first();
    // 123.45 * 0.02 = 2.469 -> rounds to 2.47
    expect((float)$req->fee_amount)->toEqual(2.47)
        ->and((float)$req->net_amount)->toEqual(120.98);
});

test('member cannot request withdrawal exceeding their savings balance', function () {
    [$user, $member] = createMemberWithUser(300.00);
    Setting::updateOrCreate([], ['savings_withdrawal_fee_percent' => 2.00]);

    $response = $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => 300.01,
        'payout_method' => 'zelle',
        'payout_details' => 'zelle@test.com',
    ]);

    $response->assertSessionHasErrors(['gross_amount']);
    expect(SavingsWithdrawalRequest::count())->toEqual(0);
});

test('member cannot request zero or negative withdrawal amount', function () {
    [$user, $member] = createMemberWithUser(500.00);

    $responseZero = $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => 0.00,
        'payout_method' => 'zelle',
        'payout_details' => 'zelle@test.com',
    ]);
    $responseZero->assertSessionHasErrors(['gross_amount']);

    $responseNeg = $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => -50.00,
        'payout_method' => 'zelle',
        'payout_details' => 'zelle@test.com',
    ]);
    $responseNeg->assertSessionHasErrors(['gross_amount']);
});

test('member cannot double-spend savings across multiple pending withdrawals', function () {
    [$user, $member] = createMemberWithUser(500.00);
    Setting::updateOrCreate([], ['savings_withdrawal_fee_percent' => 2.00]);

    // First request takes $400 out of $500 available
    $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => 400.00,
        'payout_method' => 'zelle',
        'payout_details' => 'zelle@test.com',
    ]);

    expect((float)$member->fresh()->available_savings_balance)->toEqual(100.00);

    // Second request attempts $150 ($400 + $150 > $500)
    $response = $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => 150.00,
        'payout_method' => 'zelle',
        'payout_details' => 'zelle@test.com',
    ]);

    $response->assertSessionHasErrors(['gross_amount']);
    expect(SavingsWithdrawalRequest::where('member_id', $member->id)->count())->toEqual(1);
});

test('withdrawal request requires valid payout method and details', function () {
    [$user, $member] = createMemberWithUser(500.00);

    // Invalid payout method
    $response = $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => 100.00,
        'payout_method' => 'crypto',
        'payout_details' => 'btc-wallet',
    ]);
    $response->assertSessionHasErrors(['payout_method']);

    // Missing payout details
    $responseMissing = $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => 100.00,
        'payout_method' => 'zelle',
        'payout_details' => '',
    ]);
    $responseMissing->assertSessionHasErrors(['payout_details']);
});

test('admin can approve pending withdrawal, debiting member savings atomically and logging fee', function () {
    [$user, $member, $org] = createMemberWithUser(1000.00);
    $admin = createAdminUser();
    Setting::updateOrCreate([], ['savings_withdrawal_fee_percent' => 2.00]);

    $req = SavingsWithdrawalRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'gross_amount' => 200.00,
        'fee_rate' => 2.00,
        'fee_amount' => 4.00,
        'net_amount' => 196.00,
        'payout_method' => 'zelle',
        'payout_details' => 'member@zelle.com',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->post(route('savings.withdrawal-requests.approve', $req->id), [
        'admin_notes' => 'Processed via Zelle confirmation #ZEL999',
    ]);

    $response->assertSessionHas('success');

    // Assert withdrawal request status updated
    expect($req->fresh()->status)->toEqual('approved')
        ->and($req->fresh()->reviewed_by)->toEqual($admin->id)
        ->and($req->fresh()->admin_notes)->toEqual('Processed via Zelle confirmation #ZEL999');

    // Assert ledger debit transaction created
    $this->assertDatabaseHas('savings_transactions', [
        'member_id' => $member->id,
        'amount' => 200.00,
        'type' => 'withdrawal',
        'status' => 'approved',
    ]);

    // Assert member fresh balance is debited
    expect((float)$member->fresh()->savings_balance)->toEqual(800.00);
});

test('admin cannot approve an already approved or rejected withdrawal', function () {
    [$user, $member, $org] = createMemberWithUser(1000.00);
    $admin = createAdminUser();

    $req = SavingsWithdrawalRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'gross_amount' => 200.00,
        'fee_rate' => 2.00,
        'fee_amount' => 4.00,
        'net_amount' => 196.00,
        'payout_method' => 'zelle',
        'payout_details' => 'member@zelle.com',
        'status' => 'approved',
    ]);

    $response = $this->actingAs($admin)->post(route('savings.withdrawal-requests.approve', $req->id));
    $response->assertSessionHas('error');
});

test('admin can reject withdrawal with mandatory reason note', function () {
    [$user, $member, $org] = createMemberWithUser(1000.00);
    $admin = createAdminUser();

    $req = SavingsWithdrawalRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'gross_amount' => 200.00,
        'fee_rate' => 2.00,
        'fee_amount' => 4.00,
        'net_amount' => 196.00,
        'payout_method' => 'zelle',
        'payout_details' => 'member@zelle.com',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->post(route('savings.withdrawal-requests.reject', $req->id), [
        'rejection_reason' => 'Zelle account name does not match member profile name.',
    ]);

    $response->assertSessionHas('success');

    expect($req->fresh()->status)->toEqual('rejected')
        ->and($req->fresh()->rejection_reason)->toEqual('Zelle account name does not match member profile name.');

    // Assert no withdrawal transaction debited
    expect(SavingsTransaction::where('type', 'withdrawal')->count())->toEqual(0)
        ->and((float)$member->fresh()->savings_balance)->toEqual(1000.00);
});

test('admin cannot reject withdrawal without providing mandatory reason', function () {
    [$user, $member, $org] = createMemberWithUser(1000.00);
    $admin = createAdminUser();

    $req = SavingsWithdrawalRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'gross_amount' => 200.00,
        'fee_rate' => 2.00,
        'fee_amount' => 4.00,
        'net_amount' => 196.00,
        'payout_method' => 'zelle',
        'payout_details' => 'member@zelle.com',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->post(route('savings.withdrawal-requests.reject', $req->id), [
        'rejection_reason' => '',
    ]);

    $response->assertSessionHasErrors(['rejection_reason']);
    expect($req->fresh()->status)->toEqual('pending');
});

test('regular member cannot access admin withdrawal queue or approve requests', function () {
    [$user, $member, $org] = createMemberWithUser(1000.00);

    $req = SavingsWithdrawalRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'gross_amount' => 200.00,
        'fee_rate' => 2.00,
        'fee_amount' => 4.00,
        'net_amount' => 196.00,
        'payout_method' => 'zelle',
        'payout_details' => 'member@zelle.com',
        'status' => 'pending',
    ]);

    $responseGet = $this->actingAs($user)->get(route('savings.withdrawal-requests'));
    $responseGet->assertForbidden();

    $responsePost = $this->actingAs($user)->post(route('savings.withdrawal-requests.approve', $req->id));
    $responsePost->assertForbidden();
});

test('member can view their own withdrawal requests', function () {
    [$user, $member, $org] = createMemberWithUser(1000.00);

    SavingsWithdrawalRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'gross_amount' => 350.00,
        'fee_rate' => 2.00,
        'fee_amount' => 7.00,
        'net_amount' => 343.00,
        'payout_method' => 'zelle',
        'payout_details' => '350zelle@test.com',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->get(route('member.savings.withdrawals'));
    $response->assertOk();
    $response->assertSee('$350.00');
    $response->assertSee('350zelle@test.com');
});

test('method-specific validation enforces required fields for Zelle, Bank Transfer, and Check (M1-002)', function () {
    [$user, $member, $org] = createMemberWithUser(2000.00);

    // 1. Zelle missing recipient and contact
    $resZelleFail = $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => 100.00,
        'payout_method' => 'zelle',
        'zelle_name' => '',
        'zelle_contact' => '',
    ]);
    $resZelleFail->assertSessionHasErrors(['zelle_name', 'zelle_contact']);

    // Valid Zelle
    $resZelleOk = $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => 100.00,
        'payout_method' => 'zelle',
        'zelle_name' => 'John Doe',
        'zelle_contact' => 'john.doe@example.com',
    ]);
    $resZelleOk->assertRedirect(route('member.savings.withdrawals'));
    $zelleReq = SavingsWithdrawalRequest::where('member_id', $member->id)->latest('id')->first();
    expect($zelleReq->payout_details)->toContain('Recipient: John Doe')
        ->and($zelleReq->payout_details)->toContain('Contact: john.doe@example.com');

    // 2. Bank transfer invalid routing and missing details
    $resBankFail = $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => 200.00,
        'payout_method' => 'bank_transfer',
        'bank_account_holder' => '',
        'bank_name' => '',
        'bank_routing_number' => '123', // Not 9 digits
        'bank_account_number' => '',
        'bank_account_type' => 'checking',
    ]);
    $resBankFail->assertSessionHasErrors(['bank_account_holder', 'bank_name', 'bank_routing_number', 'bank_account_number']);

    // Valid Bank Transfer
    $resBankOk = $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => 200.00,
        'payout_method' => 'bank_transfer',
        'bank_account_holder' => 'Jane Smith',
        'bank_name' => 'Wells Fargo',
        'bank_routing_number' => '123456789',
        'bank_account_number' => '9876543210',
        'bank_account_type' => 'checking',
    ]);
    $resBankOk->assertRedirect(route('member.savings.withdrawals'));
    $bankReq = SavingsWithdrawalRequest::where('member_id', $member->id)->latest('id')->first();
    expect($bankReq->payout_details)->toContain('Holder: Jane Smith')
        ->and($bankReq->payout_details)->toContain('Bank: Wells Fargo')
        ->and($bankReq->payout_details)->toContain('Routing: 123456789')
        ->and($bankReq->payout_details)->toContain('Account: 9876543210');

    // 3. Check missing payee and delivery
    $resCheckFail = $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => 150.00,
        'payout_method' => 'check',
        'check_payee_name' => '',
        'check_delivery_address' => '',
    ]);
    $resCheckFail->assertSessionHasErrors(['check_payee_name', 'check_delivery_address']);

    // Valid Check
    $resCheckOk = $this->actingAs($user)->post(route('member.savings.withdraw'), [
        'gross_amount' => 150.00,
        'payout_method' => 'check',
        'check_payee_name' => 'Alice Johnson',
        'check_delivery_address' => '123 Main St, Springfield, VA 22150',
    ]);
    $resCheckOk->assertRedirect(route('member.savings.withdrawals'));
    $checkReq = SavingsWithdrawalRequest::where('member_id', $member->id)->latest('id')->first();
    expect($checkReq->payout_details)->toContain('Payee: Alice Johnson')
        ->and($checkReq->payout_details)->toContain('Delivery: 123 Main St');
});

test('masked payout details attribute masks account number and routing number for non-privileged views (M1-002)', function () {
    [$user, $member, $org] = createMemberWithUser(1000.00);

    $bankReq = SavingsWithdrawalRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'gross_amount' => 200.00,
        'fee_rate' => 2.00,
        'fee_amount' => 4.00,
        'net_amount' => 196.00,
        'payout_method' => 'bank_transfer',
        'payout_details' => 'Holder: Jane Smith | Bank: Wells Fargo | Routing: 123456789 | Account: 9876543210 | Type: Checking',
        'status' => 'pending',
    ]);

    // Masked details should hide full routing and account digits
    expect($bankReq->masked_payout_details)->toContain('•••••6789')
        ->and($bankReq->masked_payout_details)->toContain('••••3210')
        ->and($bankReq->masked_payout_details)->not->toContain('123456789')
        ->and($bankReq->masked_payout_details)->not->toContain('9876543210');
});
