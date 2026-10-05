<?php

use App\Models\User;
use App\Models\Member;
use App\Models\Organization;
use App\Models\LoanRequest;
use App\Models\LoanRepayment;
use App\Models\LoanRepaymentRequest;
use Spatie\Permission\Models\Role;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('member can submit repayment request with receipt and valid amount', function () {
    Storage::fake('public');

    $org = Organization::create(['name' => 'Repay Org']);
    
    // Create member and user linked by email
    $email = 'repaymember@example.com';
    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-REPAY1',
        'first_name' => 'Repay',
        'last_name' => 'Member',
        'email' => $email,
        'phone' => '098765',
        'participates_in_savings' => true,
    ]);
    
    $user = User::factory()->create(['email' => $email]);

    $loan = LoanRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 500.00,
        'duration_months' => 6,
        'status' => 'active',
    ]);

    $file = UploadedFile::fake()->image('receipt.png');

    $response = $this->actingAs($user)
        ->post(route('member.loans.repay-request', $loan->id), [
            'amount' => 200.00,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'zelle',
            'screenshot' => $file,
            'reference_number' => 'TXN12345',
            'notes' => 'First partial repayment request',
        ]);

    $response->assertRedirect(route('member.loans.repayment-requests'));
    
    $this->assertDatabaseHas('loan_repayment_requests', [
        'loan_request_id' => $loan->id,
        'member_id' => $member->id,
        'amount' => 200.00,
        'status' => 'pending',
        'payment_method' => 'zelle',
        'reference_number' => 'TXN12345',
        'notes' => 'First partial repayment request',
    ]);

    // Check file upload
    $request = LoanRepaymentRequest::first();
    Storage::disk('public')->assertExists($request->screenshot_path);
});

test('member cannot submit repayment exceeding remaining loan balance', function () {
    Storage::fake('public');

    $org = Organization::create(['name' => 'Repay Org 2']);
    $email = 'repaymember2@example.com';
    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-REPAY2',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => $email,
        'phone' => '111111',
        'participates_in_savings' => true,
    ]);
    
    $user = User::factory()->create(['email' => $email]);

    $loan = LoanRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 300.00,
        'duration_months' => 6,
        'status' => 'active',
    ]);

    $file = UploadedFile::fake()->image('receipt.png');

    $response = $this->actingAs($user)
        ->from(route('member.loans.applications'))
        ->post(route('member.loans.repay-request', $loan->id), [
            'amount' => 350.00, // Exceeds outstanding balance
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'screenshot' => $file,
        ]);

    $response->assertRedirect(route('member.loans.applications'));
    $response->assertSessionHas('error');
    
    $this->assertDatabaseMissing('loan_repayment_requests', [
        'loan_request_id' => $loan->id,
        'amount' => 350.00,
    ]);
});

test('admin can approve a repayment request and loan updates balance and status', function () {
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $org = Organization::create(['name' => 'Repay Org 3']);
    $email = 'repaymember3@example.com';
    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-REPAY3',
        'first_name' => 'Steve',
        'last_name' => 'G',
        'email' => $email,
        'phone' => '222222',
        'participates_in_savings' => true,
    ]);

    $loan = LoanRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 400.00,
        'duration_months' => 6,
        'status' => 'active',
    ]);

    // Create pending repayment request
    $repayRequest = LoanRepaymentRequest::create([
        'loan_request_id' => $loan->id,
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 400.00, // Full payment
        'status' => 'pending',
        'screenshot_path' => 'repayment_proofs/receipt.png',
        'payment_date' => now()->toDateString(),
        'payment_method' => 'zelle',
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->post(route('loans.repayment-requests.approve', $repayRequest->id), [
            'review_note' => 'Paid in full via Zelle',
        ]);

    $response->assertRedirect(route('loans.repayment-requests'));
    
    // Verify request is approved
    expect($repayRequest->fresh()->status)->toEqual('approved');
    expect($repayRequest->fresh()->reviewed_by)->toEqual($admin->id);
    expect($repayRequest->fresh()->review_note)->toEqual('Paid in full via Zelle');

    // Verify ledger entry is created
    $this->assertDatabaseHas('loan_repayments', [
        'loan_request_id' => $loan->id,
        'amount' => 400.00,
        'payment_method' => 'zelle',
    ]);

    // Verify loan request core status is completed
    expect($loan->fresh()->status)->toEqual('completed');
    expect($loan->fresh()->remaining_balance)->toEqual(0.00);
});

test('admin can reject a repayment request with a review note', function () {
    Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $org = Organization::create(['name' => 'Repay Org 4']);
    $email = 'repaymember4@example.com';
    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-REPAY4',
        'first_name' => 'Reject',
        'last_name' => 'Me',
        'email' => $email,
        'phone' => '444444',
        'participates_in_savings' => true,
    ]);

    $loan = LoanRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 200.00,
        'duration_months' => 6,
        'status' => 'active',
    ]);

    $repayRequest = LoanRepaymentRequest::create([
        'loan_request_id' => $loan->id,
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 100.00,
        'status' => 'pending',
        'screenshot_path' => 'repayment_proofs/receipt.png',
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->post(route('loans.repayment-requests.reject', $repayRequest->id), [
            'review_note' => 'Screenshot proof is corrupted',
        ]);

    $response->assertRedirect(route('loans.repayment-requests'));
    
    // Verify request is rejected
    expect($repayRequest->fresh()->status)->toEqual('rejected');
    expect($repayRequest->fresh()->reviewed_by)->toEqual($admin->id);
    expect($repayRequest->fresh()->review_note)->toEqual('Screenshot proof is corrupted');

    // Verify no ledger entry is created
    $this->assertDatabaseMissing('loan_repayments', [
        'loan_request_id' => $loan->id,
        'amount' => 100.00,
    ]);

    // Loan status should still be active
    expect($loan->fresh()->status)->toEqual('active');
});
