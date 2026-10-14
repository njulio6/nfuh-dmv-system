<?php

use App\Models\User;
use App\Models\Member;
use App\Models\Organization;
use App\Models\SavingsTransaction;
use App\Models\LoanRequest;
use App\Models\Setting;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'member']);

    if (Setting::count() === 0) {
        Setting::create([
            'min_savings_for_loan' => 500.00,
            'default_loan_interest' => 1.00,
            'savings_withdrawal_fee_percent' => 2.00,
            'loan_guarantor_min' => 1,
            'loan_guarantor_max' => 3,
            'app_name' => 'NFUH DMV',
            'beneficiary_count' => 4,
        ]);
    } else {
        Setting::query()->update([
            'min_savings_for_loan' => 500.00,
            'default_loan_interest' => 1.00,
            'savings_withdrawal_fee_percent' => 2.00,
            'loan_guarantor_min' => 1,
            'loan_guarantor_max' => 3,
        ]);
    }
});

function createLoanTestMember(float $savingsBalance = 1000.00): array {
    $org = Organization::firstOrCreate(['name' => 'Test Org']);
    $user = User::factory()->create();
    $user->assignRole('member');

    $member = Member::create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'member_code' => 'M-' . rand(1000, 9999),
        'first_name' => 'Borrower',
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
            'notes' => 'Seed balance',
        ]);
    }

    // Also create 2 other members to act as potential guarantors
    $g1 = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'G1-' . rand(1000, 9999),
        'first_name' => 'Guarantor',
        'last_name' => 'One',
        'phone' => '111111',
        'status' => 'Active',
    ]);
    $g2 = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'G2-' . rand(1000, 9999),
        'first_name' => 'Guarantor',
        'last_name' => 'Two',
        'phone' => '222222',
        'status' => 'Active',
    ]);

    return [$user, $member, $org, $g1, $g2];
}

function createLoanAdmin(): User {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    return $admin;
}

test('loan applicant must satisfy dynamic min_savings_for_loan threshold', function () {
    // Threshold is $500.00
    if (Setting::count() === 0) {
        Setting::create(['min_savings_for_loan' => 500.00]);
    } else {
        Setting::query()->update(['min_savings_for_loan' => 500.00]);
    }

    // Member with $499.00 savings
    [$userShort, $memberShort, , $g1] = createLoanTestMember(499.00);

    $responseShort = $this->actingAs($userShort)->post(route('member.loans.request'), [
        'amount' => 1000.00,
        'duration_months' => 12,
        'repayment_frequency' => 'monthly',
        'payout_method' => 'zelle',
        'payout_details' => 'zelle@test.com',
        'guarantors' => [$g1->id],
    ]);
    $responseShort->assertSessionHas('error');
    expect(LoanRequest::where('member_id', $memberShort->id)->count())->toEqual(0);

    // Member with exactly $500.00 savings
    [$userEligible, $memberEligible, , $g1Eligible] = createLoanTestMember(500.00);

    $responseEligible = $this->actingAs($userEligible)->post(route('member.loans.request'), [
        'amount' => 1000.00,
        'duration_months' => 12,
        'repayment_frequency' => 'monthly',
        'payout_method' => 'zelle',
        'payout_details' => 'zelle@test.com',
        'guarantors' => [$g1Eligible->id],
    ]);
    $responseEligible->assertSessionHas('success');
    expect(LoanRequest::where('member_id', $memberEligible->id)->count())->toEqual(1);
});

test('updating min_savings_for_loan in settings immediately applies to new applications', function () {
    // Increase threshold to $750.00
    Setting::query()->update(['min_savings_for_loan' => 750.00]);

    // Member with $600.00 savings (passes 500, fails 750)
    [$user, $member, , $g1] = createLoanTestMember(600.00);

    $response = $this->actingAs($user)->post(route('member.loans.request'), [
        'amount' => 1000.00,
        'duration_months' => 12,
        'repayment_frequency' => 'monthly',
        'payout_method' => 'zelle',
        'payout_details' => 'zelle@test.com',
        'guarantors' => [$g1->id],
    ]);

    $response->assertSessionHas('error');
    expect(LoanRequest::where('member_id', $member->id)->count())->toEqual(0);
});

test('loan request automatically records dynamic default_loan_interest rate', function () {
    Setting::query()->update(['min_savings_for_loan' => 500.00, 'default_loan_interest' => 1.00]);

    [$user, $member, , $g1] = createLoanTestMember(1000.00);

    $this->actingAs($user)->post(route('member.loans.request'), [
        'amount' => 1000.00,
        'duration_months' => 6,
        'repayment_frequency' => 'monthly',
        'payout_method' => 'zelle',
        'payout_details' => 'test@zelle.com',
        'guarantors' => [$g1->id],
    ]);

    $loan1 = LoanRequest::where('member_id', $member->id)->latest('id')->first();
    expect((float)$loan1->interest_rate)->toEqual(1.00);

    // Update setting to 2.50%
    Setting::query()->update(['default_loan_interest' => 2.50]);

    $this->actingAs($user)->post(route('member.loans.request'), [
        'amount' => 2000.00,
        'duration_months' => 12,
        'repayment_frequency' => 'quarterly',
        'payout_method' => 'bank_transfer',
        'payout_details' => 'Bank info',
        'guarantors' => [$g1->id],
    ]);

    $loan2 = LoanRequest::where('member_id', $member->id)->latest('id')->first();
    expect((float)$loan2->interest_rate)->toEqual(2.50);
});

test('loan request captures repayment frequency: monthly, quarterly, end_of_term', function () {
    Setting::query()->update(['min_savings_for_loan' => 500.00, 'default_loan_interest' => 1.00]);
    [$user, $member, , $g1] = createLoanTestMember(1000.00);

    foreach (['monthly', 'quarterly', 'end_of_term'] as $freq) {
        $response = $this->actingAs($user)->post(route('member.loans.request'), [
            'amount' => 500.00,
            'duration_months' => 6,
            'repayment_frequency' => $freq,
            'payout_method' => 'zelle',
            'payout_details' => 'test@zelle.com',
            'guarantors' => [$g1->id],
        ]);
        $response->assertSessionHas('success');

        $latestLoan = LoanRequest::where('member_id', $member->id)->latest('id')->first();
        expect($latestLoan->repayment_frequency)->toEqual($freq);
    }

    // Invalid frequency
    $responseBad = $this->actingAs($user)->post(route('member.loans.request'), [
        'amount' => 500.00,
        'duration_months' => 6,
        'repayment_frequency' => 'biweekly',
        'payout_method' => 'zelle',
        'payout_details' => 'test@zelle.com',
        'guarantors' => [$g1->id],
    ]);
    $responseBad->assertSessionHasErrors(['repayment_frequency']);
});

test('loan request requires payout method and details', function () {
    Setting::query()->update(['min_savings_for_loan' => 500.00]);
    [$user, , , $g1] = createLoanTestMember(1000.00);

    // Missing payout details
    $responseNoDetails = $this->actingAs($user)->post(route('member.loans.request'), [
        'amount' => 500.00,
        'duration_months' => 6,
        'repayment_frequency' => 'monthly',
        'payout_method' => 'zelle',
        'payout_details' => '',
        'guarantors' => [$g1->id],
    ]);
    $responseNoDetails->assertSessionHasErrors(['payout_details']);

    // Invalid payout method
    $responseBadMethod = $this->actingAs($user)->post(route('member.loans.request'), [
        'amount' => 500.00,
        'duration_months' => 6,
        'repayment_frequency' => 'monthly',
        'payout_method' => 'crypto',
        'payout_details' => 'bitcoin',
        'guarantors' => [$g1->id],
    ]);
    $responseBadMethod->assertSessionHasErrors(['payout_method']);
});

test('admin loan rejection requires mandatory rejection reason', function () {
    [$user, $member, $org, $g1] = createLoanTestMember(1000.00);
    $admin = createLoanAdmin();

    $loan = LoanRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 1000.00,
        'duration_months' => 12,
        'repayment_frequency' => 'monthly',
        'payout_method' => 'zelle',
        'payout_details' => 'test@zelle.com',
        'status' => 'pending_committee',
    ]);

    // Attempt rejection with empty reason
    $responseEmpty = $this->actingAs($admin)->post(route('loans.reject', $loan->id), [
        'rejection_reason' => '',
        'notes' => '',
    ]);
    $responseEmpty->assertSessionHasErrors(['rejection_reason']);
    expect($loan->fresh()->status)->toEqual('pending_committee');

    // Reject with valid reason
    $responseValid = $this->actingAs($admin)->post(route('loans.reject', $loan->id), [
        'rejection_reason' => 'Debt-to-income ratio exceeds association policy threshold.',
    ]);
    $responseValid->assertSessionHas('success');

    expect($loan->fresh()->status)->toEqual('rejected')
        ->and($loan->fresh()->rejection_reason)->toEqual('Debt-to-income ratio exceeds association policy threshold.');
});

test('rejected loan displays rejection reason to the applicant', function () {
    [$user, $member, $org] = createLoanTestMember(1000.00);

    LoanRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 1500.00,
        'duration_months' => 12,
        'repayment_frequency' => 'monthly',
        'payout_method' => 'zelle',
        'payout_details' => 'test@zelle.com',
        'status' => 'rejected',
        'rejection_reason' => 'Insufficient continuous association tenure for this loan tier.',
    ]);

    $response = $this->actingAs($user)->get(route('member.loans.applications'));
    $response->assertOk();
    $response->assertSee('Insufficient continuous association tenure for this loan tier.');
});

test('admin can disburse loan with optional details and uploaded receipt proof', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    [$user, $member, $org] = createLoanTestMember(2000.00);
    $admin = createLoanAdmin();

    $loan = LoanRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 1200.00,
        'duration_months' => 6,
        'status' => 'approved',
        'payout_method' => 'bank_transfer',
        'payout_details' => 'Chase Bank Acct #88392019',
    ]);

    $file = \Illuminate\Http\UploadedFile::fake()->create('disbursement_voucher.pdf', 300, 'application/pdf');

    $response = $this->actingAs($admin)->post(route('loans.disburse', $loan->id), [
        'disbursement_details' => 'Wire Ref #WIRE-992144 sent via Chase Corporate Banking.',
        'disbursement_proof' => $file,
    ]);

    $response->assertRedirect(route('loans.index'));
    $response->assertSessionHas('success');

    $freshLoan = $loan->fresh();
    expect($freshLoan->status)->toEqual('active')
        ->and($freshLoan->disbursed_at)->not->toBeNull()
        ->and($freshLoan->disbursement_details)->toEqual('Wire Ref #WIRE-992144 sent via Chase Corporate Banking.')
        ->and($freshLoan->disbursement_proof)->not->toBeNull();

    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($freshLoan->disbursement_proof);
});

test('admin can disburse loan without optional details or receipt proof', function () {
    [$user, $member, $org] = createLoanTestMember(2000.00);
    $admin = createLoanAdmin();

    $loan = LoanRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 500.00,
        'duration_months' => 3,
        'status' => 'approved',
        'payout_method' => 'cash',
        'payout_details' => 'Handed over in meeting hall',
    ]);

    $response = $this->actingAs($admin)->post(route('loans.disburse', $loan->id), []);

    $response->assertRedirect(route('loans.index'));
    $response->assertSessionHas('success');

    $freshLoan = $loan->fresh();
    expect($freshLoan->status)->toEqual('active')
        ->and($freshLoan->disbursed_at)->not->toBeNull()
        ->and($freshLoan->disbursement_details)->toBeNull()
        ->and($freshLoan->disbursement_proof)->toBeNull();
});

test('disbursed details and receipt proof are displayed in member applications and statement views', function () {
    [$user, $member, $org] = createLoanTestMember(2000.00);

    $loan = LoanRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 800.00,
        'duration_months' => 6,
        'status' => 'active',
        'disbursed_at' => now(),
        'payout_method' => 'mobile_money',
        'payout_details' => 'MTN MoMo +237677112233',
        'disbursement_details' => 'MoMo Transaction ID: 20261014-99881122',
        'disbursement_proof' => 'loan_disbursements/fake_receipt.jpg',
    ]);

    // Member applications view loads successfully with loan
    $response = $this->actingAs($user)->get(route('member.loans.applications'));
    $response->assertOk();
    expect($loan->fresh()->disbursement_details)->toEqual('MoMo Transaction ID: 20261014-99881122')
        ->and($loan->fresh()->disbursement_proof)->toEqual('loan_disbursements/fake_receipt.jpg');

    // Loan statement view shows disbursement & payout details
    $statementResponse = $this->actingAs($user)->get(route('member.loans.statement', $loan->id));
    $statementResponse->assertOk();
    $statementResponse->assertSee('Disbursement & Payout Details', false);
    $statementResponse->assertSee('MoMo Transaction ID: 20261014-99881122');
    $statementResponse->assertSee('fake_receipt.jpg');

    // Admin status list view displays Details button and modal, but no old table column
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $adminUser = \App\Models\User::factory()->create();
    $adminUser->assignRole('admin');
    $adminResponse = $this->actingAs($adminUser)->get(route('loans.status-list', 'active'));
    $adminResponse->assertOk();
    $adminResponse->assertDontSee('Disbursement & Proof', false);
    $adminResponse->assertSee('Details');
    $adminResponse->assertSee('showDetailsModal');
    $adminResponse->assertSee(base64_encode('MoMo Transaction ID: 20261014-99881122') ? 'Details' : '');
});

