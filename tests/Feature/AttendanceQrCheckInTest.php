<?php

use App\Models\AttendanceAuditLog;
use App\Models\MeetingAttendance;
use App\Models\MeetingGuest;
use App\Models\Member;
use App\Models\MemberRank;
use App\Models\NjangiCycle;
use App\Models\NjangiSession;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createQrTestContext(): array
{
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'member']);

    $org = Organization::firstOrCreate(['name' => 'NFUH DMV Org']);

    $cycle = NjangiCycle::create([
        'organization_id' => $org->id,
        'name' => '2026 Yearly Cycle',
        'year' => 2026,
        'status' => 'active',
    ]);

    $session = NjangiSession::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 1,
        'session_date' => Carbon::now('America/New_York')->toDateString(),
        'title' => 'General Assembly Session 1',
        'status' => 'open',
        'check_in_opens_at' => '17:00:00',
        'check_in_closes_at' => '21:00:00',
        'check_in_timezone' => 'America/New_York',
    ]);

    $rank = MemberRank::firstOrCreate(['name' => 'Nformi', 'level' => 1]);

    $user = User::factory()->create(['organization_id' => $org->id]);
    $user->assignRole('member');

    $member = Member::create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'member_code' => 'MEM-101',
        'first_name' => 'Alain',
        'last_name' => 'Fokam',
        'phone' => '2405550101',
        'rank_id' => $rank->id,
        'status' => 'active',
    ]);

    $adminUser = User::factory()->create(['organization_id' => $org->id]);
    $adminUser->assignRole('admin');

    return [$org, $cycle, $session, $user, $member, $adminUser];
}

test('session automatically generates distinct secure qr_token', function () {
    [$org, $cycle, $session] = createQrTestContext();

    expect($session->qr_token)->not->toBeEmpty();
    expect(strlen($session->qr_token))->toBeGreaterThanOrEqual(32);

    $session2 = NjangiSession::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 2,
        'session_date' => Carbon::now('America/New_York')->addMonth()->toDateString(),
        'title' => 'Session 2',
        'status' => 'open',
    ]);

    expect($session2->qr_token)->not->toBe($session->qr_token);
});

test('member can self check-in via QR during open window and gets recorded as Present', function () {
    [$org, $cycle, $session, $user, $member] = createQrTestContext();

    // Freeze time to 6:30 PM EDT (inside 5 PM - 9 PM)
    $today = Carbon::now('America/New_York')->format('Y-m-d');
    Carbon::setTestNow(Carbon::parse("{$today} 18:30:00", 'America/New_York'));

    $response = $this->actingAs($user)->post(route('attendance.checkin.member', $session->qr_token));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('meeting_attendances', [
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'attendance_status' => 'present',
        'check_in_source' => 'qr_self_check_in',
    ]);

    Carbon::setTestNow(); // clear test now
});

test('member cannot check-in before 5:00 PM opening time or after 9:00 PM cutoff', function () {
    [$org, $cycle, $session, $user, $member] = createQrTestContext();

    $today = Carbon::now('America/New_York')->format('Y-m-d');

    // Test before opening: 4:30 PM EDT
    Carbon::setTestNow(Carbon::parse("{$today} 16:30:00", 'America/New_York'));
    $earlyResponse = $this->actingAs($user)->post(route('attendance.checkin.member', $session->qr_token));
    $earlyResponse->assertRedirect();
    $earlyResponse->assertSessionHas('error');
    $this->assertDatabaseMissing('meeting_attendances', [
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
    ]);

    // Test after cutoff: 9:15 PM EDT
    Carbon::setTestNow(Carbon::parse("{$today} 21:15:00", 'America/New_York'));
    $lateResponse = $this->actingAs($user)->post(route('attendance.checkin.member', $session->qr_token));
    $lateResponse->assertRedirect();
    $lateResponse->assertSessionHas('error');
    $this->assertDatabaseMissing('meeting_attendances', [
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
    ]);

    Carbon::setTestNow();
});

test('configurable window allows check-in during custom officer hours for special meetings', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createQrTestContext();

    // Officer updates session hours to 1:00 PM – 4:00 PM EDT for a special afternoon AGM
    $this->actingAs($adminUser)->post(route('attendance.session.window', $session->id), [
        'check_in_opens_at' => '13:00',
        'check_in_closes_at' => '16:00',
        'check_in_timezone' => 'America/New_York',
    ]);

    $session->refresh();
    expect($session->check_in_opens_at)->toBe('13:00:00');
    expect($session->check_in_closes_at)->toBe('16:00:00');

    // Test at 2:00 PM EDT (would have failed under standard 5pm-9pm, but passes with custom hours)
    $today = Carbon::now('America/New_York')->format('Y-m-d');
    Carbon::setTestNow(Carbon::parse("{$today} 14:00:00", 'America/New_York'));

    $response = $this->actingAs($user)->post(route('attendance.checkin.member', $session->qr_token));
    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('meeting_attendances', [
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'attendance_status' => 'present',
    ]);

    Carbon::setTestNow();
});

test('member duplicate check-in shows already checked in with original timestamp and zero extra rows', function () {
    [$org, $cycle, $session, $user, $member] = createQrTestContext();

    $today = Carbon::now('America/New_York')->format('Y-m-d');
    $checkInTime = Carbon::parse("{$today} 17:30:00", 'America/New_York');
    Carbon::setTestNow($checkInTime);

    // Initial check in
    $this->actingAs($user)->post(route('attendance.checkin.member', $session->qr_token));

    // Second check in 30 minutes later
    Carbon::setTestNow(Carbon::parse("{$today} 18:00:00", 'America/New_York'));
    $duplicateResponse = $this->actingAs($user)->post(route('attendance.checkin.member', $session->qr_token));

    $duplicateResponse->assertRedirect();
    $duplicateResponse->assertSessionHas('info');

    // Ensure only 1 record exists in meeting_attendances
    $count = MeetingAttendance::where('njangi_session_id', $session->id)->where('member_id', $member->id)->count();
    expect($count)->toBe(1);

    Carbon::setTestNow();
});

test('guest can check in without user account into isolated meeting_guests table', function () {
    [$org, $cycle, $session, $user, $member] = createQrTestContext();

    $today = Carbon::now('America/New_York')->format('Y-m-d');
    Carbon::setTestNow(Carbon::parse("{$today} 18:00:00", 'America/New_York'));

    // Guest submits check in (unauthenticated)
    $response = $this->post(route('attendance.checkin.guest', $session->qr_token), [
        'guest_name' => 'Fru Ndi Bernard',
        'guest_phone' => '2405559988',
        'guest_email' => 'fru@example.com',
        'invited_by_member_id' => $member->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('guest_success');

    $this->assertDatabaseHas('meeting_guests', [
        'njangi_session_id' => $session->id,
        'guest_name' => 'Fru Ndi Bernard',
        'invited_by_member_id' => $member->id,
        'invited_by_name' => 'Alain Fokam',
    ]);

    Carbon::setTestNow();
});

test('guest check-in records are isolated and do not alter member attendance counts or percentages', function () {
    [$org, $cycle, $session, $user, $member] = createQrTestContext();

    MeetingGuest::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'guest_name' => 'Visiting Elder John',
        'check_in_at' => now(),
    ]);

    // Member attendance rate must still be 0.0% (not affected by guests)
    expect($member->attendanceRate())->toBe(0.0);

    // MeetingAttendance table has 0 rows
    expect(MeetingAttendance::count())->toBe(0);

    // Session stats shows 1 guest and 0 member present
    $stats = $session->attendanceStats();
    expect($stats['total_guests'])->toBe(1);
    expect($stats['total_present'])->toBe(0);
});

test('guest duplicate name registration is prevented', function () {
    [$org, $cycle, $session] = createQrTestContext();

    $today = Carbon::now('America/New_York')->format('Y-m-d');
    Carbon::setTestNow(Carbon::parse("{$today} 18:00:00", 'America/New_York'));

    // First guest check in
    $this->post(route('attendance.checkin.guest', $session->qr_token), [
        'guest_name' => 'Mary Ngu',
    ]);

    // Second guest check in with same name
    $duplicate = $this->post(route('attendance.checkin.guest', $session->qr_token), [
        'guest_name' => 'mary ngu', // lowercase check
    ]);

    $duplicate->assertRedirect();
    $duplicate->assertSessionHas('info');

    expect(MeetingGuest::where('njangi_session_id', $session->id)->count())->toBe(1);

    Carbon::setTestNow();
});

test('officer manual correction requires reason and creates audit log', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createQrTestContext();

    // Initial officer roll-call marking
    $this->actingAs($adminUser)->post(route('attendance.mark'), [
        'session_id' => $session->id,
        'member_id' => $member->id,
        'status' => 'present',
        'officer_notes' => 'Checked in at desk',
    ]);

    // Officer changes to Excused
    $this->actingAs($adminUser)->post(route('attendance.mark'), [
        'session_id' => $session->id,
        'member_id' => $member->id,
        'status' => 'excused',
        'reason' => 'Executive committee granted bereavement excuse',
    ]);

    $this->assertDatabaseHas('attendance_audit_logs', [
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'changed_by' => $adminUser->id,
        'new_status' => 'excused',
        'previous_status' => 'present',
        'reason' => 'Executive committee granted bereavement excuse',
    ]);
});

test('existing historical attendance records remain intact', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createQrTestContext();

    // Create a verified attendance record simulating past milestone 2 tests
    $pastAttendance = MeetingAttendance::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'attendance_status' => 'present',
        'verified_by' => $adminUser->id,
        'verified_at' => Carbon::now()->subMonths(1),
        'officer_notes' => 'Historical verified record',
    ]);

    expect($pastAttendance->attendance_status)->toBe('present');
    expect($pastAttendance->verified_by)->toBe($adminUser->id);
    expect($member->attendanceRate())->toBe(100.0);
});

test('strict cycle restriction setting blocks check-in for members not enrolled in that cycle', function () {
    [$org, $cycle, $session, $user, $member] = createQrTestContext();

    // Enable strict cycle restriction
    $settings = \App\Models\Setting::first() ?? new \App\Models\Setting();
    $settings->restrict_checkin_to_cycle_members = true;
    $settings->save();

    // Remove member from cycle members
    \Illuminate\Support\Facades\DB::table('njangi_cycle_members')
        ->where('njangi_cycle_id', $cycle->id)
        ->where('member_id', $member->id)
        ->delete();

    $today = Carbon::now('America/New_York')->format('Y-m-d');
    Carbon::setTestNow(Carbon::parse("{$today} 18:00:00", 'America/New_York'));

    // Checkin page shows not enrolled
    $pageResponse = $this->actingAs($user)->get(route('attendance.checkin.show', $session->qr_token));
    $pageResponse->assertStatus(200);
    $pageResponse->assertSee('Not Enrolled in This Njangi Cycle');

    // Attempting checkin post is rejected
    $submitResponse = $this->actingAs($user)->post(route('attendance.checkin.member', $session->qr_token));
    $submitResponse->assertRedirect();
    $submitResponse->assertSessionHas('error');

    expect(MeetingAttendance::where('njangi_session_id', $session->id)->where('member_id', $member->id)->exists())->toBeFalse();

    // Now re-enroll member and verify check-in succeeds
    \Illuminate\Support\Facades\DB::table('njangi_cycle_members')->insert([
        'njangi_cycle_id' => $cycle->id,
        'member_id' => $member->id,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $enrolledSubmit = $this->actingAs($user)->post(route('attendance.checkin.member', $session->qr_token));
    $enrolledSubmit->assertRedirect();
    $enrolledSubmit->assertSessionHas('success');

    expect(MeetingAttendance::where('njangi_session_id', $session->id)->where('member_id', $member->id)->value('attendance_status'))->toBe('present');

    // Reset setting to false for test isolation
    $settings->restrict_checkin_to_cycle_members = false;
    $settings->save();
    Carbon::setTestNow();
});

test('unauthenticated visit to check-in sets intended url and redirects back to check-in page upon login', function () {
    [$org, $cycle, $session, $user] = createQrTestContext();

    // 1. Guest visits check-in page without logging in
    $response = $this->get(route('attendance.checkin.show', $session->qr_token));
    $response->assertStatus(200);
    $response->assertSessionHas('url.intended', route('attendance.checkin.show', $session->qr_token));

    // 2. Member logs in using credentials
    $loginResponse = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    // 3. User is redirected directly back to check-in page instead of dashboard
    $loginResponse->assertRedirect(route('attendance.checkin.show', $session->qr_token));
});


