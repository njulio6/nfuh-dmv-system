<?php

use App\Models\Member;
use App\Models\Organization;
use App\Models\SavingsTransaction;
use App\Models\User;
use App\Services\SavingsBalanceReconciler;
use Database\Seeders\RolesAndPermissionsSeeder;

/**
 * Covers Bug 4 from the Nov 3 2026 demo: seeded/demo savings statements whose previous_balance
 * and new_balance snapshots did not reconcile against the member's real running total.
 */

beforeEach(function () {
    (new RolesAndPermissionsSeeder())->run();
});

function reconcilerMember(): Member
{
    $org = Organization::firstOrCreate(['name' => 'Test Org']);
    $user = User::factory()->create();
    $user->assignRole('member');

    return Member::create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'member_code' => 'M-' . rand(100000, 999999),
        'first_name' => 'Test',
        'last_name' => 'Member',
        'phone' => '123456789',
        'participates_in_savings' => true,
    ]);
}

test('report() finds no mismatches when snapshots were written correctly in order', function () {
    $member = reconcilerMember();

    SavingsTransaction::create([
        'member_id' => $member->id, 'organization_id' => $member->organization_id,
        'amount' => 500, 'previous_balance' => 0, 'new_balance' => 500,
        'type' => 'deposit', 'status' => 'approved', 'transaction_date' => '2026-01-01',
    ]);
    SavingsTransaction::create([
        'member_id' => $member->id, 'organization_id' => $member->organization_id,
        'amount' => 100, 'previous_balance' => 500, 'new_balance' => 400,
        'type' => 'withdrawal', 'status' => 'approved', 'transaction_date' => '2026-02-01',
    ]);

    $mismatches = (new SavingsBalanceReconciler())->report();

    expect($mismatches)->toHaveCount(0);
});

test('report() detects a seeded transaction whose previous_balance does not follow the prior one', function () {
    $member = reconcilerMember();

    SavingsTransaction::create([
        'member_id' => $member->id, 'organization_id' => $member->organization_id,
        'amount' => 500, 'previous_balance' => 0, 'new_balance' => 500,
        'type' => 'deposit', 'status' => 'approved', 'transaction_date' => '2026-01-01',
    ]);

    // Simulates the demo-seeder bug: an unrelated made-up opening balance instead of 500.
    SavingsTransaction::create([
        'member_id' => $member->id, 'organization_id' => $member->organization_id,
        'amount' => 100, 'previous_balance' => 1400, 'new_balance' => 1300,
        'type' => 'withdrawal', 'status' => 'approved', 'transaction_date' => '2026-02-01',
    ]);

    $mismatches = (new SavingsBalanceReconciler())->report();

    expect($mismatches)->toHaveCount(1);
    expect($mismatches->first()['expected_previous'])->toEqual(500.0);
    expect($mismatches->first()['expected_new'])->toEqual(400.0);
});

test('fix() rewrites every transaction snapshot to the real running total, in date order', function () {
    $member = reconcilerMember();

    // Deliberately inserted out of chronological order with wrong snapshots, as a bad seeder would.
    $txnFeb = SavingsTransaction::create([
        'member_id' => $member->id, 'organization_id' => $member->organization_id,
        'amount' => 150, 'previous_balance' => 9999, 'new_balance' => 9999,
        'type' => 'withdrawal', 'status' => 'approved', 'transaction_date' => '2026-02-15',
    ]);
    $txnJan = SavingsTransaction::create([
        'member_id' => $member->id, 'organization_id' => $member->organization_id,
        'amount' => 600, 'previous_balance' => 0, 'new_balance' => 0,
        'type' => 'deposit', 'status' => 'approved', 'transaction_date' => '2026-01-10',
    ]);
    $txnMar = SavingsTransaction::create([
        'member_id' => $member->id, 'organization_id' => $member->organization_id,
        'amount' => 50, 'previous_balance' => 1, 'new_balance' => 1,
        'type' => 'deposit', 'status' => 'approved', 'transaction_date' => '2026-03-01',
    ]);

    $fixed = (new SavingsBalanceReconciler())->fix();

    expect($fixed)->toBeGreaterThanOrEqual(3);

    expect((float) $txnJan->fresh()->previous_balance)->toEqual(0.0)
        ->and((float) $txnJan->fresh()->new_balance)->toEqual(600.0);
    expect((float) $txnFeb->fresh()->previous_balance)->toEqual(600.0)
        ->and((float) $txnFeb->fresh()->new_balance)->toEqual(450.0);
    expect((float) $txnMar->fresh()->previous_balance)->toEqual(450.0)
        ->and((float) $txnMar->fresh()->new_balance)->toEqual(500.0);

    expect((new SavingsBalanceReconciler())->report())->toHaveCount(0);

    expect((float) $member->fresh()->savings_balance)->toEqual(500.0);
});

test('rejected transactions do not move the running settled balance', function () {
    $member = reconcilerMember();

    SavingsTransaction::create([
        'member_id' => $member->id, 'organization_id' => $member->organization_id,
        'amount' => 500, 'previous_balance' => 0, 'new_balance' => 500,
        'type' => 'deposit', 'status' => 'approved', 'transaction_date' => '2026-01-01',
    ]);
    $rejected = SavingsTransaction::create([
        'member_id' => $member->id, 'organization_id' => $member->organization_id,
        'amount' => 999, 'previous_balance' => 0, 'new_balance' => 0,
        'type' => 'deposit', 'status' => 'rejected', 'transaction_date' => '2026-01-15',
    ]);
    $nextDeposit = SavingsTransaction::create([
        'member_id' => $member->id, 'organization_id' => $member->organization_id,
        'amount' => 200, 'previous_balance' => 0, 'new_balance' => 0,
        'type' => 'deposit', 'status' => 'approved', 'transaction_date' => '2026-02-01',
    ]);

    (new SavingsBalanceReconciler())->fix();

    expect((float) $rejected->fresh()->previous_balance)->toEqual(500.0)
        ->and((float) $rejected->fresh()->new_balance)->toEqual(500.0); // unchanged by a rejected entry
    expect((float) $nextDeposit->fresh()->previous_balance)->toEqual(500.0)
        ->and((float) $nextDeposit->fresh()->new_balance)->toEqual(700.0);
});
