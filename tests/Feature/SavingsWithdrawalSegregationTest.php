<?php

use App\Models\User;
use App\Models\Member;
use App\Models\Organization;
use App\Models\SavingsTransaction;
use App\Models\SavingsWithdrawalRequest;
use App\Models\Setting;
use Database\Seeders\RolesAndPermissionsSeeder;

/**
 * Regression tests for the four post-demo withdrawal bugs (Nov 3 2026 demo, Revision 11):
 *   1. Treasurer could not find the Disburse action (default work-queue tab per permission).
 *   2. Same officer could approve AND disburse the same withdrawal (segregation-of-duties setting).
 *   3. Approval wrongly blocked a request that was more than half the member's balance (double-counted reservation).
 *   4. Disbursed/rejected requests must never be payable twice (row locking).
 *
 * These use REAL Financial Secretary / Treasurer officer accounts, not the generic admin helper,
 * because the demo bug only reproduces with two distinct non-admin officers.
 */

beforeEach(function () {
    (new RolesAndPermissionsSeeder())->run();
});

function wsSegOrg(): Organization
{
    return Organization::firstOrCreate(['name' => 'Test Org']);
}

function wsSegMember(float $savingsBalance = 1000.00): array
{
    $org = wsSegOrg();
    $user = User::factory()->create();
    $user->assignRole('member');

    $member = Member::create([
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'member_code' => 'M-' . rand(100000, 999999),
        'first_name' => 'Test',
        'last_name' => 'Member',
        'phone' => '123456789',
        'participates_in_savings' => true,
    ]);

    if ($savingsBalance > 0) {
        SavingsTransaction::create([
            'member_id' => $member->id,
            'organization_id' => $org->id,
            'amount' => $savingsBalance,
            'type' => 'deposit',
            'status' => 'approved',
            'transaction_date' => now()->toDateString(),
            'notes' => 'Initial balance seed',
        ]);
    }

    return [$user, $member, $org];
}

function wsSegFinancialSecretary(): User
{
    $user = User::factory()->create();
    $user->assignRole('Financial Secretary');
    return $user;
}

function wsSegTreasurer(): User
{
    $user = User::factory()->create();
    $user->assignRole('Treasurer');
    return $user;
}

function wsSegWithdrawalRequest(Member $member, Organization $org, float $gross = 200.00, string $status = 'pending'): SavingsWithdrawalRequest
{
    return SavingsWithdrawalRequest::create([
        'member_id' => $member->id,
        'organization_id' => $org->id,
        'gross_amount' => $gross,
        'fee_rate' => 2.00,
        'fee_amount' => round($gross * 0.02, 2),
        'net_amount' => $gross - round($gross * 0.02, 2),
        'payout_method' => 'zelle',
        'payout_details' => 'zelle@member.com',
        'status' => $status,
    ]);
}

// ── Bug 2: default roles now support Financial Secretary decides -> Treasurer disburses ──────

test('a real Financial Secretary account can approve a withdrawal out of the box', function () {
    [$user, $member, $org] = wsSegMember(1000.00);
    $finSec = wsSegFinancialSecretary();
    $req = wsSegWithdrawalRequest($member, $org, 200.00);

    $response = $this->actingAs($finSec)->post(route('savings.withdrawal-requests.approve', $req->id), [
        'admin_notes' => 'Reviewed and approved',
    ]);

    $response->assertSessionHas('success');
    expect($req->fresh()->status)->toEqual('approved');
});

test('a real Treasurer account cannot approve a withdrawal by default (no approve permission)', function () {
    [$user, $member, $org] = wsSegMember(1000.00);
    $treasurer = wsSegTreasurer();
    $req = wsSegWithdrawalRequest($member, $org, 200.00);

    $this->actingAs($treasurer)->post(route('savings.withdrawal-requests.approve', $req->id), [
        'admin_notes' => 'x',
    ])->assertStatus(403);
});

test('a real Financial Secretary account cannot disburse by default (no disburse permission)', function () {
    [$user, $member, $org] = wsSegMember(1000.00);
    $finSec = wsSegFinancialSecretary();
    $req = wsSegWithdrawalRequest($member, $org, 200.00, 'approved');

    $this->actingAs($finSec)->post(route('savings.withdrawal-requests.disburse', $req->id), [
        'disbursement_method' => 'zelle',
        'disbursement_destination' => 'zelle@member.com',
        'disbursement_date' => now()->toDateString(),
        'disbursement_proof' => \Illuminate\Http\UploadedFile::fake()->create('r.png', 100, 'image/png'),
    ])->assertStatus(403);
});

test('Financial Secretary approves and a different Treasurer can disburse end-to-end', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    [$user, $member, $org] = wsSegMember(1000.00);
    $finSec = wsSegFinancialSecretary();
    $treasurer = wsSegTreasurer();
    $req = wsSegWithdrawalRequest($member, $org, 200.00);

    $this->actingAs($finSec)->post(route('savings.withdrawal-requests.approve', $req->id), [
        'admin_notes' => 'Approved',
    ])->assertSessionHas('success');

    expect($req->fresh()->status)->toEqual('approved');

    $this->actingAs($treasurer)->post(route('savings.withdrawal-requests.disburse', $req->id), [
        'disbursement_method' => 'zelle',
        'disbursement_destination' => 'zelle@member.com',
        'disbursement_date' => now()->toDateString(),
        'disbursement_proof' => \Illuminate\Http\UploadedFile::fake()->create('r.png', 100, 'image/png'),
    ])->assertSessionHas('success');

    expect($req->fresh()->status)->toEqual('disbursed');
});

// ── Bug 2: the officer who approved cannot also disburse, when the setting is enabled ─────────

test('the setting defaults to enabled and blocks the approver from disbursing their own approval', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    [$user, $member, $org] = wsSegMember(1000.00);
    // One officer holding both permissions (e.g. admin, or a custom role) to isolate the "same person" check
    $bothPermsOfficer = User::factory()->create();
    $bothPermsOfficer->assignRole('admin');

    $req = wsSegWithdrawalRequest($member, $org, 200.00);

    expect(Setting::first()?->require_separate_withdrawal_disburser ?? true)->toBeTrue();

    $this->actingAs($bothPermsOfficer)->post(route('savings.withdrawal-requests.approve', $req->id), [
        'admin_notes' => 'Approved',
    ])->assertSessionHas('success');

    $response = $this->actingAs($bothPermsOfficer)->post(route('savings.withdrawal-requests.disburse', $req->id), [
        'disbursement_method' => 'zelle',
        'disbursement_destination' => 'zelle@member.com',
        'disbursement_date' => now()->toDateString(),
        'disbursement_proof' => \Illuminate\Http\UploadedFile::fake()->create('r.png', 100, 'image/png'),
    ]);

    $response->assertSessionHas('error');
    expect($req->fresh()->status)->toEqual('approved'); // still awaiting a different disburser
});

test('turning off the setting allows the same officer to approve and disburse', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    [$user, $member, $org] = wsSegMember(1000.00);
    $bothPermsOfficer = User::factory()->create();
    $bothPermsOfficer->assignRole('admin');

    Setting::updateOrCreate([], ['require_separate_withdrawal_disburser' => false]);

    $req = wsSegWithdrawalRequest($member, $org, 200.00);

    $this->actingAs($bothPermsOfficer)->post(route('savings.withdrawal-requests.approve', $req->id), [
        'admin_notes' => 'Approved',
    ])->assertSessionHas('success');

    $this->actingAs($bothPermsOfficer)->post(route('savings.withdrawal-requests.disburse', $req->id), [
        'disbursement_method' => 'zelle',
        'disbursement_destination' => 'zelle@member.com',
        'disbursement_date' => now()->toDateString(),
        'disbursement_proof' => \Illuminate\Http\UploadedFile::fake()->create('r.png', 100, 'image/png'),
    ])->assertSessionHas('success');

    expect($req->fresh()->status)->toEqual('disbursed');
});

// ── Bug 3: approval no longer double-counts the request's own reservation ─────────────────────

test('a withdrawal for more than half the balance can still be approved (no double-counted reservation)', function () {
    [$user, $member, $org] = wsSegMember(1000.00);
    $finSec = wsSegFinancialSecretary();

    // Member requests $600 of their $1000 balance. Available balance is already reserved down to $400
    // the moment the request is submitted. Approval must not compare $600 against that already-reserved $400.
    $req = wsSegWithdrawalRequest($member, $org, 600.00);

    expect((float) $member->fresh()->available_savings_balance)->toEqual(400.00);

    $response = $this->actingAs($finSec)->post(route('savings.withdrawal-requests.approve', $req->id), [
        'admin_notes' => 'Approved',
    ]);

    $response->assertSessionHas('success');
    expect($req->fresh()->status)->toEqual('approved');
});

test('the balance check still blocks approval when the member genuinely lacks the funds', function () {
    [$user, $member, $org] = wsSegMember(1000.00);
    $finSec = wsSegFinancialSecretary();

    // First $600 request is approved on its own (fits the $1000 balance).
    $reqA = wsSegWithdrawalRequest($member, $org, 600.00);
    $this->actingAs($finSec)->post(route('savings.withdrawal-requests.approve', $reqA->id), [
        'admin_notes' => 'Approved',
    ])->assertSessionHas('success');

    // A second $600 request now cannot also be approved: only $400 genuinely remains.
    $reqB = wsSegWithdrawalRequest($member, $org, 600.00);
    $response = $this->actingAs($finSec)->post(route('savings.withdrawal-requests.approve', $reqB->id), [
        'admin_notes' => 'Approved',
    ]);

    $response->assertSessionHas('error');
    expect($reqB->fresh()->status)->toEqual('pending');
});

test('reserved_withdrawals_amount and available_savings_balance reflect pending and approved requests', function () {
    [$user, $member, $org] = wsSegMember(1000.00);
    wsSegWithdrawalRequest($member, $org, 150.00, 'pending');
    wsSegWithdrawalRequest($member, $org, 250.00, 'approved');
    wsSegWithdrawalRequest($member, $org, 999.00, 'rejected'); // must not reserve

    $fresh = $member->fresh();
    expect((float) $fresh->reserved_withdrawals_amount)->toEqual(400.00)
        ->and((float) $fresh->available_savings_balance)->toEqual(600.00)
        ->and((float) $fresh->savings_balance)->toEqual(1000.00);
});

// ── Bug 4 (code-side): a request cannot be disbursed or approved twice ─────────────────────────

test('an already-disbursed withdrawal cannot be disbursed again', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    [$user, $member, $org] = wsSegMember(1000.00);
    $treasurer = wsSegTreasurer();
    $req = wsSegWithdrawalRequest($member, $org, 200.00, 'approved');

    $this->actingAs($treasurer)->post(route('savings.withdrawal-requests.disburse', $req->id), [
        'disbursement_method' => 'zelle',
        'disbursement_destination' => 'zelle@member.com',
        'disbursement_date' => now()->toDateString(),
        'disbursement_proof' => \Illuminate\Http\UploadedFile::fake()->create('r.png', 100, 'image/png'),
    ])->assertSessionHas('success');

    $settledAfterFirst = (float) $member->fresh()->savings_balance;

    $second = $this->actingAs($treasurer)->post(route('savings.withdrawal-requests.disburse', $req->id), [
        'disbursement_method' => 'zelle',
        'disbursement_destination' => 'zelle@member.com',
        'disbursement_date' => now()->toDateString(),
        'disbursement_proof' => \Illuminate\Http\UploadedFile::fake()->create('r2.png', 100, 'image/png'),
    ]);

    $second->assertSessionHas('error');
    expect((float) $member->fresh()->savings_balance)->toEqual($settledAfterFirst);
});

// ── Bug 1: default landing tab is chosen from the officer's own permissions ────────────────────

test('a Financial Secretary visiting the withdrawals page defaults to the Pending Review tab', function () {
    [$user, $member, $org] = wsSegMember(1000.00);
    $finSec = wsSegFinancialSecretary();
    wsSegWithdrawalRequest($member, $org, 100.00, 'pending');
    wsSegWithdrawalRequest($member, $org, 100.00, 'approved');

    $response = $this->actingAs($finSec)->get(route('savings.withdrawal-requests'));

    $response->assertOk();
    $response->assertViewHas('activeStatus', 'pending');
});

test('a Treasurer visiting the withdrawals page defaults to the Awaiting Disbursement tab', function () {
    [$user, $member, $org] = wsSegMember(1000.00);
    $treasurer = wsSegTreasurer();
    wsSegWithdrawalRequest($member, $org, 100.00, 'pending');
    wsSegWithdrawalRequest($member, $org, 100.00, 'approved');

    $response = $this->actingAs($treasurer)->get(route('savings.withdrawal-requests'));

    $response->assertOk();
    $response->assertViewHas('activeStatus', 'approved');
});

test('an admin visiting the withdrawals page defaults to the All tab', function () {
    [$user, $member, $org] = wsSegMember(1000.00);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    wsSegWithdrawalRequest($member, $org, 100.00, 'pending');
    wsSegWithdrawalRequest($member, $org, 100.00, 'approved');

    $response = $this->actingAs($admin)->get(route('savings.withdrawal-requests'));

    $response->assertOk();
    $response->assertViewHas('activeStatus', 'all');
});

test('an explicit status query parameter overrides the permission-based default tab', function () {
    [$user, $member, $org] = wsSegMember(1000.00);
    $treasurer = wsSegTreasurer();

    $response = $this->actingAs($treasurer)->get(route('savings.withdrawal-requests', ['status' => 'disbursed']));

    $response->assertOk();
    $response->assertViewHas('activeStatus', 'disbursed');
});

// ── Rejection keeps the audit trail instead of deleting the ledger entry ───────────────────────

// ── Full page render smoke test across every status/role branch in the Blade view ─────────────

test('the withdrawals page renders every status row and button state without error', function () {
    [$userA, $memberA, $org] = wsSegMember(1000.00);
    [$userB, $memberB] = wsSegMember(1000.00);
    $finSec = wsSegFinancialSecretary();
    $treasurer = wsSegTreasurer();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Pending row, an approved row that THIS admin approved (exercises the "blocked as approver"
    // branch, since only someone with disburse permission can ever reach that branch), disbursed, rejected.
    wsSegWithdrawalRequest($memberA, $org, 100.00, 'pending');
    $approvedByAdmin = wsSegWithdrawalRequest($memberA, $org, 100.00, 'approved');
    $approvedByAdmin->update(['reviewed_by' => $admin->id]);
    wsSegWithdrawalRequest($memberA, $org, 100.00, 'disbursed');
    wsSegWithdrawalRequest($memberB, $org, 100.00, 'rejected');

    foreach ([$finSec, $treasurer, $admin] as $officer) {
        foreach (['pending', 'approved', 'disbursed', 'rejected', 'all'] as $status) {
            $this->actingAs($officer)
                ->get(route('savings.withdrawal-requests', ['status' => $status]))
                ->assertOk();
        }
    }

    // The admin who approved this request is blocked from also disbursing it themselves.
    $this->actingAs($admin)
        ->get(route('savings.withdrawal-requests', ['status' => 'approved']))
        ->assertOk()
        ->assertSee('You Approved');

    // A Financial Secretary (no disburse permission at all) sees "Awaiting Payout" instead.
    $this->actingAs($finSec)
        ->get(route('savings.withdrawal-requests', ['status' => 'approved']))
        ->assertOk()
        ->assertSee('Awaiting Payout');
});

test('rejecting an approved withdrawal marks its pending ledger entry rejected instead of deleting it', function () {
    [$user, $member, $org] = wsSegMember(1000.00);
    $finSec = wsSegFinancialSecretary();
    $req = wsSegWithdrawalRequest($member, $org, 200.00);

    $this->actingAs($finSec)->post(route('savings.withdrawal-requests.approve', $req->id), [
        'admin_notes' => 'Approved',
    ])->assertSessionHas('success');

    $txnId = $req->fresh()->savings_transaction_id;
    expect($txnId)->not->toBeNull();

    $this->actingAs($finSec)->post(route('savings.withdrawal-requests.reject', $req->id), [
        'rejection_reason' => 'Member provided invalid payout details after approval.',
    ])->assertSessionHas('success');

    expect($req->fresh()->status)->toEqual('rejected');
    $this->assertDatabaseHas('savings_transactions', [
        'id' => $txnId,
        'status' => 'rejected',
    ]);
});
