<?php

use App\Models\User;
use App\Models\Member;
use App\Models\Organization;
use App\Models\SavingsTransaction;
use App\Models\LoanRequest;
use App\Models\LoanGuarantor;
use App\Models\LoanRepayment;
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

function createGuarantorGovernanceEnvironment(): array {
    $org = Organization::firstOrCreate(['name' => 'Test Org']);

    // Borrower
    $borrowerUser = User::factory()->create();
    $borrowerUser->assignRole('member');
    $borrower = Member::create([
        'organization_id' => $org->id,
        'user_id' => $borrowerUser->id,
        'member_code' => 'BOR-' . rand(1000, 9999),
        'first_name' => 'Alice',
        'last_name' => 'Borrower',
        'phone' => '111222',
        'participates_in_savings' => true,
    ]);

    // Guarantor
    $guarantorUser = User::factory()->create();
    $guarantorUser->assignRole('member');
    $guarantorMember = Member::create([
        'organization_id' => $org->id,
        'user_id' => $guarantorUser->id,
        'member_code' => 'GUA-' . rand(1000, 9999),
        'first_name' => 'Bob',
        'last_name' => 'Guarantor',
        'phone' => '333444',
        'participates_in_savings' => true,
    ]);

    $loan = LoanRequest::create([
        'member_id' => $borrower->id,
        'organization_id' => $org->id,
        'amount' => 1000.00,
        'interest_rate' => 1.00,
        'interest_type' => 'flat',
        'duration_months' => 12,
        'repayment_frequency' => 'monthly',
        'payout_method' => 'zelle',
        'payout_details' => 'alice@zelle.com',
        'status' => 'pending_guarantors',
    ]);

    $guarantor = LoanGuarantor::create([
        'loan_request_id' => $loan->id,
        'guarantor_member_id' => $guarantorMember->id,
        'status' => 'pending',
    ]);

    return [$borrowerUser, $borrower, $guarantorUser, $guarantorMember, $loan, $guarantor, $org];
}

test('guarantor approval requires accepting legal liability terms', function () {
    [, , $guarantorUser, , $loan, $guarantor] = createGuarantorGovernanceEnvironment();

    // Attempt approval without terms_accepted
    $responseNoTerms = $this->actingAs($guarantorUser)->post(route('member.loans.guarantee.approve', $guarantor->id), [
        'notes' => 'Happy to help',
    ]);
    $responseNoTerms->assertSessionHasErrors(['terms_accepted']);
    expect($guarantor->fresh()->status)->toEqual('pending');

    // Attempt with terms_accepted = 0
    $responseFalseTerms = $this->actingAs($guarantorUser)->post(route('member.loans.guarantee.approve', $guarantor->id), [
        'terms_accepted' => '0',
    ]);
    $responseFalseTerms->assertSessionHasErrors(['terms_accepted']);
    expect($guarantor->fresh()->status)->toEqual('pending');
});

test('guarantor accepting terms stores timestamp and ip address', function () {
    [, , $guarantorUser, , $loan, $guarantor] = createGuarantorGovernanceEnvironment();

    $response = $this->actingAs($guarantorUser)->post(route('member.loans.guarantee.approve', $guarantor->id), [
        'terms_accepted' => '1',
        'notes' => 'Confirmed legal guarantor obligations.',
    ]);

    $response->assertSessionHas('success');

    $gFresh = $guarantor->fresh();
    expect($gFresh->status)->toEqual('approved')
        ->and($gFresh->terms_accepted)->toBeTrue()
        ->and($gFresh->terms_accepted_at)->not->toBeNull()
        ->and($gFresh->terms_ip_address)->not->toBeNull();

    // Since this was the only guarantor, loan should advance to pending_committee
    expect($loan->fresh()->status)->toEqual('pending_committee');
});

test('guarantor declining request transitions loan to rejected', function () {
    [, , $guarantorUser, , $loan, $guarantor] = createGuarantorGovernanceEnvironment();

    $response = $this->actingAs($guarantorUser)->post(route('member.loans.guarantee.decline', $guarantor->id), [
        'notes' => 'Cannot take on additional financial liability at this time.',
    ]);

    $response->assertSessionHas('success');

    expect($guarantor->fresh()->status)->toEqual('declined')
        ->and($loan->fresh()->status)->toEqual('rejected');
});

test('guarantor live tracker returns active loans backed by the member', function () {
    [, $borrower, $guarantorUser, $guarantorMember, $loan, $guarantor] = createGuarantorGovernanceEnvironment();

    // Mark guarantor as approved and loan as active
    $guarantor->update([
        'status' => 'approved',
        'terms_accepted' => true,
        'terms_accepted_at' => now(),
    ]);

    $loan->update([
        'status' => 'active',
        'disbursed_at' => now(),
        'repayment_due_date' => now()->addMonths(12),
    ]);

    $response = $this->actingAs($guarantorUser)->get(route('member.loans'));
    $response->assertOk();
    $response->assertSee('Loans I Am Guaranteeing (Live Tracker)');
    $response->assertSee($borrower->name);
    $response->assertSee($borrower->member_code);
    $response->assertSee('$1,010.00'); // $1,000 + 1% interest = $1,010.00
});

test('guarantor tracker accurately reflects repayment progress', function () {
    [, , $guarantorUser, , $loan, $guarantor, $org] = createGuarantorGovernanceEnvironment();

    $guarantor->update([
        'status' => 'approved',
        'terms_accepted' => true,
        'terms_accepted_at' => now(),
    ]);

    $loan->update([
        'status' => 'active',
        'disbursed_at' => now(),
        'repayment_due_date' => now()->addMonths(12),
    ]);

    // Borrower makes repayment of $505 (50% of $1,010)
    LoanRepayment::create([
        'loan_request_id' => $loan->id,
        'organization_id' => $org->id,
        'amount' => 505.00,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'zelle',
        'notes' => 'Halfway installment payment',
    ]);

    $response = $this->actingAs($guarantorUser)->get(route('member.loans'));
    $response->assertOk();
    $response->assertSee('$505.00'); // Repaid amount
    $response->assertSee('50%'); // Progress bar percentage
});
