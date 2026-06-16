<?php

use App\Models\User;
use App\Models\Member;
use App\Models\Organization;
use App\Models\SavingsTransaction;
use App\Models\LoanRequest;
use App\Models\LoanGuarantor;
use App\Models\LoanRepayment;
use App\Models\Setting;

test('member cannot request loan if savings balance is under dynamic settings threshold', function () {
    $org = Organization::create(['name' => 'Test Org']);
    
    // Set settings threshold to $500
    Setting::create([
        'app_name' => 'Test',
        'min_savings_for_loan' => 500.00
    ]);

    $borrowerUser = User::create([
        'name' => 'Borrower',
        'email' => 'borrower@example.com',
        'password' => bcrypt('password'),
    ]);

    $borrower = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-BORR',
        'first_name' => 'Loan',
        'last_name' => 'Applicant',
        'email' => 'borrower@example.com',
        'phone' => '12345',
        'participates_in_savings' => true,
    ]);

    $guarantor = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-GUAR',
        'first_name' => 'Guar',
        'last_name' => 'One',
        'email' => 'guar@example.com',
        'phone' => '54321',
        'participates_in_savings' => true,
    ]);

    // Give borrower $400 in savings (which is under $500 threshold)
    SavingsTransaction::create([
        'member_id' => $borrower->id,
        'organization_id' => $org->id,
        'amount' => 400.00,
        'type' => 'deposit',
        'status' => 'approved',
        'transaction_date' => now()->toDateString(),
    ]);

    // Request loan should fail/redirect back with error
    $response = $this->actingAs($borrowerUser)
        ->post(route('member.loans.request'), [
            'amount' => 1000.00,
            'duration_months' => 12,
            'purpose' => 'Business',
            'guarantors' => [$guarantor->id]
        ]);

    $response->assertSessionHas('error');
    expect(LoanRequest::count())->toEqual(0);
});

test('member can request loan with sufficient savings and it defaults to pending_guarantors', function () {
    $org = Organization::create(['name' => 'Test Org']);
    
    Setting::create([
        'app_name' => 'Test',
        'min_savings_for_loan' => 500.00
    ]);

    $borrowerUser = User::create([
        'name' => 'Borrower',
        'email' => 'borrower@example.com',
        'password' => bcrypt('password'),
    ]);

    $borrower = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-BORR',
        'first_name' => 'Loan',
        'last_name' => 'Applicant',
        'email' => 'borrower@example.com',
        'phone' => '12345',
        'participates_in_savings' => true,
    ]);

    $guarantor = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-GUAR',
        'first_name' => 'Guar',
        'last_name' => 'One',
        'email' => 'guar@example.com',
        'phone' => '54321',
        'status' => 'Active',
        'participates_in_savings' => true,
    ]);

    // Give borrower $600 in savings (eligible)
    SavingsTransaction::create([
        'member_id' => $borrower->id,
        'organization_id' => $org->id,
        'amount' => 600.00,
        'type' => 'deposit',
        'status' => 'approved',
        'transaction_date' => now()->toDateString(),
    ]);

    $response = $this->actingAs($borrowerUser)
        ->post(route('member.loans.request'), [
            'amount' => 1000.00,
            'duration_months' => 12,
            'purpose' => 'Business',
            'guarantors' => [$guarantor->id]
        ]);

    $response->assertRedirect(route('member.loans.applications'));
    $response->assertSessionHas('success');
    
    expect(LoanRequest::count())->toEqual(1);
    $loan = LoanRequest::first();
    expect($loan->status)->toEqual('pending_guarantors');
    expect($loan->amount)->toEqual('1000.00');

    expect(LoanGuarantor::count())->toEqual(1);
    expect(LoanGuarantor::first()->status)->toEqual('pending');
});

test('loan transitions to pending_committee only when all guarantors approve', function () {
    $org = Organization::create(['name' => 'Test Org']);
    
    Setting::create(['app_name' => 'Test', 'min_savings_for_loan' => 100.00]);

    $borrower = Member::create(['organization_id' => $org->id, 'member_code' => 'B1', 'first_name' => 'B', 'last_name' => 'R', 'email' => 'b@example.com', 'phone' => '1', 'participates_in_savings' => true]);
    $g1 = Member::create(['organization_id' => $org->id, 'member_code' => 'G1', 'first_name' => 'G', 'last_name' => '1', 'email' => 'g1@example.com', 'phone' => '2', 'participates_in_savings' => true]);
    $g2 = Member::create(['organization_id' => $org->id, 'member_code' => 'G2', 'first_name' => 'G', 'last_name' => '2', 'email' => 'g2@example.com', 'phone' => '3', 'participates_in_savings' => true]);

    $g1User = User::create(['name' => 'G1', 'email' => 'g1@example.com', 'password' => bcrypt('password')]);
    $g2User = User::create(['name' => 'G2', 'email' => 'g2@example.com', 'password' => bcrypt('password')]);

    $loan = LoanRequest::create([
        'member_id' => $borrower->id,
        'organization_id' => $org->id,
        'amount' => 1000.00,
        'duration_months' => 12,
        'status' => 'pending_guarantors'
    ]);

    $gReq1 = LoanGuarantor::create(['loan_request_id' => $loan->id, 'guarantor_member_id' => $g1->id, 'status' => 'pending']);
    $gReq2 = LoanGuarantor::create(['loan_request_id' => $loan->id, 'guarantor_member_id' => $g2->id, 'status' => 'pending']);

    // G1 Approves
    $response = $this->actingAs($g1User)->post(route('member.loans.guarantee.approve', $gReq1->id));
    $response->assertRedirect(route('member.loans'));
    expect($gReq1->fresh()->status)->toEqual('approved');
    expect($loan->fresh()->status)->toEqual('pending_guarantors'); // still pending G2

    // G2 Approves
    $response = $this->actingAs($g2User)->post(route('member.loans.guarantee.approve', $gReq2->id));
    expect($gReq2->fresh()->status)->toEqual('approved');
    expect($loan->fresh()->status)->toEqual('pending_committee'); // both approved
});

test('admin can approve and disburse loan, and record repayment lifecycle', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');

    $org = Organization::create(['name' => 'Test Org']);
    $borrower = Member::create(['organization_id' => $org->id, 'member_code' => 'B1', 'first_name' => 'B', 'last_name' => 'R', 'email' => 'b@example.com', 'phone' => '1', 'participates_in_savings' => true]);

    $loan = LoanRequest::create([
        'member_id' => $borrower->id,
        'organization_id' => $org->id,
        'amount' => 1000.00,
        'duration_months' => 10,
        'status' => 'pending_committee'
    ]);

    // Admin approves
    $response = $this->actingAs($adminUser)->post(route('loans.approve', $loan->id));
    $response->assertRedirect(route('loans.index'));
    expect($loan->fresh()->status)->toEqual('approved');

    // Admin disburses
    $response = $this->actingAs($adminUser)->post(route('loans.disburse', $loan->id));
    $response->assertRedirect(route('loans.index'));
    
    $freshLoan = $loan->fresh();
    expect($freshLoan->status)->toEqual('active');
    expect($freshLoan->disbursed_at)->not->toBeNull();
    expect($freshLoan->repayment_due_date->toDateString())->toEqual(now()->addMonths(10)->toDateString());
    expect((float)$freshLoan->remaining_balance)->toEqual(1000.00);

    // Record partial repayment
    $response = $this->actingAs($adminUser)->post(route('loans.repay', $loan->id), [
        'amount' => 400.00,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'zelle',
        'reference_number' => 'REF123',
        'notes' => 'First installment'
    ]);
    $response->assertRedirect(route('loans.index'));
    expect((float)$loan->fresh()->remaining_balance)->toEqual(600.00);
    expect($loan->fresh()->status)->toEqual('active');

    // Record final repayment
    $response = $this->actingAs($adminUser)->post(route('loans.repay', $loan->id), [
        'amount' => 600.00,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'reference_number' => 'REF456',
        'notes' => 'Settlement'
    ]);
    expect((float)$loan->fresh()->remaining_balance)->toEqual(0.00);
    expect($loan->fresh()->status)->toEqual('completed');
});

test('member can view their own loan applications page with search and status filters', function () {
    $org = Organization::create(['name' => 'Test Org']);
    
    $borrowerUser = User::create([
        'name' => 'Borrower',
        'email' => 'borrower@example.com',
        'password' => bcrypt('password'),
    ]);

    $borrower = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-BORR',
        'first_name' => 'Loan',
        'last_name' => 'Applicant',
        'email' => 'borrower@example.com',
        'phone' => '12345',
        'participates_in_savings' => true,
    ]);

    // Create a couple of loan applications
    $loan1 = LoanRequest::create([
        'member_id' => $borrower->id,
        'organization_id' => $org->id,
        'amount' => 1250.00,
        'duration_months' => 12,
        'purpose' => 'Business expansion',
        'status' => 'pending_guarantors'
    ]);

    $loan2 = LoanRequest::create([
        'member_id' => $borrower->id,
        'organization_id' => $org->id,
        'amount' => 3000.00,
        'duration_months' => 24,
        'purpose' => 'Medical bill payment',
        'status' => 'active'
    ]);

    // 1. Assert simple index page loads
    $response = $this->actingAs($borrowerUser)
        ->get(route('member.loans.applications'));

    $response->assertStatus(200);
    $response->assertSee('$1,250.00');
    $response->assertSee('$3,000.00');
    $response->assertSee('Business expansion');
    $response->assertSee('Medical bill payment');

    // 2. Assert search works by purpose or amount
    $responseSearch = $this->actingAs($borrowerUser)
        ->get(route('member.loans.applications', ['search' => 'expansion']));
    $responseSearch->assertStatus(200);
    $responseSearch->assertSee('Business expansion');
    $responseSearch->assertDontSee('Medical bill payment');

    // 3. Assert status filtering works
    $responseStatus = $this->actingAs($borrowerUser)
        ->get(route('member.loans.applications', ['status' => 'active']));
    $responseStatus->assertStatus(200);
    $responseStatus->assertSee('Medical bill payment');
    $responseStatus->assertDontSee('Business expansion');
});

test('admin can access loan dashboard, status lists, repayments log, and member statements', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');

    $org = Organization::create(['name' => 'Test Org']);
    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-TEST',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '12345',
        'participates_in_savings' => true,
    ]);

    $loan = LoanRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 1500.00,
        'duration_months' => 12,
        'purpose' => 'Education',
        'status' => 'active'
    ]);

    // Add a repayment
    LoanRepayment::create([
        'loan_request_id' => $loan->id,
        'amount' => 200.00,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'zelle',
        'reference_number' => 'REFTEST1',
        'notes' => 'Test repay notes'
    ]);

    // 1. Dashboard
    $response = $this->actingAs($adminUser)->get(route('loans.index'));
    $response->assertStatus(200);
    $response->assertSee('Active Disbursements');
    $response->assertSee('Outstanding Principal');
    $response->assertSee('Total Repayments Collected');
    $response->assertSee('Defaulted Balance');
    $response->assertViewHas('totalRepaymentsCollected', 200.00);
    $response->assertViewHas('totalDefaultedBalance', 0.00);

    // 2. Status List
    $response = $this->actingAs($adminUser)->get(route('loans.status-list', 'active'));
    $response->assertStatus(200);
    $response->assertSee('John Doe');
    $response->assertSee('$1,500.00');

    // 3. Repayments Log
    $response = $this->actingAs($adminUser)->get(route('loans.repayments-log'));
    $response->assertStatus(200);
    $response->assertSee('Test repay notes');
    $response->assertSee('$200.00');

    // 4. Admin Member Statement
    $response = $this->actingAs($adminUser)->get(route('loans.statement', $loan->id));
    $response->assertStatus(200);
    $response->assertSee('Official Loan Statement Report');
    $response->assertSee('John Doe');
});

test('non-admin is forbidden from accessing admin loan dashboard, status lists, repayments log, and statements', function () {
    $nonAdminUser = User::factory()->create();

    $org = Organization::create(['name' => 'Test Org']);
    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-TEST',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '12345',
        'participates_in_savings' => true,
    ]);

    $loan = LoanRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 1500.00,
        'duration_months' => 12,
        'purpose' => 'Education',
        'status' => 'active'
    ]);

    // Assert 403 for admin actions
    $this->actingAs($nonAdminUser)->get(route('loans.index'))->assertStatus(403);
    $this->actingAs($nonAdminUser)->get(route('loans.status-list', 'active'))->assertStatus(403);
    $this->actingAs($nonAdminUser)->get(route('loans.repayments-log'))->assertStatus(403);
    $this->actingAs($nonAdminUser)->get(route('loans.statement', $loan->id))->assertStatus(403);
});

test('member can view their own statement but not other members statements', function () {
    $org = Organization::create(['name' => 'Test Org']);

    $memberUser1 = User::create([
        'name' => 'Member One',
        'email' => 'member1@example.com',
        'password' => bcrypt('password'),
    ]);

    $member1 = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-1',
        'first_name' => 'Member',
        'last_name' => 'One',
        'email' => 'member1@example.com',
        'phone' => '11111',
        'participates_in_savings' => true,
    ]);

    $memberUser2 = User::create([
        'name' => 'Member Two',
        'email' => 'member2@example.com',
        'password' => bcrypt('password'),
    ]);

    $member2 = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-2',
        'first_name' => 'Member',
        'last_name' => 'Two',
        'email' => 'member2@example.com',
        'phone' => '22222',
        'participates_in_savings' => true,
    ]);

    $loan1 = LoanRequest::create([
        'member_id' => $member1->id,
        'organization_id' => $org->id,
        'amount' => 1000.00,
        'duration_months' => 12,
        'status' => 'active'
    ]);

    // Member 1 can view their own statement
    $response = $this->actingAs($memberUser1)->get(route('member.loans.statement', $loan1->id));
    $response->assertStatus(200);
    $response->assertSee('Official Loan Statement Report');
    $response->assertSee('Member One');

    // Member 2 cannot view Member 1's statement
    $response = $this->actingAs($memberUser2)->get(route('member.loans.statement', $loan1->id));
    $response->assertStatus(403);
});


