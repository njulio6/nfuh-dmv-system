<?php

use App\Models\User;
use App\Models\Member;
use App\Models\Organization;
use App\Models\NjangiCycle;
use App\Models\NjangiSession;
use App\Models\NjangiCycleMember;
use App\Models\NjangiPaymentSubmission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->org = Organization::create(['name' => 'NFUH Test']);
});

test('guest is redirected to login', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('user without member profile but with admin role lands on admin dashboard', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $user = User::factory()->create();
    $user->assignRole('admin');
    
    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
    $response->assertViewIs('dashboard');
});

test('user without member profile and without admin role is forbidden', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(403);
});

test('member with admin role lands on admin dashboard', function () {
    $user = User::factory()->create();
    $member = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'MD-2026-001',
        'first_name' => 'Admin',
        'last_name' => 'User',
        'email' => $user->email,
        'phone' => '1234567890',
    ]);
    
    // Assign Secretary role (admin role)
    $role = \App\Models\MemberRole::firstOrCreate(['name' => 'Secretary']);
    $member->roles()->sync([$role->id]);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
    $response->assertViewIs('dashboard');
});

test('member without admin roles lands on member portal dashboard', function () {
    $user = User::factory()->create();
    $member = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'MD-2026-002',
        'first_name' => 'Regular',
        'last_name' => 'Member',
        'email' => $user->email,
        'phone' => '1234567891',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
    $response->assertViewIs('dashboard.member');
});

test('unverified member can still access member portal dashboard', function () {
    $user = User::factory()->unverified()->create();
    $member = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'MD-2026-999',
        'first_name' => 'Unverified',
        'last_name' => 'Member',
        'email' => $user->email,
        'phone' => '1234567899',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
    $response->assertViewIs('dashboard.member');
});

test('member can submit a valid payment with a screenshot', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $member = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'MD-2026-003',
        'first_name' => 'Regular',
        'last_name' => 'Member',
        'email' => $user->email,
        'phone' => '1234567892',
    ]);

    $cycle = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => '2026 Cycle',
        'year' => 2026,
        'status' => 'active',
    ]);

    $session = NjangiSession::create([
        'organization_id' => $this->org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 1,
        'session_date' => now(),
        'status' => 'open',
    ]);

    $screenshot = UploadedFile::fake()->image('zelle.png');

    $response = $this->actingAs($user)->post('/member/submissions', [
        'njangi_session_id' => $session->id,
        'amount' => 400.00,
        'is_attending' => 1,
        'screenshot' => $screenshot,
        'member_note' => 'Paid via Zelle.',
    ]);

    $response->assertRedirect(route('member.njangi-payments'));
    $response->assertSessionHas('success');

    $submission = NjangiPaymentSubmission::first();
    expect($submission)->not->toBeNull();
    expect($submission->member_id)->toEqual($member->id);
    expect((float)$submission->amount)->toEqual(400.00);
    expect($submission->is_attending)->toBeTrue();
    expect($submission->status)->toEqual('pending');
    expect($submission->screenshot_path)->not->toBeNull();

    Storage::disk('public')->assertExists($submission->screenshot_path);
});

test('member cannot submit duplicate payment for the same session', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $member = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'MD-2026-004',
        'first_name' => 'Regular',
        'last_name' => 'Member',
        'email' => $user->email,
        'phone' => '1234567893',
    ]);

    $cycle = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => '2026 Cycle',
        'year' => 2026,
        'status' => 'active',
    ]);

    $session = NjangiSession::create([
        'organization_id' => $this->org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 1,
        'session_date' => now(),
        'status' => 'open',
    ]);

    // Create an existing pending submission
    NjangiPaymentSubmission::create([
        'organization_id' => $this->org->id,
        'member_id' => $member->id,
        'njangi_cycle_id' => $cycle->id,
        'njangi_session_id' => $session->id,
        'amount' => 400.00,
        'is_attending' => true,
        'screenshot_path' => 'screenshots/old.png',
        'status' => 'pending',
    ]);

    $screenshot = UploadedFile::fake()->image('zelle2.png');

    $response = $this->actingAs($user)->from(route('member.njangi-payments'))->post('/member/submissions', [
        'njangi_session_id' => $session->id,
        'amount' => 400.00,
        'is_attending' => 1,
        'screenshot' => $screenshot,
        'member_note' => 'Another submission.',
    ]);

    $response->assertRedirect(route('member.njangi-payments'));
    $response->assertSessionHas('error');
    
    // Make sure only the first submission exists in DB
    expect(NjangiPaymentSubmission::count())->toEqual(1);
});

test('regular member is forbidden from viewing admin members list', function () {
    $user = User::factory()->create();
    Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'MD-2026-005',
        'first_name' => 'Regular',
        'last_name' => 'Member',
        'email' => $user->email,
        'phone' => '1234567894',
    ]);

    $response = $this->actingAs($user)->get('/members');
    $response->assertStatus(403);
});

test('regular member is forbidden from viewing admin cycles list', function () {
    $user = User::factory()->create();
    Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'MD-2026-006',
        'first_name' => 'Regular',
        'last_name' => 'Member',
        'email' => $user->email,
        'phone' => '1234567895',
    ]);

    $response = $this->actingAs($user)->get('/njangi-cycles');
    $response->assertStatus(403);
});

test('member lands on member portal dashboard for the correct active cycle they are enrolled in', function () {
    $user = User::factory()->create();
    $member = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'MD-2026-007',
        'first_name' => 'Specific',
        'last_name' => 'Member',
        'email' => $user->email,
        'phone' => '1234567896',
    ]);

    // Create two active cycles
    $cycle1 = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 1 (First)',
        'year' => 2026,
        'status' => 'active',
    ]);

    $cycle2 = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 2 (Member Joined)',
        'year' => 2026,
        'status' => 'active',
    ]);

    // Member is enrolled only in cycle2 (not cycle1)
    NjangiCycleMember::create([
        'njangi_cycle_id' => $cycle2->id,
        'member_id' => $member->id,
        'benefit_order' => 5,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
    $response->assertViewIs('dashboard.member');
    
    // Assert that cycle2 is loaded
    $response->assertViewHas('activeCycle', function ($cycle) use ($cycle2) {
        return $cycle->id === $cycle2->id;
    });
});

test('member can switch between multiple active cycles they are enrolled in via cycle_id query param', function () {
    $user = User::factory()->create();
    $member = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'MD-2026-008',
        'first_name' => 'Multi',
        'last_name' => 'Cycle Member',
        'email' => $user->email,
        'phone' => '1234567897',
    ]);

    // Create two active cycles
    $cycle1 = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 1',
        'year' => 2026,
        'status' => 'active',
    ]);

    $cycle2 = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 2',
        'year' => 2026,
        'status' => 'active',
    ]);

    // Enroll member in both cycles
    NjangiCycleMember::create([
        'njangi_cycle_id' => $cycle1->id,
        'member_id' => $member->id,
        'benefit_order' => 1,
        'is_active' => true,
    ]);

    NjangiCycleMember::create([
        'njangi_cycle_id' => $cycle2->id,
        'member_id' => $member->id,
        'benefit_order' => 2,
        'is_active' => true,
    ]);

    // Request without cycle_id - should default to first (cycle1)
    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
    $response->assertViewHas('activeCycle', function ($cycle) use ($cycle1) {
        return $cycle->id === $cycle1->id;
    });

    // Request with cycle_id=cycle2->id - should load cycle2
    $response = $this->actingAs($user)->get('/dashboard?cycle_id=' . $cycle2->id);
    $response->assertStatus(200);
    $response->assertViewHas('activeCycle', function ($cycle) use ($cycle2) {
        return $cycle->id === $cycle2->id;
    });
});

test('member njangi payments list only shows submissions for the selected cycle', function () {
    $user = User::factory()->create();
    $member = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'MD-2026-009',
        'first_name' => 'Filter',
        'last_name' => 'Submissions',
        'email' => $user->email,
        'phone' => '1234567898',
    ]);

    // Create two active cycles
    $cycle1 = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 1',
        'year' => 2026,
        'status' => 'active',
    ]);

    $cycle2 = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 2',
        'year' => 2026,
        'status' => 'active',
    ]);

    // Enroll in both
    NjangiCycleMember::create([
        'njangi_cycle_id' => $cycle1->id,
        'member_id' => $member->id,
        'benefit_order' => 1,
        'is_active' => true,
    ]);

    NjangiCycleMember::create([
        'njangi_cycle_id' => $cycle2->id,
        'member_id' => $member->id,
        'benefit_order' => 2,
        'is_active' => true,
    ]);

    $session1 = NjangiSession::create([
        'organization_id' => $this->org->id,
        'njangi_cycle_id' => $cycle1->id,
        'session_number' => 1,
        'session_date' => now(),
        'status' => 'open',
    ]);

    $session2 = NjangiSession::create([
        'organization_id' => $this->org->id,
        'njangi_cycle_id' => $cycle2->id,
        'session_number' => 1,
        'session_date' => now(),
        'status' => 'open',
    ]);

    // Submission in cycle 1
    $sub1 = NjangiPaymentSubmission::create([
        'organization_id' => $this->org->id,
        'member_id' => $member->id,
        'njangi_cycle_id' => $cycle1->id,
        'njangi_session_id' => $session1->id,
        'amount' => 400.00,
        'screenshot_path' => 'screenshots/s1.png',
        'status' => 'pending',
    ]);

    // Submission in cycle 2
    $sub2 = NjangiPaymentSubmission::create([
        'organization_id' => $this->org->id,
        'member_id' => $member->id,
        'njangi_cycle_id' => $cycle2->id,
        'njangi_session_id' => $session2->id,
        'amount' => 500.00,
        'screenshot_path' => 'screenshots/s2.png',
        'status' => 'pending',
    ]);

    // Fetch njangi payments page without cycle_id query -> should load cycle1 and only show $sub1
    $response = $this->actingAs($user)->get(route('member.njangi-payments'));
    $response->assertStatus(200);
    $response->assertViewHas('submissions');
    $submissions = $response->viewData('submissions');
    expect($submissions->pluck('id'))->toContain($sub1->id);
    expect($submissions->pluck('id'))->not->toContain($sub2->id);

    // Fetch njangi payments page with cycle_id for cycle2 -> should load cycle2 and only show $sub2
    $response = $this->actingAs($user)->get(route('member.njangi-payments', ['cycle_id' => $cycle2->id]));
    $response->assertStatus(200);
    $response->assertViewHas('submissions');
    $submissions = $response->viewData('submissions');
    expect($submissions->pluck('id'))->toContain($sub2->id);
    expect($submissions->pluck('id'))->not->toContain($sub1->id);
});

test('member dashboard displays active loan progress, statement link, guarantors, and pending requests', function () {
    $user = User::factory()->create();
    $member = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'M-TEST-1',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => $user->email,
        'phone' => '12345',
        'participates_in_savings' => true,
    ]);

    $guarantor = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'M-GUAR-1',
        'first_name' => 'Guar',
        'last_name' => 'One',
        'email' => 'guar1@example.com',
        'phone' => '54321',
        'participates_in_savings' => true,
    ]);

    // Create an active loan
    $loan = \App\Models\LoanRequest::create([
        'organization_id' => $this->org->id,
        'member_id' => $member->id,
        'amount' => 1000.00,
        'remaining_balance' => 800.00,
        'duration_months' => 12,
        'status' => 'active',
        'purpose' => 'Test Loan',
    ]);

    // Assign guarantor
    \App\Models\LoanGuarantor::create([
        'loan_request_id' => $loan->id,
        'guarantor_member_id' => $guarantor->id,
        'status' => 'approved',
    ]);

    // Create a pending savings deposit request
    $savingsReq = \App\Models\SavingsDepositRequest::create([
        'organization_id' => $this->org->id,
        'member_id' => $member->id,
        'amount' => 250.00,
        'screenshot_path' => 'screenshots/savings.png',
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    // Create a pending loan repayment request
    $repayReq = \App\Models\LoanRepaymentRequest::create([
        'organization_id' => $this->org->id,
        'member_id' => $member->id,
        'loan_request_id' => $loan->id,
        'amount' => 150.00,
        'screenshot_path' => 'screenshots/repay.png',
        'status' => 'pending',
        'payment_date' => now()->toDateString(),
        'payment_method' => 'zelle',
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);

    // Verify relations and variables passed to view
    $response->assertViewHas('activeLoans');
    $response->assertViewHas('pendingSavingsRequests');
    $response->assertViewHas('pendingRepayRequests');
    $response->assertViewHas('pendingLoanRequests');

    $activeLoans = $response->viewData('activeLoans');
    expect($activeLoans->first()->id)->toEqual($loan->id);
    expect($activeLoans->first()->guarantors)->not->toBeEmpty();
    expect($activeLoans->first()->guarantors->first()->guarantorMember->id)->toEqual($guarantor->id);

    $pendingSavings = $response->viewData('pendingSavingsRequests');
    expect($pendingSavings->pluck('id'))->toContain($savingsReq->id);

    $pendingRepays = $response->viewData('pendingRepayRequests');
    expect($pendingRepays->pluck('id'))->toContain($repayReq->id);

    // Verify HTML has the relevant content
    $response->assertSee('Active Loan Progress');
    $response->assertSee('Remaining Balance');
    $response->assertSee('Guarantors');
    $response->assertSee('Guar One');
    $response->assertSee('View & Print Statement', false);
    $response->assertSee('My Pending Requests');
    $response->assertSee('Savings Deposit');
    $response->assertSee('Loan Repayment');
    $response->assertSee('Active Loan Balance');
    $response->assertSee('Pending Requests');
});

test('member not enrolled in Njangi cycle can access dashboard and see savings and active loan metrics', function () {
    $user = User::factory()->create();
    $member = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'M-TEST-2',
        'first_name' => 'SavingsOnly',
        'last_name' => 'Member',
        'email' => $user->email,
        'phone' => '123456',
        'participates_in_savings' => true,
        'participates_in_njangi' => false,
    ]);

    // Give some savings balance
    \App\Models\SavingsTransaction::create([
        'organization_id' => $this->org->id,
        'member_id' => $member->id,
        'amount' => 600.00,
        'type' => 'deposit',
        'status' => 'approved',
        'transaction_date' => now()->toDateString(),
    ]);

    // Active loan
    $loan = \App\Models\LoanRequest::create([
        'organization_id' => $this->org->id,
        'member_id' => $member->id,
        'amount' => 500.00,
        'remaining_balance' => 500.00,
        'duration_months' => 6,
        'status' => 'active',
        'purpose' => 'Short Loan',
    ]);

    // We do NOT enroll the member in any NjangiCycle (no NjangiCycleMember creation)

    $response = $this->actingAs($user)->get('/dashboard');
    
    $response->assertStatus(200);
    $response->assertViewIs('dashboard.member');

    // Njangi warning banner should be shown
    $response->assertSee('Not Enrolled in Njangi');

    // Savings balance and active loan card should be visible
    $response->assertSee('$600.00');
    $response->assertSee('Active Loan Progress');
    $response->assertSee('$500.00');
    $response->assertSee('Active Loan Balance');
    $response->assertSee('Pending Requests');

    // Njangi-specific stats cards should NOT be visible
    $response->assertDontSee('Benefit Position');
    $response->assertDontSee('My Payout Date');

    // Submit Njangi Play form should NOT be visible
    $response->assertDontSee('Submit Njangi Play');
});




