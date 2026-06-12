<?php

use App\Models\User;
use App\Models\Organization;
use App\Models\NjangiCycle;
use App\Models\NjangiSession;
use App\Models\NjangiPaymentSubmission;
use App\Models\NjangiContribution;
use App\Models\Member;

test('submissions list filters by query cycle_id or defaults to active cycle', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $user = User::factory()->create();
    $user->assignRole('admin');
    $org = Organization::create(['name' => 'Org']);

    // Create member
    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-TEST',
        'first_name' => 'Test',
        'last_name' => 'Member',
        'email' => 'test@member.com',
        'phone' => '12345',
    ]);

    // Create active cycle
    $activeCycle = NjangiCycle::create([
        'organization_id' => $org->id,
        'name' => 'Active Cycle',
        'year' => 2026,
        'status' => 'active',
    ]);

    // Create draft cycle
    $draftCycle = NjangiCycle::create([
        'organization_id' => $org->id,
        'name' => 'Draft Cycle',
        'year' => 2026,
        'status' => 'draft',
    ]);

    $session1 = NjangiSession::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $activeCycle->id,
        'session_number' => 1,
        'session_date' => now(),
        'status' => 'open',
    ]);

    $session2 = NjangiSession::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $draftCycle->id,
        'session_number' => 1,
        'session_date' => now(),
        'status' => 'open',
    ]);

    $sub1 = NjangiPaymentSubmission::create([
        'organization_id' => $org->id,
        'member_id' => $member->id,
        'njangi_cycle_id' => $activeCycle->id,
        'njangi_session_id' => $session1->id,
        'amount' => 100,
        'screenshot_path' => 'sc.png',
        'status' => 'pending',
    ]);

    $sub2 = NjangiPaymentSubmission::create([
        'organization_id' => $org->id,
        'member_id' => $member->id,
        'njangi_cycle_id' => $draftCycle->id,
        'njangi_session_id' => $session2->id,
        'amount' => 200,
        'screenshot_path' => 'sc.png',
        'status' => 'pending',
    ]);

    // Request submissions index without cycle_id => should default to activeCycle (activeCycle->id)
    $response = $this->actingAs($user)
        ->get(route('njangi-submissions.index'));

    $response->assertOk();
    $response->assertViewHas('activeCycle');
    $response->assertViewHas('submissions');
    $submissions = $response->viewData('submissions');
    expect($submissions->pluck('id'))->toContain($sub1->id);
    expect($submissions->pluck('id'))->not->toContain($sub2->id);

    // Request submissions index with cycle_id = draftCycle->id
    $response = $this->actingAs($user)
        ->get(route('njangi-submissions.index', ['cycle_id' => $draftCycle->id]));

    $response->assertOk();
    $submissions = $response->viewData('submissions');
    expect($submissions->pluck('id'))->toContain($sub2->id);
    expect($submissions->pluck('id'))->not->toContain($sub1->id);
});

test('contributions list filters by query cycle_id or defaults to active cycle', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    $user = User::factory()->create();
    $user->assignRole('admin');
    $org = Organization::create(['name' => 'Org 2']);

    $member = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M-TEST2',
        'first_name' => 'Test2',
        'last_name' => 'Member2',
        'email' => 'test2@member.com',
        'phone' => '123452',
    ]);

    $activeCycle = NjangiCycle::create([
        'organization_id' => $org->id,
        'name' => 'Active Cycle 2',
        'year' => 2026,
        'status' => 'active',
    ]);

    $draftCycle = NjangiCycle::create([
        'organization_id' => $org->id,
        'name' => 'Draft Cycle 2',
        'year' => 2026,
        'status' => 'draft',
    ]);

    $session1 = NjangiSession::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $activeCycle->id,
        'session_number' => 1,
        'session_date' => now(),
        'status' => 'open',
    ]);

    $session2 = NjangiSession::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $draftCycle->id,
        'session_number' => 1,
        'session_date' => now(),
        'status' => 'open',
    ]);

    $sub1 = NjangiPaymentSubmission::create([
        'organization_id' => $org->id,
        'member_id' => $member->id,
        'njangi_cycle_id' => $activeCycle->id,
        'njangi_session_id' => $session1->id,
        'amount' => 150,
        'screenshot_path' => 'sc1.png',
        'status' => 'approved',
    ]);

    $sub2 = NjangiPaymentSubmission::create([
        'organization_id' => $org->id,
        'member_id' => $member->id,
        'njangi_cycle_id' => $draftCycle->id,
        'njangi_session_id' => $session2->id,
        'amount' => 250,
        'screenshot_path' => 'sc2.png',
        'status' => 'approved',
    ]);

    $contrib1 = NjangiContribution::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $activeCycle->id,
        'njangi_session_id' => $session1->id,
        'contributor_member_id' => $member->id,
        'beneficiary_member_id' => $member->id,
        'payment_submission_id' => $sub1->id,
        'amount' => 150,
        'payment_date' => now(),
        'payment_method' => 'Zelle',
    ]);

    $contrib2 = NjangiContribution::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $draftCycle->id,
        'njangi_session_id' => $session2->id,
        'contributor_member_id' => $member->id,
        'beneficiary_member_id' => $member->id,
        'payment_submission_id' => $sub2->id,
        'amount' => 250,
        'payment_date' => now(),
        'payment_method' => 'Zelle',
    ]);

    // Request contributions index without cycle_id => should default to activeCycle (activeCycle->id)
    $response = $this->actingAs($user)
        ->get(route('njangi-contributions.index'));

    $response->assertOk();
    $response->assertViewHas('activeCycle');
    $response->assertViewHas('contributions');
    $contributions = $response->viewData('contributions');
    expect($contributions->pluck('id'))->toContain($contrib1->id);
    expect($contributions->pluck('id'))->not->toContain($contrib2->id);

    // Check summary calculation is filtered
    $response->assertViewHas('totalAmount');
    expect((float)$response->viewData('totalAmount'))->toEqual(150.0);

    // Request contributions index with cycle_id = draftCycle->id
    $response = $this->actingAs($user)
        ->get(route('njangi-contributions.index', ['cycle_id' => $draftCycle->id]));

    $response->assertOk();
    $contributions = $response->viewData('contributions');
    expect($contributions->pluck('id'))->toContain($contrib2->id);
    expect($contributions->pluck('id'))->not->toContain($contrib1->id);

    // Check summary calculation is filtered for draft cycle
    expect((float)$response->viewData('totalAmount'))->toEqual(250.0);
});
