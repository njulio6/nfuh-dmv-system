<?php

use App\Models\User;
use App\Models\Member;
use App\Models\MemberRole;
use App\Models\Organization;
use App\Models\NjangiCycle;
use App\Models\NjangiCycleMember;
use App\Models\NjangiSession;
use App\Models\NjangiSessionBeneficiary;

beforeEach(function () {
    $this->org = Organization::create(['name' => 'NFUH DMV System Test']);
});

test('guest is redirected to login from beneficiaries page', function () {
    $cycle = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 2026',
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

    $response = $this->get("/njangi-sessions/{$session->id}/beneficiaries");
    $response->assertRedirect('/login');

    $responsePost = $this->post("/njangi-sessions/{$session->id}/beneficiaries", ['cycle_member_ids' => []]);
    $responsePost->assertRedirect('/login');
});

test('regular member is forbidden from managing beneficiaries', function () {
    $user = User::factory()->create();
    $member = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'M-REG',
        'first_name' => 'Regular',
        'last_name' => 'Member',
        'email' => $user->email,
        'phone' => '1234567890',
    ]);

    $cycle = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 2026',
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

    $response = $this->actingAs($user)->get("/njangi-sessions/{$session->id}/beneficiaries");
    $response->assertStatus(403);

    $responsePost = $this->actingAs($user)->post("/njangi-sessions/{$session->id}/beneficiaries", [
        'cycle_member_ids' => [1, 2, 3, 4]
    ]);
    $responsePost->assertStatus(403);
});

test('global admin (user without a member profile) can access beneficiaries page', function () {
    $user = User::factory()->create();
    // No Member record for this email

    $cycle = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 2026',
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

    $response = $this->actingAs($user)->get("/njangi-sessions/{$session->id}/beneficiaries");
    $response->assertStatus(200);
    $response->assertViewIs('njangi.sessions.beneficiaries');
});

test('admin member (with Treasurer role) can access beneficiaries page', function () {
    $user = User::factory()->create();
    $member = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'M-TREAS',
        'first_name' => 'Treasurer',
        'last_name' => 'Member',
        'email' => $user->email,
        'phone' => '1234567891',
    ]);

    $role = MemberRole::firstOrCreate(['name' => 'Treasurer']);
    $member->roles()->sync([$role->id]);

    $cycle = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 2026',
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

    $response = $this->actingAs($user)->get("/njangi-sessions/{$session->id}/beneficiaries");
    $response->assertStatus(200);
});

test('updating with zero beneficiaries triggers a validation error redirecting back', function () {
    $user = User::factory()->create();

    // Set beneficiary_count to 1 dynamically for this test
    \App\Models\Setting::query()->update(['beneficiary_count' => 1]);

    $cycle = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 2026',
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

    $m1 = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M1', 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com', 'phone' => '1']);
    $cm1 = NjangiCycleMember::create(['organization_id' => $this->org->id, 'njangi_cycle_id' => $cycle->id, 'member_id' => $m1->id, 'is_active' => true]);

    $response = $this->actingAs($user)
        ->from("/njangi-sessions/{$session->id}/beneficiaries")
        ->post("/njangi-sessions/{$session->id}/beneficiaries", [
            'cycle_member_ids' => [] // 0 members selected (requires min 1)
        ]);

    $response->assertRedirect("/njangi-sessions/{$session->id}/beneficiaries");
    $response->assertSessionHas('error', 'A minimum of 1 beneficiary must be selected for this session.');
    
    // Assert no beneficiaries are in the DB for this session
    expect(NjangiSessionBeneficiary::where('njangi_session_id', $session->id)->count())->toEqual(0);
});

test('updating with valid beneficiaries successfully updates database and redirects', function () {
    $user = User::factory()->create();

    $cycle = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 2026',
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

    $m1 = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M1', 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com', 'phone' => '1']);
    $m2 = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M2', 'first_name' => 'C', 'last_name' => 'D', 'email' => 'c@d.com', 'phone' => '2']);
    $m3 = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M3', 'first_name' => 'E', 'last_name' => 'F', 'email' => 'e@f.com', 'phone' => '3']);
    $m4 = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M4', 'first_name' => 'G', 'last_name' => 'H', 'email' => 'g@h.com', 'phone' => '4']);
    $m5 = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M5', 'first_name' => 'I', 'last_name' => 'J', 'email' => 'i@j.com', 'phone' => '5']);

    $cm1 = NjangiCycleMember::create(['organization_id' => $this->org->id, 'njangi_cycle_id' => $cycle->id, 'member_id' => $m1->id, 'is_active' => true, 'benefit_order' => 1]);
    $cm2 = NjangiCycleMember::create(['organization_id' => $this->org->id, 'njangi_cycle_id' => $cycle->id, 'member_id' => $m2->id, 'is_active' => true, 'benefit_order' => 2]);
    $cm3 = NjangiCycleMember::create(['organization_id' => $this->org->id, 'njangi_cycle_id' => $cycle->id, 'member_id' => $m3->id, 'is_active' => true, 'benefit_order' => 3]);
    $cm4 = NjangiCycleMember::create(['organization_id' => $this->org->id, 'njangi_cycle_id' => $cycle->id, 'member_id' => $m4->id, 'is_active' => true, 'benefit_order' => 4]);
    $cm5 = NjangiCycleMember::create(['organization_id' => $this->org->id, 'njangi_cycle_id' => $cycle->id, 'member_id' => $m5->id, 'is_active' => true, 'benefit_order' => 5]);

    // Pre-insert some beneficiary to make sure deletes old ones
    NjangiSessionBeneficiary::create([
        'organization_id' => $this->org->id,
        'njangi_session_id' => $session->id,
        'njangi_cycle_member_id' => $cm5->id,
        'beneficiary_slot' => 1,
        'benefit_order' => 5,
    ]);

    $response = $this->actingAs($user)
        ->from("/njangi-sessions/{$session->id}/beneficiaries")
        ->post("/njangi-sessions/{$session->id}/beneficiaries", [
            'cycle_member_ids' => [$cm1->id, $cm2->id, $cm3->id, $cm4->id]
        ]);

    $response->assertRedirect(route('njangi-cycles.show', $cycle->id));
    $response->assertSessionHas('success', 'Session beneficiaries updated successfully.');

    // Assert database contents
    $beneficiaries = NjangiSessionBeneficiary::where('njangi_session_id', $session->id)
        ->orderBy('beneficiary_slot')
        ->get();

    expect($beneficiaries)->toHaveCount(4);
    expect($beneficiaries[0]->njangi_cycle_member_id)->toEqual($cm1->id);
    expect($beneficiaries[0]->beneficiary_slot)->toEqual(1);
    expect($beneficiaries[0]->benefit_order)->toEqual(1);

    expect($beneficiaries[1]->njangi_cycle_member_id)->toEqual($cm2->id);
    expect($beneficiaries[1]->beneficiary_slot)->toEqual(2);
    expect($beneficiaries[1]->benefit_order)->toEqual(2);

    expect($beneficiaries[2]->njangi_cycle_member_id)->toEqual($cm3->id);
    expect($beneficiaries[2]->beneficiary_slot)->toEqual(3);
    expect($beneficiaries[2]->benefit_order)->toEqual(3);

    expect($beneficiaries[3]->njangi_cycle_member_id)->toEqual($cm4->id);
    expect($beneficiaries[3]->beneficiary_slot)->toEqual(4);
    expect($beneficiaries[3]->benefit_order)->toEqual(4);

    // Assert that the old one (cm5) was deleted
    expect(NjangiSessionBeneficiary::where('njangi_session_id', $session->id)->where('njangi_cycle_member_id', $cm5->id)->count())->toEqual(0);
});
