<?php

use App\Models\Member;
use App\Models\MemberRank;
use App\Models\NjangiCycle;
use App\Models\NjangiSession;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use App\Services\Njangi\GenerateNjangiSessions;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('admin can update meeting schedule settings in njangi settings', function () {
    Role::firstOrCreate(['name' => 'admin']);
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)->post(route('settings.update'), [
        'app_name' => 'NFUH DMV Test',
        'beneficiary_count' => 4,
        'min_savings_for_loan' => 500.00,
        'loan_guarantor_min' => 1,
        'loan_guarantor_max' => 3,
        'session_schedule_ordinal' => 'first',
        'session_schedule_weekday' => 'Saturday',
    ])->assertRedirect();

    $settings = Setting::first();
    expect($settings->session_schedule_ordinal)->toBe('first')
        ->and($settings->session_schedule_weekday)->toBe('Saturday');
});

test('session generator automatically schedules sessions on configured first saturday', function () {
    $org = Organization::firstOrCreate(['name' => 'NFUH DMV Org']);
    $setting = Setting::firstOrCreate([]);
    $setting->update([
        'session_schedule_ordinal' => 'first',
        'session_schedule_weekday' => 'Saturday',
    ]);

    $cycle = NjangiCycle::create([
        'organization_id' => $org->id,
        'name' => '2026 Yearly Cycle',
        'year' => 2026,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'active',
    ]);

    // Create a cycle member with benefit_order
    $rank = MemberRank::firstOrCreate(['name' => 'Nformi', 'level' => 1]);
    $user = User::factory()->create(['organization_id' => $org->id]);
    $member = Member::create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'member_code' => 'MEM-001',
        'first_name' => 'David',
        'last_name' => 'Njoh',
        'phone' => '2405550001',
        'rank_id' => $rank->id,
        'status' => 'active',
    ]);

    \Illuminate\Support\Facades\DB::table('njangi_cycle_members')->insert([
        'njangi_cycle_id' => $cycle->id,
        'member_id' => $member->id,
        'benefit_order' => 1,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $generator = new GenerateNjangiSessions();
    $generator->execute($cycle);

    $novemberSession = NjangiSession::where('njangi_cycle_id', $cycle->id)
        ->where('title', 'like', 'November%')
        ->first();

    expect($novemberSession)->not->toBeNull()
        ->and($novemberSession->session_date->toDateString())->toBe('2026-11-07')
        ->and($novemberSession->session_date->format('l'))->toBe('Saturday');
});

test('check-in window evaluated before meeting date returns not_started status with dynamic timezone', function () {
    $org = Organization::firstOrCreate(['name' => 'NFUH DMV Org']);
    $cycle = NjangiCycle::create([
        'organization_id' => $org->id,
        'name' => '2026 Cycle',
        'year' => 2026,
        'status' => 'active',
    ]);

    $session = NjangiSession::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 11,
        'session_date' => '2026-11-07', // Saturday Nov 7, 2026
        'title' => 'November Session',
        'status' => 'open',
        'check_in_opens_at' => '17:00:00',
        'check_in_closes_at' => '21:00:00',
        'check_in_timezone' => 'America/New_York',
    ]);

    // 1. Accessed days before (e.g. Nov 2, 2026)
    $beforeDate = Carbon::parse('2026-11-02 12:00:00', 'America/New_York');
    $statusBefore = $session->checkInWindowStatus($beforeDate);

    expect($statusBefore['status'])->toBe('not_started')
        ->and($statusBefore['is_open'])->toBeFalse()
        ->and($statusBefore['timezone_abbr'])->toBe('EST')
        ->and($statusBefore['closes_at']->format('g:i A T'))->toBe('9:00 PM EST');

    // 2. Accessed on meeting date before 5 PM (e.g. Nov 7, 2026 2:00 PM)
    $meetingDayBeforeOpen = Carbon::parse('2026-11-07 14:00:00', 'America/New_York');
    $statusMeetingDayEarly = $session->checkInWindowStatus($meetingDayBeforeOpen);
    expect($statusMeetingDayEarly['status'])->toBe('not_started')
        ->and($statusMeetingDayEarly['is_open'])->toBeFalse();

    // 3. Accessed during window (Nov 7, 2026 6:30 PM)
    $duringWindow = Carbon::parse('2026-11-07 18:30:00', 'America/New_York');
    $statusOpen = $session->checkInWindowStatus($duringWindow);
    expect($statusOpen['status'])->toBe('open')
        ->and($statusOpen['is_open'])->toBeTrue();

    // 4. Accessed after cutoff (Nov 7, 2026 9:15 PM)
    $afterCutoff = Carbon::parse('2026-11-07 21:15:00', 'America/New_York');
    $statusClosed = $session->checkInWindowStatus($afterCutoff);
    expect($statusClosed['status'])->toBe('closed')
        ->and($statusClosed['is_open'])->toBeFalse();
});

test('server-side validation strictly blocks check-in attempt before meeting date', function () {
    Role::firstOrCreate(['name' => 'member']);
    $org = Organization::firstOrCreate(['name' => 'NFUH DMV Org']);
    $cycle = NjangiCycle::create([
        'organization_id' => $org->id,
        'name' => '2026 Cycle',
        'year' => 2026,
        'status' => 'active',
    ]);

    // Meeting is in the future
    $session = NjangiSession::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 11,
        'session_date' => Carbon::now('America/New_York')->addDays(10)->toDateString(),
        'title' => 'Future Session',
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
        'member_code' => 'MEM-202',
        'first_name' => 'Beatrice',
        'last_name' => 'Enow',
        'phone' => '2405550202',
        'rank_id' => $rank->id,
        'status' => 'active',
    ]);

    // Member attempt
    $response = $this->actingAs($user)->post(route('attendance.checkin.member', $session->qr_token));
    $response->assertSessionHas('error');
    $errorMessage = session('error');
    expect($errorMessage)->toContain('Check-in is not yet open');

    // Guest attempt
    $guestResponse = $this->post(route('attendance.checkin.guest', $session->qr_token), [
        'guest_name' => 'Visitor John',
    ]);
    $guestResponse->assertSessionHas('error');
    expect(session('error'))->toContain('Check-in is not yet open');
});
