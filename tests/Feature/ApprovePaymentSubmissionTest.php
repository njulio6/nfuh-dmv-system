<?php

use App\Models\User;
use App\Models\Member;
use App\Models\Organization;
use App\Models\NjangiCycle;
use App\Models\NjangiCycleMember;
use App\Models\NjangiSession;
use App\Models\NjangiSessionBeneficiary;
use App\Models\NjangiPaymentSubmission;
use App\Models\NjangiContribution;
use App\Services\Njangi\ApproveNjangiPaymentSubmission;

it('splits the payment submission amount equally among beneficiaries', function () {
    $org = Organization::create(['name' => 'Test Org']);
    
    $reviewer = User::create([
        'name' => 'Reviewer',
        'email' => 'reviewer@example.com',
        'password' => bcrypt('password'),
    ]);

    $member1 = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M1',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '123',
    ]);
    
    $member2 = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M2',
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'phone' => '456',
    ]);

    $member3 = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M3',
        'first_name' => 'Bob',
        'last_name' => 'Smith',
        'email' => 'bob@example.com',
        'phone' => '789',
    ]);
    
    $cycle = NjangiCycle::create([
        'organization_id' => $org->id,
        'name' => 'Test Cycle',
        'year' => 2026,
        'status' => 'active',
    ]);
    
    $cycleMember2 = NjangiCycleMember::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'member_id' => $member2->id,
        'subscription_amount' => 200,
        'is_active' => true,
    ]);
    
    $cycleMember3 = NjangiCycleMember::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'member_id' => $member3->id,
        'subscription_amount' => 200,
        'is_active' => true,
    ]);
    
    $session = NjangiSession::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 1,
        'session_date' => now(),
        'status' => 'open',
    ]);
    
    NjangiSessionBeneficiary::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'njangi_cycle_member_id' => $cycleMember2->id,
        'beneficiary_slot' => 1,
    ]);

    NjangiSessionBeneficiary::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'njangi_cycle_member_id' => $cycleMember3->id,
        'beneficiary_slot' => 2,
    ]);
    
    $submission = NjangiPaymentSubmission::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'njangi_session_id' => $session->id,
        'member_id' => $member1->id,
        'amount' => 400.00,
        'screenshot_path' => 'screenshots/test.png',
        'status' => 'pending',
    ]);
    
    $service = new ApproveNjangiPaymentSubmission();
    $service->execute($submission, $reviewer->id);
    
    $contributions = NjangiContribution::where('payment_submission_id', $submission->id)->get();
    
    expect($contributions)->toHaveCount(2);
    expect((float)$contributions[0]->amount)->toEqual(200.00);
    expect((float)$contributions[1]->amount)->toEqual(200.00);
});

it('handles fractional remains cleanly', function () {
    $org = Organization::create(['name' => 'Test Org 2']);
    
    $reviewer = User::create([
        'name' => 'Reviewer 2',
        'email' => 'reviewer2@example.com',
        'password' => bcrypt('password'),
    ]);

    $member1 = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M11',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john2@example.com',
        'phone' => '123',
    ]);
    
    $member2 = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M22',
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane2@example.com',
        'phone' => '456',
    ]);

    $member3 = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M33',
        'first_name' => 'Bob',
        'last_name' => 'Smith',
        'email' => 'bob2@example.com',
        'phone' => '789',
    ]);

    $member4 = Member::create([
        'organization_id' => $org->id,
        'member_code' => 'M44',
        'first_name' => 'Alice',
        'last_name' => 'Cooper',
        'email' => 'alice@example.com',
        'phone' => '000',
    ]);
    
    $cycle = NjangiCycle::create([
        'organization_id' => $org->id,
        'name' => 'Test Cycle 2',
        'year' => 2026,
        'status' => 'active',
    ]);
    
    $cm2 = NjangiCycleMember::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'member_id' => $member2->id,
        'subscription_amount' => 100,
        'is_active' => true,
    ]);
    
    $cm3 = NjangiCycleMember::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'member_id' => $member3->id,
        'subscription_amount' => 100,
        'is_active' => true,
    ]);

    $cm4 = NjangiCycleMember::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'member_id' => $member4->id,
        'subscription_amount' => 100,
        'is_active' => true,
    ]);
    
    $session = NjangiSession::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'session_number' => 1,
        'session_date' => now(),
        'status' => 'open',
    ]);
    
    NjangiSessionBeneficiary::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'njangi_cycle_member_id' => $cm2->id,
        'beneficiary_slot' => 1,
    ]);

    NjangiSessionBeneficiary::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'njangi_cycle_member_id' => $cm3->id,
        'beneficiary_slot' => 2,
    ]);

    NjangiSessionBeneficiary::create([
        'organization_id' => $org->id,
        'njangi_session_id' => $session->id,
        'njangi_cycle_member_id' => $cm4->id,
        'beneficiary_slot' => 3,
    ]);
    
    $submission = NjangiPaymentSubmission::create([
        'organization_id' => $org->id,
        'njangi_cycle_id' => $cycle->id,
        'njangi_session_id' => $session->id,
        'member_id' => $member1->id,
        'amount' => 400.00,
        'screenshot_path' => 'screenshots/test2.png',
        'status' => 'pending',
    ]);
    
    $service = new ApproveNjangiPaymentSubmission();
    $service->execute($submission, $reviewer->id);
    
    $contributions = NjangiContribution::where('payment_submission_id', $submission->id)
        ->orderBy('id')
        ->get();
    
    expect($contributions)->toHaveCount(3);
    // 400 / 3 = 133.33 each with 0.01 remainder distributed to the first beneficiary
    expect((float)$contributions[0]->amount)->toEqual(133.34);
    expect((float)$contributions[1]->amount)->toEqual(133.33);
    expect((float)$contributions[2]->amount)->toEqual(133.33);
    expect((float)$contributions->sum('amount'))->toEqual(400.00);
});
