<?php

use App\Models\LoanGuarantor;
use App\Models\LoanRequest;
use App\Models\MeetingAttendance;
use App\Models\Member;
use App\Models\NjangiCycle;
use App\Models\NjangiCycleMember;
use App\Models\NjangiSession;
use App\Models\Organization;
use App\Models\SavingsTransaction;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder())->run();
});

function createDashboardContext(): array
{
    $org = Organization::create(['name' => 'NFUH DMV Org']);

    $cycle = NjangiCycle::create([
        'organization_id' => $org->id,
        'name' => '2026 Gold Cycle',
        'year' => 2026,
        'status' => 'active',
    ]);

    $session = NjangiSession::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 1,
        'session_date' => now()->addDays(7)->toDateString(),
        'title' => 'First Assembly 2026',
        'status' => 'open',
    ]);

    $userA = User::factory()->create(['organization_id' => $org->id]);
    $userA->assignRole('member');

    $memberA = Member::create([
        'organization_id' => $org->id,
        'user_id' => $userA->id,
        'member_code' => 'MEM-A',
        'first_name' => 'Alice',
        'last_name' => 'Nfor',
        'phone' => '2401111111',
        'participates_in_savings' => true,
        'participates_in_njangi' => true,
        'status' => 'active',
    ]);

    NjangiCycleMember::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'member_id' => $memberA->id,
        'benefit_order' => 3,
    ]);

    // Give Alice $2,500 in savings
    SavingsTransaction::create([
        'organization_id' => $org->id,
        'member_id' => $memberA->id,
        'amount' => 2500.00,
        'type' => 'deposit',
        'status' => 'approved',
        'transaction_date' => now()->toDateString(),
        'notes' => 'Alice savings deposit',
    ]);

    // Member B (Bob)
    $userB = User::factory()->create(['organization_id' => $org->id]);
    $userB->assignRole('member');

    $memberB = Member::create([
        'organization_id' => $org->id,
        'user_id' => $userB->id,
        'member_code' => 'MEM-B',
        'first_name' => 'Bob',
        'last_name' => 'Talla',
        'phone' => '2402222222',
        'participates_in_savings' => true,
        'status' => 'active',
    ]);

    SavingsTransaction::create([
        'organization_id' => $org->id,
        'member_id' => $memberB->id,
        'amount' => 9999.00,
        'type' => 'deposit',
        'status' => 'approved',
        'transaction_date' => now()->toDateString(),
        'notes' => 'Bob confidential savings',
    ]);

    return [$org, $cycle, $session, $userA, $memberA, $userB, $memberB];
}

test('member dashboard loads private financial data isolated to the authenticated member', function () {
    [$org, $cycle, $session, $userA, $memberA, $userB, $memberB] = createDashboardContext();

    $response = $this->actingAs($userA)->get(route('dashboard'));
    $response->assertStatus(200);

    // Alice should see her own name and $2,500 savings balance
    $response->assertSee('Alice');
    $response->assertSee('2,500.00');

    // Alice MUST NEVER see Bob's confidential $9,999 savings
    $response->assertDontSee('9,999.00');
    $response->assertDontSee('Bob');
});

test('member cannot view other members financial snapshots or loans on the dashboard', function () {
    [$org, $cycle, $session, $userA, $memberA, $userB, $memberB] = createDashboardContext();

    // Bob takes out a confidential loan
    $loanB = LoanRequest::create([
        'organization_id' => $org->id,
        'member_id' => $memberB->id,
        'amount' => 7500.00,
        'duration_months' => 12,
        'purpose' => 'Confidential business loan',
        'status' => 'active',
    ]);

    $response = $this->actingAs($userA)->get(route('dashboard'));
    $response->assertStatus(200);

    // Alice should not see Bob's loan details
    $response->assertDontSee('7,500.00');
    $response->assertDontSee('Confidential business loan');
});

test('action center alerts display pending guarantor requests when active', function () {
    [$org, $cycle, $session, $userA, $memberA, $userB, $memberB] = createDashboardContext();

    $loanB = LoanRequest::create([
        'organization_id' => $org->id,
        'member_id' => $memberB->id,
        'amount' => 1000.00,
        'duration_months' => 6,
        'purpose' => 'Guaranteed loan',
        'status' => 'pending_guarantors',
    ]);

    // Bob requests Alice to guarantee his loan
    LoanGuarantor::create([
        'loan_request_id' => $loanB->id,
        'guarantor_member_id' => $memberA->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($userA)->get(route('dashboard'));
    $response->assertStatus(200);
    $response->assertSee('Pending Guarantor Requests');
    $response->assertSee('Respond Now');
});

test('action center alerts display upcoming meeting check-in prompt when not checked in', function () {
    [$org, $cycle, $session, $userA, $memberA, $userB, $memberB] = createDashboardContext();

    // Alice has not checked in for upcoming session
    $response = $this->actingAs($userA)->get(route('dashboard'));
    $response->assertStatus(200);
    $response->assertSee('Upcoming Meeting');
    $response->assertSee('Open Check-In Pass');

    // Alice checks in
    MeetingAttendance::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'member_id' => $memberA->id,
        'attendance_status' => 'present',
        'check_in_source' => 'qr_self_check_in',
        'check_in_at' => now(),
    ]);

    // Now the banner shows checked in
    $responseAfter = $this->actingAs($userA)->get(route('dashboard'));
    $responseAfter->assertStatus(200);
    $responseAfter->assertDontSee('Open Check-In Pass');
    $responseAfter->assertSee('Checked In Present');
});

test('dashboard displays localized NFUH terminology correctly', function () {
    [$org, $cycle, $session, $userA, $memberA, $userB, $memberB] = createDashboardContext();

    $response = $this->actingAs($userA)->get(route('dashboard'));
    $response->assertStatus(200);
    $response->assertSee('Njangi');
    $response->assertSee('Nkap');
    $response->assertSee('Attendance Score');
});

test('admin dashboard loads cleanly without SQL exceptions and displays governance metrics', function () {
    [$org, $cycle, $session, $userA, $memberA, $userB, $memberB] = createDashboardContext();

    $adminUser = User::factory()->create(['organization_id' => $org->id]);
    $adminUser->assignRole('admin');

    $response = $this->actingAs($adminUser)->get(route('dashboard'));
    $response->assertStatus(200);
    $response->assertSee('Executive oversight');
    $response->assertSee('Active Membership');
    $response->assertSee('Savings Vault (Nkap)');
});

test('member dashboard displays personal loan repayment activity without error', function () {
    [$org, $cycle, $session, $userA, $memberA, $userB, $memberB] = createDashboardContext();

    $loan = LoanRequest::create([
        'organization_id' => $org->id,
        'member_id' => $memberA->id,
        'amount' => 500.00,
        'duration_months' => 3,
        'purpose' => 'Test small loan',
        'status' => 'active',
        'interest_rate' => 0,
        'interest_type' => 'flat',
    ]);

    \App\Models\LoanRepayment::create([
        'loan_request_id' => $loan->id,
        'amount' => 150.00,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'reference_number' => 'REF-123',
    ]);

    $response = $this->actingAs($userA)->get(route('dashboard'));
    $response->assertStatus(200);
    $response->assertSee('Personal Activity Ledger');
    $response->assertSee('Loan Repayment');
    $response->assertSee('150.00');
});

test('member dashboard accurately renders accepted guarantor underwriting statistics', function () {
    [$org, $cycle, $session, $userA, $memberA, $userB, $memberB] = createDashboardContext();

    $loanB = LoanRequest::create([
        'organization_id' => $org->id,
        'member_id' => $memberB->id,
        'amount' => 1200.00,
        'duration_months' => 6,
        'purpose' => 'Business expansion',
        'status' => 'active',
        'interest_rate' => 0,
        'interest_type' => 'flat',
    ]);

    LoanGuarantor::create([
        'loan_request_id' => $loanB->id,
        'guarantor_member_id' => $memberA->id,
        'status' => 'accepted',
        'terms_accepted' => true,
        'terms_accepted_at' => now(),
    ]);

    $response = $this->actingAs($userA)->get(route('dashboard'));
    $response->assertStatus(200);
    $response->assertSee('Guarantorship Trust');
    $response->assertSee('1 Active');
    $response->assertSee('1,200.00 Underwritten');
});

test('executive dashboard renders comprehensive print header, export dropdown, and reports exports', function () {
    $admin = User::factory()->create(['name' => 'Finance Director']);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('dashboard'));
    $response->assertStatus(200);

    // Verify Print-only executive report header
    $response->assertSee('Executive Operations & Comprehensive Financial Report', false);
    $response->assertSee('Report Date:');
    $response->assertSee('Reporting Period:');
    $response->assertSee('Generated By:');
    $response->assertSee('Finance Director');

    // Verify Export dropdown options
    $response->assertSee('Export Report');
    $response->assertSee('Print / Save as PDF');
    $response->assertSee('Export Loans (CSV/Excel)', false);
    $response->assertSee('Export Savings (CSV/Excel)', false);
    $response->assertSee(route('reports.export.loans'));
    $response->assertSee(route('reports.export.savings'));

    // Verify Loans CSV export endpoint streams valid CSV
    $loansCsv = $this->actingAs($admin)->get(route('reports.export.loans'));
    $loansCsv->assertStatus(200);
    $loansCsv->assertHeader('Content-type', 'text/csv; charset=UTF-8');

    // Verify Savings CSV export endpoint streams valid CSV
    $savingsCsv = $this->actingAs($admin)->get(route('reports.export.savings'));
    $savingsCsv->assertStatus(200);
    $savingsCsv->assertHeader('Content-type', 'text/csv; charset=UTF-8');
});

test('officer view renders actual executive role badge rather than generic member badge', function () {
    [$org, $cycle, $session, $userA, $memberA, $userB, $memberB] = createDashboardContext();

    // Assign both member and Treasurer role
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'member']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Treasurer']);
    $userA->assignRole(['member', 'Treasurer']);

    // Set portal mode to officer
    $response = $this->actingAs($userA)
        ->withSession(['portal_mode' => 'officer'])
        ->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertSee('Generated By:');
    $response->assertSee($userA->name . ' (Treasurer)');
    $response->assertDontSee($userA->name . ' (member)');
});

