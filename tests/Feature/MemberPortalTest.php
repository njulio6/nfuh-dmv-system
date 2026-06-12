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

    $response->assertRedirect('/dashboard');
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

    $response = $this->actingAs($user)->from('/dashboard')->post('/member/submissions', [
        'njangi_session_id' => $session->id,
        'amount' => 400.00,
        'is_attending' => 1,
        'screenshot' => $screenshot,
        'member_note' => 'Another submission.',
    ]);

    $response->assertRedirect('/dashboard');
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

test('member dashboard submissions list only shows submissions for the selected cycle', function () {
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

    // Fetch dashboard without cycle_id query -> should load cycle1 and only show $sub1
    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
    $response->assertViewHas('submissions');
    $submissions = $response->viewData('submissions');
    expect($submissions->pluck('id'))->toContain($sub1->id);
    expect($submissions->pluck('id'))->not->toContain($sub2->id);

    // Fetch dashboard with cycle_id for cycle2 -> should load cycle2 and only show $sub2
    $response = $this->actingAs($user)->get('/dashboard?cycle_id=' . $cycle2->id);
    $response->assertStatus(200);
    $response->assertViewHas('submissions');
    $submissions = $response->viewData('submissions');
    expect($submissions->pluck('id'))->toContain($sub2->id);
    expect($submissions->pluck('id'))->not->toContain($sub1->id);
});


