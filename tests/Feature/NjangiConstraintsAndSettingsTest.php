<?php

use App\Models\User;
use App\Models\Member;
use App\Models\NjangiCycle;
use App\Models\NjangiCycleMember;
use App\Models\NjangiSession;
use App\Models\NjangiSessionBeneficiary;
use App\Models\Organization;
use App\Models\Setting;

beforeEach(function () {
    $this->org = Organization::create(['name' => 'Constraint test org']);
    $this->adminUser = User::factory()->create();
});

test('only members with participates_in_njangi = true can be enrolled in a cycle via addMembers', function () {
    $cycle = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 2026',
        'year' => 2026,
        'status' => 'draft',
    ]);

    // Create 1 participating member and 1 non-participating member
    $m1 = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'M-YES',
        'first_name' => 'Yes',
        'last_name' => 'Njangi',
        'email' => 'yes@njangi.com',
        'phone' => '123',
        'participates_in_njangi' => true,
    ]);

    $m2 = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'M-NO',
        'first_name' => 'No',
        'last_name' => 'Njangi',
        'email' => 'no@njangi.com',
        'phone' => '456',
        'participates_in_njangi' => false,
    ]);

    // Run add-members request
    $response = $this->actingAs($this->adminUser)
        ->post(route('njangi-cycles.add-members', $cycle));

    $response->assertRedirect(route('njangi-cycles.show', $cycle->id));

    // Assert only m1 is added to the cycle members list
    $cycleMembers = NjangiCycleMember::where('njangi_cycle_id', $cycle->id)->get();
    expect($cycleMembers)->toHaveCount(1);
    expect($cycleMembers->first()->member_id)->toEqual($m1->id);
});

test('session beneficiary count threshold is validated dynamically based on settings table', function () {
    Setting::query()->update(['beneficiary_count' => 5]);

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

    // Add 4 members and enroll them
    $cmIds = [];
    for ($i = 1; $i <= 4; $i++) {
        $m = Member::create(['organization_id' => $this->org->id, 'member_code' => "M$i", 'first_name' => "Name$i", 'last_name' => 'L', 'email' => "m$i@test.com", 'phone' => "$i"]);
        $cm = NjangiCycleMember::create(['organization_id' => $this->org->id, 'njangi_cycle_id' => $cycle->id, 'member_id' => $m->id, 'is_active' => true]);
        $cmIds[] = $cm->id;
    }

    // Try to update with 4 beneficiaries (required 5 based on settings)
    $response = $this->actingAs($this->adminUser)
        ->from("/njangi-sessions/{$session->id}/beneficiaries")
        ->post("/njangi-sessions/{$session->id}/beneficiaries", [
            'cycle_member_ids' => $cmIds
        ]);

    $response->assertRedirect("/njangi-sessions/{$session->id}/beneficiaries");
    $response->assertSessionHas('error', 'A minimum of 5 beneficiaries must be selected for this session.');
});

test('single benefit per cycle constraint is validated dynamically based on settings table', function () {
    Setting::query()->update(['single_benefit_constraint' => true, 'beneficiary_count' => 1]);

    $cycle = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 2026',
        'year' => 2026,
        'status' => 'active',
    ]);

    $session1 = NjangiSession::create([
        'organization_id' => $this->org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 1,
        'session_date' => now(),
        'status' => 'open',
    ]);

    $session2 = NjangiSession::create([
        'organization_id' => $this->org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 2,
        'session_date' => now()->addMonth(),
        'status' => 'open',
    ]);

    $m1 = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M1', 'first_name' => 'Alice', 'last_name' => 'Smith', 'email' => 'alice@test.com', 'phone' => '111']);
    $cm1 = NjangiCycleMember::create(['organization_id' => $this->org->id, 'njangi_cycle_id' => $cycle->id, 'member_id' => $m1->id, 'is_active' => true]);

    // Mark cm1 as beneficiary in session1
    NjangiSessionBeneficiary::create([
        'organization_id' => $this->org->id,
        'njangi_session_id' => $session1->id,
        'njangi_cycle_member_id' => $cm1->id,
        'beneficiary_slot' => 1,
        'benefit_order' => 1,
    ]);

    // Now try to select cm1 as beneficiary in session2
    $response = $this->actingAs($this->adminUser)
        ->from("/njangi-sessions/{$session2->id}/beneficiaries")
        ->post("/njangi-sessions/{$session2->id}/beneficiaries", [
            'cycle_member_ids' => [$cm1->id]
        ]);

    $response->assertRedirect("/njangi-sessions/{$session2->id}/beneficiaries");
    $response->assertSessionHas('error'); // should contain "already benefited" message
    
    // Now disable the constraint in settings
    Setting::query()->update(['single_benefit_constraint' => false]);

    // Try again
    $responseSuccess = $this->actingAs($this->adminUser)
        ->from("/njangi-sessions/{$session2->id}/beneficiaries")
        ->post("/njangi-sessions/{$session2->id}/beneficiaries", [
            'cycle_member_ids' => [$cm1->id]
        ]);

    $responseSuccess->assertRedirect(route('njangi-cycles.show', $cycle->id));
});
