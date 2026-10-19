<?php

use App\Models\Member;
use App\Models\MeetingAttendance;
use App\Models\NjangiCycle;
use App\Models\NjangiSession;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createAttendanceTestContext(): array
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
        'session_date' => now()->addDays(5)->toDateString(),
        'title' => 'General Assembly Session 1',
        'status' => 'open',
    ]);

    $user = User::factory()->create(['organization_id' => $org->id]);
    $user->assignRole('member');

    $member = Member::create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'member_code' => 'MEM-101',
        'first_name' => 'Alain',
        'last_name' => 'Fokam',
        'phone' => '2405550101',
        'status' => 'active',
    ]);

    $adminUser = User::factory()->create(['organization_id' => $org->id]);
    $adminUser->assignRole('admin');

    return [$org, $cycle, $session, $user, $member, $adminUser];
}

test('member can submit and update their RSVP status for an upcoming session', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createAttendanceTestContext();

    $response = $this->actingAs($user)->post(route('member.attendance.rsvp'), [
        'session_id' => $session->id,
        'rsvp_status' => 'attending',
        'rsvp_notes' => 'Looking forward to meeting everyone.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('meeting_attendances', [
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'rsvp_status' => 'attending',
        'rsvp_notes' => 'Looking forward to meeting everyone.',
    ]);

    // Member updates RSVP to not_attending
    $updateResponse = $this->actingAs($user)->post(route('member.attendance.rsvp'), [
        'session_id' => $session->id,
        'rsvp_status' => 'not_attending',
        'rsvp_notes' => 'Sudden travel schedule conflict.',
    ]);

    $updateResponse->assertRedirect();
    $this->assertDatabaseHas('meeting_attendances', [
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'rsvp_status' => 'not_attending',
        'rsvp_notes' => 'Sudden travel schedule conflict.',
    ]);
});

test('member cannot submit RSVP for a closed or cancelled session', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createAttendanceTestContext();

    $session->update(['status' => 'closed']);

    $response = $this->actingAs($user)->post(route('member.attendance.rsvp'), [
        'session_id' => $session->id,
        'rsvp_status' => 'attending',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $this->assertDatabaseMissing('meeting_attendances', [
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
    ]);
});

test('regular member cannot access the officer roll-call desk (403 forbidden)', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createAttendanceTestContext();

    $this->actingAs($user)->get(route('attendance.desk'))->assertStatus(403);
    $this->actingAs($user)->post(route('attendance.mark'), [
        'session_id' => $session->id,
        'member_id' => $member->id,
        'status' => 'present',
    ])->assertStatus(403);
    $this->actingAs($user)->post(route('attendance.bulk-mark-rsvps'), [
        'session_id' => $session->id,
    ])->assertStatus(403);
});

test('authorized officer can access the roll-call desk', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createAttendanceTestContext();

    $response = $this->actingAs($adminUser)->get(route('attendance.desk'));
    $response->assertStatus(200);
    $response->assertSee('Official Attendance Desk');
    $response->assertSee('Alain Fokam');
});

test('officer can verify member attendance as Present, Absent, or Excused with audit timestamps', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createAttendanceTestContext();

    // Mark as Present
    $response = $this->actingAs($adminUser)->post(route('attendance.mark'), [
        'session_id' => $session->id,
        'member_id' => $member->id,
        'status' => 'present',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('meeting_attendances', [
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'attendance_status' => 'present',
        'verified_by' => $adminUser->id,
    ]);

    // Update to Excused with officer note
    $excuseResponse = $this->actingAs($adminUser)->post(route('attendance.mark'), [
        'session_id' => $session->id,
        'member_id' => $member->id,
        'status' => 'excused',
        'officer_notes' => 'Formal letter of absence accepted by executives.',
    ]);

    $excuseResponse->assertRedirect();
    $this->assertDatabaseHas('meeting_attendances', [
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'attendance_status' => 'excused',
        'officer_notes' => 'Formal letter of absence accepted by executives.',
        'verified_by' => $adminUser->id,
    ]);
});

test('bulk action marks all attending RSVPs as Present in one click', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createAttendanceTestContext();

    // Create second member with RSVP attending
    $user2 = User::factory()->create(['organization_id' => $org->id]);
    $member2 = Member::create([
        'organization_id' => $org->id,
        'user_id' => $user2->id,
        'member_code' => 'MEM-102',
        'first_name' => 'Beatrice',
        'last_name' => 'Mbah',
        'phone' => '2405550102',
        'status' => 'active',
    ]);

    MeetingAttendance::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'rsvp_status' => 'attending',
        'rsvp_at' => now(),
    ]);

    MeetingAttendance::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'member_id' => $member2->id,
        'rsvp_status' => 'attending',
        'rsvp_at' => now(),
    ]);

    $response = $this->actingAs($adminUser)->post(route('attendance.bulk-mark-rsvps'), [
        'session_id' => $session->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('meeting_attendances', [
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'attendance_status' => 'present',
    ]);
    $this->assertDatabaseHas('meeting_attendances', [
        'njangi_session_id' => $session->id,
        'member_id' => $member2->id,
        'attendance_status' => 'present',
    ]);
});

test('member portal displays verified attendance history and accurate attendance percentage', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createAttendanceTestContext();

    // Before any verified meetings, attendance rate is 0.0%
    expect($member->attendanceRate())->toBe(0.0);

    // Create session 2
    $session2 = NjangiSession::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 2,
        'session_date' => now()->subDays(10)->toDateString(),
        'title' => 'Past Assembly 2',
        'status' => 'closed',
    ]);

    // Member was present in session 2
    MeetingAttendance::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session2->id,
        'member_id' => $member->id,
        'attendance_status' => 'present',
        'verified_by' => $adminUser->id,
        'verified_at' => now(),
    ]);

    // Attendance rate should be 100%
    expect($member->attendanceRate())->toBe(100.0);

    $response = $this->actingAs($user)->get(route('member.attendance'));
    $response->assertStatus(200);
    $response->assertSee('My Meeting Attendance');
    $response->assertSee('100%');
});

test('session attendance unique constraint prevents duplicate rows for the same member and session', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createAttendanceTestContext();

    MeetingAttendance::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'rsvp_status' => 'attending',
        'rsvp_at' => now(),
    ]);

    expect(fn() => MeetingAttendance::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'rsvp_status' => 'not_attending',
        'rsvp_at' => now(),
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

