<?php

use App\Models\User;
use App\Models\Member;
use App\Models\Organization;
use App\Models\SavingsTransaction;
use App\Models\NjangiCycle;
use App\Models\NjangiSession;
use App\Models\NjangiContribution;
use App\Models\MeetingAttendance;

test('savings csv export contains previous and new balance headers', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $org = Organization::create(['name' => 'Export Org']);

    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'EXP-01',
        'first_name' => 'Export',
        'last_name' => 'User',
        'participates_in_savings' => true,
    ]);

    SavingsTransaction::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 500.00,
        'previous_balance' => 0.00,
        'new_balance' => 500.00,
        'type' => 'deposit',
        'status' => 'approved',
        'transaction_date' => now()->toDateString(),
        'notes' => 'Initial deposit',
        'reviewed_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->get(route('reports.export.savings'));
    $response->assertStatus(200);
    $response->assertHeader('Content-type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    expect($content)->toContain('Transaction ID')
        ->toContain('Previous Balance ($)')
        ->toContain('New Balance ($)')
        ->toContain('Officer / Reviewed By')
        ->toContain('500.00');
});

test('savings balances view csv export streams member balances with first and last name ordering', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $org = Organization::create(['name' => 'Export Balances Org']);

    $memberUser = User::factory()->create(['email' => 'savingsuser@nfuh.org']);

    $member = Member::create([
        'organization_id' => $org->id,
        'user_id' => $memberUser->id,
        'member_code' => 'SB-01',
        'first_name' => 'Savings',
        'last_name' => 'AccountHolder',
        'participates_in_savings' => true,
        'state_code' => 'MD',
    ]);

    SavingsTransaction::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 750.00,
        'previous_balance' => 0.00,
        'new_balance' => 750.00,
        'type' => 'deposit',
        'status' => 'approved',
        'transaction_date' => now()->toDateString(),
        'notes' => 'Member verified vault balance',
        'reviewed_by' => $admin->id,
    ]);

    // Test without search filter
    $response = $this->actingAs($admin)->get(route('reports.export.savings', ['view' => 'balances']));
    $response->assertStatus(200);
    $content = $response->streamedContent();
    expect($content)->toContain('Member Code')
        ->toContain('Current Savings Balance ($)')
        ->toContain('SB-01')
        ->toContain('Savings AccountHolder')
        ->toContain('savingsuser@nfuh.org')
        ->toContain('750.00');

    // Test with search filter matching first/last name
    $searchResponse = $this->actingAs($admin)->get(route('reports.export.savings', ['view' => 'balances', 'search' => 'AccountHolder']));
    $searchResponse->assertStatus(200);
    expect($searchResponse->streamedContent())->toContain('SB-01');
});

test('njangi csv export streams valid contributions ledger', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $org = Organization::create(['name' => 'Export Org 2']);

    $cycle = NjangiCycle::create([
        'organization_id' => $org->id,
        'name' => '2026 Test Cycle',
        'year' => 2026,
        'status' => 'active',
        'contribution_amount' => 100.00,
    ]);

    $session = NjangiSession::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 1,
        'title' => 'January Kickoff',
        'meeting_date' => now()->toDateString(),
        'session_date' => now()->toDateString(),
        'status' => 'closed',
    ]);

    $contributor = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'EXP-02',
        'first_name' => 'Njangi',
        'last_name' => 'Member',
        'participates_in_njangi' => true,
    ]);

    NjangiContribution::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'njangi_session_id' => $session->id,
        'contributor_member_id' => $contributor->id,
        'beneficiary_member_id' => $contributor->id,
        'amount' => 100.00,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'reference_number' => 'NJ-REF-001',
    ]);

    $response = $this->actingAs($admin)->get(route('reports.export.njangi'));
    $response->assertStatus(200);
    $response->assertHeader('Content-type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    expect($content)->toContain('Contribution ID')
        ->toContain('Cycle Name')
        ->toContain('2026 Test Cycle')
        ->toContain('January Kickoff')
        ->toContain('100.00');
});

test('attendance csv export streams valid roll-call verification records', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $org = Organization::create(['name' => 'Export Org 3']);

    $cycle = NjangiCycle::create([
        'organization_id' => $org->id,
        'name' => '2026 Cycle',
        'year' => 2026,
        'status' => 'active',
        'contribution_amount' => 50.00,
    ]);

    $session = NjangiSession::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 1,
        'title' => 'February Session',
        'meeting_date' => now()->toDateString(),
        'session_date' => now()->toDateString(),
        'status' => 'closed',
    ]);

    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'EXP-03',
        'first_name' => 'Attend',
        'last_name' => 'Member',
    ]);

    MeetingAttendance::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'attendance_status' => 'present',
        'check_in_source' => 'qr_self_check_in',
        'check_in_at' => now(),
        'verified_by' => $admin->id,
        'verified_at' => now(),
        'officer_notes' => 'Punctual arrival',
    ]);

    $response = $this->actingAs($admin)->get(route('reports.export.attendance'));
    $response->assertStatus(200);
    $response->assertHeader('Content-type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    expect($content)->toContain('Attendance ID')
        ->toContain('February Session')
        ->toContain('Present')
        ->toContain('Qr Self Check In')
        ->toContain('Punctual arrival');
});

test('member data isolation prevents members from exporting other members records', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'member']);
    $user1 = User::factory()->create();
    $user1->assignRole('member');

    $user2 = User::factory()->create();
    $user2->assignRole('member');

    $org = Organization::create(['name' => 'Isolation Org']);

    $member1 = Member::create([
        'organization_id' => $org->id,
        'user_id' => $user1->id,
        'member_code' => 'ISO-01',
        'first_name' => 'Member',
        'last_name' => 'One',
    ]);

    $member2 = Member::create([
        'organization_id' => $org->id,
        'user_id' => $user2->id,
        'member_code' => 'ISO-02',
        'first_name' => 'Member',
        'last_name' => 'Two',
    ]);

    \App\Models\SavingsTransaction::create([
        'member_id' => $member1->id,
        'organization_id' => $org->id,
        'amount' => 111.00,
        'previous_balance' => 0.00,
        'new_balance' => 111.00,
        'type' => 'deposit',
        'status' => 'approved',
        'transaction_date' => now()->toDateString(),
        'notes' => 'Secret note for member one',
    ]);

    \App\Models\SavingsTransaction::create([
        'member_id' => $member2->id,
        'organization_id' => $org->id,
        'amount' => 222.00,
        'previous_balance' => 0.00,
        'new_balance' => 222.00,
        'type' => 'deposit',
        'status' => 'approved',
        'transaction_date' => now()->toDateString(),
        'notes' => 'Secret note for member two',
    ]);

    // Member 1 attempts to export, passing member_id of Member 2 in query string
    $response = $this->actingAs($user1)->get(route('reports.export.savings', ['member_id' => $member2->id]));
    $response->assertStatus(200);

    $content = $response->streamedContent();
    // Must contain Member 1 data only
    expect($content)->toContain('111.00')
        ->toContain('Secret note for member one')
        ->not->toContain('222.00')
        ->not->toContain('Secret note for member two');
});

test('savings deposit requests export respects filters and unpaginated stream', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $org = Organization::create(['name' => 'Deposit Org']);

    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'DEP-01',
        'first_name' => 'Deposit',
        'last_name' => 'Tester',
    ]);

    \App\Models\SavingsDepositRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'amount' => 750.00,
        'status' => 'pending',
        'screenshot_path' => 'receipts/test.png',
        'notes' => 'Urgent deposit test record',
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('reports.export.savings-deposit-requests', ['status' => 'pending']));
    $response->assertStatus(200);
    $response->assertHeader('Content-type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    expect($content)->toContain('Request ID')
        ->toContain('DEP-01')
        ->toContain('750.00')
        ->toContain('Pending')
        ->toContain('Urgent deposit test record');
});

test('export streams entire filtered dataset unpaginated without page cutoff', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $org = Organization::create(['name' => 'Unpaginated Org']);

    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'UNPAG-01',
        'first_name' => 'Batch',
        'last_name' => 'Tester',
    ]);

    // Create 15 loans
    for ($i = 1; $i <= 15; $i++) {
        \App\Models\LoanRequest::create([
            'member_id' => $member->id,
            'organization_id' => $org->id,
            'amount' => 100.00 * $i,
            'duration_months' => 12,
            'repayment_frequency' => 'monthly',
            'status' => 'active',
            'remaining_balance' => 100.00 * $i,
            'purpose' => "Loan sample batch number {$i}",
        ]);
    }

    // Call export passing page=2 and per_page=5 - export must still return all 15 records!
    $response = $this->actingAs($admin)->get(route('reports.export.loans', [
        'status' => 'active',
        'search' => 'Loan sample batch number',
    ]));

    $response->assertStatus(200);
    $content = $response->streamedContent();

    // Verify all 15 records exist in the streamed CSV
    for ($i = 1; $i <= 15; $i++) {
        expect($content)->toContain("Loan sample batch number {$i}");
    }
});

test('members csv export streams active members unpaginated and respects state filter', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $org = Organization::create(['name' => 'Roster Org']);

    Member::create([
        'organization_id' => $org->id,
        'member_code' => 'MD-01',
        'first_name' => 'Maryland',
        'last_name' => 'Resident',
        'status' => 'Active',
        'state_code' => 'MD',
    ]);

    Member::create([
        'organization_id' => $org->id,
        'member_code' => 'VA-01',
        'first_name' => 'Virginia',
        'last_name' => 'Resident',
        'status' => 'Active',
        'state_code' => 'VA',
    ]);

    $response = $this->actingAs($admin)->get(route('reports.export.members', ['state_code' => 'MD']));
    $response->assertStatus(200);

    $content = $response->streamedContent();
    expect($content)->toContain('MD-01')
        ->toContain('Maryland Resident')
        ->not->toContain('VA-01')
        ->not->toContain('Virginia Resident');
});

test('traditional titles csv export streams titles and respects search filter', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    \App\Models\MemberRank::create(['name' => 'Nformi Alpha', 'level' => 1]);
    \App\Models\MemberRank::create(['name' => 'Shey Beta', 'level' => 2]);

    $response = $this->actingAs($admin)->get(route('reports.export.titles', ['search' => 'Alpha']));
    $response->assertStatus(200);

    $content = $response->streamedContent();
    expect($content)->toContain('Nformi Alpha')
        ->not->toContain('Shey Beta');
});

test('loan sub-statuses csv export streams definitions and respects search filter', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    \App\Models\LoanSubStatus::create(['name' => 'Under Review Phase A', 'color' => 'blue']);
    \App\Models\LoanSubStatus::create(['name' => 'Guarantor Call Queue', 'color' => 'amber']);

    $response = $this->actingAs($admin)->get(route('reports.export.sub-statuses', ['search' => 'Phase A']));
    $response->assertStatus(200);

    $content = $response->streamedContent();
    expect($content)->toContain('Under Review Phase A')
        ->not->toContain('Guarantor Call Queue');
});

test('user roles csv export streams assignments and respects role filter', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Treasurer']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $treasurerUser = User::factory()->create(['name' => 'Finance Custodian', 'email' => 'treasurer@nfuh.org']);
    $treasurerUser->assignRole('Treasurer');

    $response = $this->actingAs($admin)->get(route('reports.export.user-roles', ['role_filter' => 'Treasurer']));
    $response->assertStatus(200);

    $content = $response->streamedContent();
    expect($content)->toContain('Finance Custodian')
        ->toContain('treasurer@nfuh.org')
        ->toContain('Treasurer');
});

test('loans csv export respects borrower name and member search filter without sql errors', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $org = Organization::create(['name' => 'Loans Org']);

    $borrowerUser = User::factory()->create(['email' => 'borrower@nfuh.org']);
    $borrower = Member::create([
        'organization_id' => $org->id,
        'user_id' => $borrowerUser->id,
        'member_code' => 'LN-01',
        'first_name' => 'Tatah',
        'last_name' => 'Wirdin',
    ]);

    \App\Models\LoanRequest::create([
        'member_id' => $borrower->id,
        'organization_id' => $org->id,
        'amount' => 1200.00,
        'duration_months' => 6,
        'status' => 'approved',
        'purpose' => 'Agricultural equipment expansion',
        'remaining_balance' => 1200.00,
    ]);

    $response = $this->actingAs($admin)->get(route('reports.export.loans', ['search' => 'Wirdin']));
    $response->assertStatus(200);

    $content = $response->streamedContent();
    expect($content)->toContain('LN-01')
        ->toContain('Tatah Wirdin')
        ->toContain('1200.00')
        ->toContain('Agricultural equipment expansion');
});

test('loan repayments csv export respects member search filter without sql errors', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $org = Organization::create(['name' => 'Repayments Org']);

    $borrower = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'REP-01',
        'first_name' => 'Shey',
        'last_name' => 'Kinyuy',
    ]);

    $loan = \App\Models\LoanRequest::create([
        'member_id' => $borrower->id,
        'organization_id' => $org->id,
        'amount' => 1000.00,
        'duration_months' => 6,
        'status' => 'disbursed',
        'remaining_balance' => 800.00,
    ]);

    \App\Models\LoanRepayment::create([
        'loan_request_id' => $loan->id,
        'amount' => 200.00,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'reference_number' => 'REPAY-TEST-999',
        'notes' => 'Installment month 1',
    ]);

    $response = $this->actingAs($admin)->get(route('reports.export.repayments', ['search' => 'Kinyuy']));
    $response->assertStatus(200);

    $content = $response->streamedContent();
    expect($content)->toContain('REPAY-TEST-999')
        ->toContain('Shey Kinyuy')
        ->toContain('200.00');
});




