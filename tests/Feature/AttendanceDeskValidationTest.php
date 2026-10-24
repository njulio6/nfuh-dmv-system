<?php

use App\Models\AttendanceAuditLog;
use App\Models\MeetingAttendance;
use App\Models\Member;
use App\Models\NjangiCycle;
use App\Models\NjangiSession;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createDeskValidationContext(bool $isFuture = true): array
{
    Permission::firstOrCreate(['name' => 'attendance.mark', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'attendance.clear_records', 'guard_name' => 'web']);

    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $adminRole->givePermissionTo(['attendance.mark', 'attendance.clear_records']);

    $officerRole = Role::firstOrCreate(['name' => 'General Secretary', 'guard_name' => 'web']);
    $officerRole->givePermissionTo(['attendance.mark']);

    $memberRole = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);

    $org = Organization::firstOrCreate(['name' => 'NFUH DMV Org']);

    $cycle = NjangiCycle::create([
        'organization_id' => $org->id,
        'name' => '2026 Yearly Cycle',
        'year' => 2026,
        'status' => 'active',
    ]);

    $sessionDate = $isFuture ? Carbon::now('America/New_York')->addDays(14)->toDateString() : Carbon::now('America/New_York')->toDateString();

    $session = NjangiSession::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 1,
        'session_date' => $sessionDate,
        'title' => 'November 2026 Assembly',
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
        'member_code' => 'MEM-202',
        'first_name' => 'Agnes',
        'last_name' => 'Tanyi',
        'phone' => '2405550202',
        'status' => 'active',
    ]);

    $adminUser = User::factory()->create(['organization_id' => $org->id]);
    $adminUser->assignRole('admin');

    $officerUser = User::factory()->create(['organization_id' => $org->id]);
    $officerUser->assignRole('General Secretary');

    return [$org, $cycle, $session, $user, $member, $adminUser, $officerUser];
}

test('marking present on future session without reason is rejected with 422', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createDeskValidationContext(true);

    $response = $this->actingAs($adminUser)->postJson(route('attendance.mark'), [
        'session_id' => $session->id,
        'member_id' => $member->id,
        'status' => 'present',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Regular roll-call cannot mark Present or Absent before the scheduled meeting date. An authorized override reason is required.',
        ]);

    expect(MeetingAttendance::where('njangi_session_id', $session->id)->where('member_id', $member->id)->exists())->toBeFalse();
});

test('advance roll-call override with valid reason succeeds on future session and logs audit trail', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createDeskValidationContext(true);

    $response = $this->actingAs($adminUser)->postJson(route('attendance.mark'), [
        'session_id' => $session->id,
        'member_id' => $member->id,
        'status' => 'present',
        'reason' => 'Pre-meeting system testing for roll-call',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $attendance = MeetingAttendance::where('njangi_session_id', $session->id)
        ->where('member_id', $member->id)
        ->first();

    expect($attendance)->not->toBeNull()
        ->and($attendance->attendance_status)->toBe('present')
        ->and($attendance->check_in_source)->toBe('early_officer_override')
        ->and($attendance->officer_notes)->toBe('Pre-meeting system testing for roll-call');

    $audit = AttendanceAuditLog::where('njangi_session_id', $session->id)
        ->where('member_id', $member->id)
        ->latest()
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->action)->toBe('early_officer_override')
        ->and($audit->new_status)->toBe('present');
});

test('marking excused on future session records early_officer_override with note', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createDeskValidationContext(true);

    $response = $this->actingAs($adminUser)->postJson(route('attendance.mark'), [
        'session_id' => $session->id,
        'member_id' => $member->id,
        'status' => 'excused',
        'officer_notes' => 'Travel conflict reported in advance',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $attendance = MeetingAttendance::where('njangi_session_id', $session->id)
        ->where('member_id', $member->id)
        ->first();

    expect($attendance)->not->toBeNull()
        ->and($attendance->attendance_status)->toBe('excused')
        ->and($attendance->check_in_source)->toBe('early_officer_override')
        ->and($attendance->officer_notes)->toBe('Travel conflict reported in advance');
});

test('officer can reset attendance status back to pending verification', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createDeskValidationContext(true);

    // First mark present
    MeetingAttendance::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'attendance_status' => 'present',
        'check_in_source' => 'officer_verification',
    ]);

    $response = $this->actingAs($adminUser)->postJson(route('attendance.mark'), [
        'session_id' => $session->id,
        'member_id' => $member->id,
        'status' => 'unverified',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $attendance = MeetingAttendance::where('njangi_session_id', $session->id)
        ->where('member_id', $member->id)
        ->first();

    expect($attendance->attendance_status)->toBeNull()
        ->and($attendance->check_in_source)->toBe('unverified');

    $audit = AttendanceAuditLog::where('njangi_session_id', $session->id)
        ->where('member_id', $member->id)
        ->latest()
        ->first();

    expect($audit->action)->toBe('status_reset_to_pending')
        ->and($audit->previous_status)->toBe('present')
        ->and($audit->new_status)->toBe('unverified');
});

test('clear session records is forbidden (403) for users without attendance.clear_records permission', function () {
    [$org, $cycle, $session, $user, $member, $adminUser, $officerUser] = createDeskValidationContext(true);

    $response = $this->actingAs($officerUser)->postJson(route('attendance.session.clear-records', $session->id), [
        'confirm_word' => 'Clear',
    ]);

    $response->assertStatus(403);
});

test('clear session records fails with 422 if confirmation challenge word is incorrect', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createDeskValidationContext(true);

    $response = $this->actingAs($adminUser)->postJson(route('attendance.session.clear-records', $session->id), [
        'confirm_word' => 'clear', // lowercase should fail strict challenge
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Confirmation failed. You must type the word "Clear" exactly to confirm this action.',
        ]);
});

test('clear session records resets all statuses to pending with exact challenge word Clear', function () {
    [$org, $cycle, $session, $user, $member, $adminUser] = createDeskValidationContext(true);

    // Create 2 test records
    MeetingAttendance::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'member_id' => $member->id,
        'attendance_status' => 'present',
    ]);

    $member2 = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'MEM-203',
        'first_name' => 'Eric',
        'last_name' => 'Tchinda',
        'phone' => '2405550203',
        'status' => 'active',
    ]);

    MeetingAttendance::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'member_id' => $member2->id,
        'attendance_status' => 'absent',
    ]);

    $response = $this->actingAs($adminUser)->postJson(route('attendance.session.clear-records', $session->id), [
        'confirm_word' => 'Clear',
        'reason' => 'Resetting pre-meeting test data',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'cleared_count' => 2,
        ]);

    $attendances = MeetingAttendance::where('njangi_session_id', $session->id)->get();
    foreach ($attendances as $att) {
        expect($att->attendance_status)->toBeNull();
    }

    $auditCount = AttendanceAuditLog::where('njangi_session_id', $session->id)
        ->where('action', 'session_records_cleared')
        ->count();

    expect($auditCount)->toBe(2);
});

test('checkin page displays scheduled meeting date in the opening badge', function () {
    [$org, $cycle, $session] = createDeskValidationContext(true);

    $response = $this->get(route('attendance.checkin.show', $session->qr_token));

    $response->assertStatus(200);
    $formattedDate = $session->session_date->format('D, M j, Y');
    $response->assertSee($formattedDate);
});
