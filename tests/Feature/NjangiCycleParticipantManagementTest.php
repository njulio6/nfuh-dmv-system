<?php

use App\Models\User;
use App\Models\Member;
use App\Models\NjangiCycle;
use App\Models\NjangiCycleMember;
use App\Models\NjangiSession;
use App\Models\NjangiSessionBeneficiary;
use App\Models\NjangiDisbursement;
use App\Models\Organization;
use App\Models\Setting;

beforeEach(function () {
    $this->org = Organization::create(['name' => 'Management test org']);
    $this->seed(\Database\Seeders\SettingsSeeder::class);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole('admin');
});

test('admins can add individual eligible member to a cycle and specify their benefit order', function () {
    $cycle = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 2026',
        'year' => 2026,
        'status' => 'draft',
    ]);

    $member = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'M-INDIV',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@test.com',
        'phone' => '111222',
        'participates_in_njangi' => true,
    ]);

    $response = $this->actingAs($this->adminUser)
        ->post(route('njangi-cycles.members.store', $cycle), [
            'member_id' => $member->id,
            'benefit_order' => 5,
        ]);

    $response->assertRedirect(route('njangi-cycles.show', $cycle->id));
    $response->assertSessionHas('success');

    $cycleMember = NjangiCycleMember::where('njangi_cycle_id', $cycle->id)
        ->where('member_id', $member->id)
        ->first();

    expect($cycleMember)->not->toBeNull();
    expect($cycleMember->benefit_order)->toEqual(5);
    expect($cycleMember->status)->toEqual('active');
});

test('admins cannot add individual member with duplicate benefit order in a cycle', function () {
    $cycle = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 2026',
        'year' => 2026,
        'status' => 'draft',
    ]);

    $m1 = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M1', 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@test.com', 'phone' => '1', 'participates_in_njangi' => true]);
    $m2 = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M2', 'first_name' => 'C', 'last_name' => 'D', 'email' => 'c@test.com', 'phone' => '2', 'participates_in_njangi' => true]);

    NjangiCycleMember::create([
        'organization_id' => $this->org->id,
        'njangi_cycle_id' => $cycle->id,
        'member_id' => $m1->id,
        'benefit_order' => 3,
    ]);

    // Try to add m2 with same benefit order 3
    $response = $this->actingAs($this->adminUser)
        ->post(route('njangi-cycles.members.store', $cycle), [
            'member_id' => $m2->id,
            'benefit_order' => 3,
        ]);

    $response->assertSessionHasErrors(['benefit_order']);
});

test('admins can bulk update member benefit orders and statuses', function () {
    $cycle = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 2026',
        'year' => 2026,
        'status' => 'draft',
    ]);

    $member1 = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M1', 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@test.com', 'phone' => '1', 'participates_in_njangi' => true]);
    $member2 = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M2', 'first_name' => 'C', 'last_name' => 'D', 'email' => 'c@test.com', 'phone' => '2', 'participates_in_njangi' => true]);
    
    $cm1 = NjangiCycleMember::create([
        'organization_id' => $this->org->id,
        'njangi_cycle_id' => $cycle->id,
        'member_id' => $member1->id,
        'benefit_order' => 1,
        'status' => 'active',
    ]);

    $cm2 = NjangiCycleMember::create([
        'organization_id' => $this->org->id,
        'njangi_cycle_id' => $cycle->id,
        'member_id' => $member2->id,
        'benefit_order' => 2,
        'status' => 'active',
    ]);

    // Perform bulk update swapping benefit orders and updating status
    $response = $this->actingAs($this->adminUser)
        ->put(route('njangi-cycles.members.bulk-update', $cycle), [
            'members' => [
                $cm1->id => [
                    'benefit_order' => 2,
                    'status' => 'suspended',
                ],
                $cm2->id => [
                    'benefit_order' => 1,
                    'status' => 'inactive',
                ],
            ]
        ]);

    $response->assertRedirect(route('njangi-cycles.show', $cycle->id));
    
    $cm1->refresh();
    $cm2->refresh();

    expect($cm1->benefit_order)->toEqual(2);
    expect($cm1->status)->toEqual('suspended');
    expect($cm2->benefit_order)->toEqual(1);
    expect($cm2->status)->toEqual('inactive');
});

test('admins cannot bulk update duplicate benefit orders', function () {
    $cycle = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 2026',
        'year' => 2026,
        'status' => 'draft',
    ]);

    $member1 = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M1', 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@test.com', 'phone' => '1', 'participates_in_njangi' => true]);
    $member2 = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M2', 'first_name' => 'C', 'last_name' => 'D', 'email' => 'c@test.com', 'phone' => '2', 'participates_in_njangi' => true]);
    
    $cm1 = NjangiCycleMember::create(['organization_id' => $this->org->id, 'njangi_cycle_id' => $cycle->id, 'member_id' => $member1->id, 'benefit_order' => 1, 'status' => 'active']);
    $cm2 = NjangiCycleMember::create(['organization_id' => $this->org->id, 'njangi_cycle_id' => $cycle->id, 'member_id' => $member2->id, 'benefit_order' => 2, 'status' => 'active']);

    $response = $this->actingAs($this->adminUser)
        ->put(route('njangi-cycles.members.bulk-update', $cycle), [
            'members' => [
                $cm1->id => [
                    'benefit_order' => 3,
                    'status' => 'active',
                ],
                $cm2->id => [
                    'benefit_order' => 3,
                    'status' => 'active',
                ],
            ]
        ]);

    $response->assertSessionHas('error', 'Duplicate benefit orders are not allowed within the same cycle.');
});

test('admins can remove a member from a draft cycle', function () {
    $cycle = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 2026',
        'year' => 2026,
        'status' => 'draft',
    ]);

    $member = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M1', 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@test.com', 'phone' => '1', 'participates_in_njangi' => true]);
    $cycleMember = NjangiCycleMember::create([
        'organization_id' => $this->org->id,
        'njangi_cycle_id' => $cycle->id,
        'member_id' => $member->id,
        'benefit_order' => 2,
    ]);

    $response = $this->actingAs($this->adminUser)
        ->delete(route('njangi-cycles.members.destroy', [$cycle, $cycleMember]));

    $response->assertRedirect(route('njangi-cycles.show', $cycle->id));
    expect(NjangiCycleMember::find($cycleMember->id))->toBeNull();
});

test('adding/removing members in an active cycle is rejected if mid-cycle settings are disabled', function () {
    Setting::query()->update([
        'allow_mid_cycle_enrollment' => false,
        'allow_mid_cycle_removal' => false,
    ]);

    $cycle = NjangiCycle::create([
        'organization_id' => $this->org->id,
        'name' => 'Cycle 2026',
        'year' => 2026,
        'status' => 'active',
    ]);

    // Generate sessions so the cycle is considered active (has sessions)
    $session = NjangiSession::create([
        'organization_id' => $this->org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 1,
        'session_date' => now(),
        'status' => 'open',
    ]);

    $member = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M1', 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@test.com', 'phone' => '1', 'participates_in_njangi' => true]);
    
    // Add individual participant should be rejected
    $responseAdd = $this->actingAs($this->adminUser)
        ->post(route('njangi-cycles.members.store', $cycle), [
            'member_id' => $member->id,
            'benefit_order' => 1,
        ]);
    $responseAdd->assertSessionHas('error', 'Mid-cycle enrollment is disabled in system settings.');

    // Enroll a member beforehand to test removal
    $cycleMember = NjangiCycleMember::create([
        'organization_id' => $this->org->id,
        'njangi_cycle_id' => $cycle->id,
        'member_id' => $member->id,
        'benefit_order' => 2,
    ]);

    // Removal should be rejected
    $responseRemove = $this->actingAs($this->adminUser)
        ->delete(route('njangi-cycles.members.destroy', [$cycle, $cycleMember]));
    $responseRemove->assertSessionHas('error', 'Mid-cycle removal is disabled in system settings.');
});

test('adding/removing members in an active cycle succeeds if mid-cycle settings are enabled', function () {
    Setting::query()->update([
        'allow_mid_cycle_enrollment' => true,
        'allow_mid_cycle_removal' => true,
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

    $member = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M1', 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@test.com', 'phone' => '1', 'participates_in_njangi' => true]);
    
    // Add individual participant should succeed
    $responseAdd = $this->actingAs($this->adminUser)
        ->post(route('njangi-cycles.members.store', $cycle), [
            'member_id' => $member->id,
            'benefit_order' => 1,
        ]);
    $responseAdd->assertRedirect(route('njangi-cycles.show', $cycle->id));

    $cycleMember = NjangiCycleMember::where('njangi_cycle_id', $cycle->id)->where('member_id', $member->id)->first();
    expect($cycleMember)->not->toBeNull();

    // Removal should succeed
    $responseRemove = $this->actingAs($this->adminUser)
        ->delete(route('njangi-cycles.members.destroy', [$cycle, $cycleMember]));
    $responseRemove->assertRedirect(route('njangi-cycles.show', $cycle->id));
    expect(NjangiCycleMember::find($cycleMember->id))->toBeNull();
});

test('member cannot be deleted if they already benefited', function () {
    Setting::query()->update([
        'allow_mid_cycle_removal' => true,
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

    $member = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M1', 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@test.com', 'phone' => '1', 'participates_in_njangi' => true]);
    $cycleMember = NjangiCycleMember::create([
        'organization_id' => $this->org->id,
        'njangi_cycle_id' => $cycle->id,
        'member_id' => $member->id,
        'benefit_order' => 2,
    ]);

    $sessionBeneficiary = NjangiSessionBeneficiary::create([
        'organization_id' => $this->org->id,
        'njangi_session_id' => $session->id,
        'njangi_cycle_member_id' => $cycleMember->id,
        'beneficiary_slot' => 1,
        'benefit_order' => 2,
    ]);

    // Create a disbursement to signify benefiting
    NjangiDisbursement::create([
        'organization_id' => $this->org->id,
        'njangi_session_id' => $session->id,
        'njangi_session_beneficiary_id' => $sessionBeneficiary->id,
        'njangi_cycle_member_id' => $cycleMember->id,
        'gross_amount' => 100,
        'net_amount' => 100,
        'status' => 'approved',
    ]);

    // Try deleting
    $response = $this->actingAs($this->adminUser)
        ->delete(route('njangi-cycles.members.destroy', [$cycle, $cycleMember]));

    $response->assertSessionHas('error');
    expect(NjangiCycleMember::find($cycleMember->id))->not->toBeNull();
});

test('member dashboard displays the correct Njangi warning banner depending on registration status', function () {
    // Member who is NOT registered for Njangi
    $memberNoNjangi = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'M-NO',
        'first_name' => 'No',
        'last_name' => 'Njangi',
        'email' => 'nonjangi@test.com',
        'phone' => '999',
        'participates_in_njangi' => false,
    ]);
    
    $userNoNjangi = User::factory()->create(['email' => $memberNoNjangi->email]);

    $response1 = $this->actingAs($userNoNjangi)->get(route('dashboard'));
    $response1->assertSee('You are not currently enrolled in an active Njangi rotational cycle');

    // Member who is registered for Njangi but not enrolled in a cycle
    $memberRegistered = Member::create([
        'organization_id' => $this->org->id,
        'member_code' => 'M-REG',
        'first_name' => 'Reg',
        'last_name' => 'Njangi',
        'email' => 'regnjangi@test.com',
        'phone' => '888',
        'participates_in_njangi' => true,
    ]);

    $userRegistered = User::factory()->create(['email' => $memberRegistered->email]);

    $response2 = $this->actingAs($userRegistered)->get(route('dashboard'));
    $response2->assertSee('You are registered for Njangi participation but have not yet been assigned to an active Njangi cycle');
});

test('member who has only contributed can be removed and contribution history remains', function () {
    Setting::query()->update([
        'allow_mid_cycle_removal' => true,
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

    $member = Member::create(['organization_id' => $this->org->id, 'member_code' => 'M1', 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@test.com', 'phone' => '1', 'participates_in_njangi' => true]);
    $cycleMember = NjangiCycleMember::create([
        'organization_id' => $this->org->id,
        'njangi_cycle_id' => $cycle->id,
        'member_id' => $member->id,
        'benefit_order' => 2,
    ]);

    // Create a contribution from this member
    $contribution = \App\Models\NjangiContribution::create([
        'organization_id' => $this->org->id,
        'njangi_cycle_id' => $cycle->id,
        'njangi_session_id' => $session->id,
        'contributor_member_id' => $member->id,
        'beneficiary_member_id' => $member->id,
        'amount' => 50.00,
    ]);

    // Deletion should succeed because they have only contributed and not benefited
    $response = $this->actingAs($this->adminUser)
        ->delete(route('njangi-cycles.members.destroy', [$cycle, $cycleMember]));

    $response->assertRedirect(route('njangi-cycles.show', $cycle->id));
    expect(NjangiCycleMember::find($cycleMember->id))->toBeNull();
    
    // Validate contribution history is preserved
    expect(\App\Models\NjangiContribution::find($contribution->id))->not->toBeNull();
});

