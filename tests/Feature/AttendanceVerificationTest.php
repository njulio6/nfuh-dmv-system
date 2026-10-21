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
        'session_date' => now()->toDateString(),
        'title' => 'General Assembly Session 1',
        'status' => 'open',
        'check_in_opens_at' => '17:00:00',
        'check_in_closes_at' => '21:00:00',
        'check_in_timezone' => 'America/New_York',
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

test('regular member cannot access the officer roll-call desk (403 forbidden)', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createAttendanceTestContext();

    $this->actingAs($user)->get(route('attendance.desk'))->assertStatus(403);
    $this->actingAs($user)->post(route('attendance.mark'), [
        'session_id' => $session->id,
        'member_id' => $member->id,
        'status' => 'present',
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
        'reason' => 'Prior approved family bereavement.',
    ]);

    $excuseResponse->assertRedirect();
    $this->assertDatabaseHas('meeting_attendances', [
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'attendance_status' => 'excused',
        'officer_notes' => 'Formal letter of absence accepted by executives.',
        'verified_by' => $adminUser->id,
    ]);

    // Verify audit log was recorded
    $this->assertDatabaseHas('attendance_audit_logs', [
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'changed_by' => $adminUser->id,
        'new_status' => 'excused',
        'reason' => 'Prior approved family bereavement.',
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
        'attendance_status' => 'present',
        'check_in_source' => 'qr_self_check_in',
        'check_in_at' => now(),
    ]);

    expect(fn() => MeetingAttendance::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'attendance_status' => 'absent',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
