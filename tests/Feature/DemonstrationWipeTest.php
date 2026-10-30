<?php

use App\Models\Member;
use App\Models\User;
use App\Models\NjangiCycle;
use App\Models\NjangiSession;
use App\Models\SavingsTransaction;
use App\Models\SavingsDepositRequest;
use App\Models\SavingsWithdrawalRequest;
use App\Models\LoanRequest;
use App\Models\LoanRepaymentRequest;
use App\Models\MeetingAttendance;
use App\Services\DemonstrationResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('demonstration wipe page returns 404 without secret open query parameter', function () {
    $response = $this->get(route('system.demonstration-wipe'));
    $response->assertStatus(404);
});

test('demonstration wipe page renders successfully with secret open query parameter', function () {
    $response = $this->get(route('system.demonstration-wipe', ['open' => 'KhulJaChimChim']));
    $response->assertStatus(200);
    $response->assertSee('Clean-Slate Database Wipe');
    $response->assertSee('Enter Secret Security Confirmation Code:');
    $response->assertDontSee(DemonstrationResetService::SECRET_CODE);
});

test('demonstration wipe rejects invalid secret code', function () {
    $response = $this->post(route('system.demonstration-wipe.execute', ['open' => 'KhulJaChimChim']), [
        'open' => 'KhulJaChimChim',
        'secret_code' => 'invalid_secret_code',
    ]);

    $response->assertRedirect(route('system.demonstration-wipe', ['open' => 'KhulJaChimChim']));
    $response->assertSessionHas('error');
});

test('demonstration wipe successfully wipes database and seeds complete demonstration dataset with exact secret code', function () {
    // Submit exact secret code: Mou_Amar_Bou_Ektu_Santo_Hou with open=KhulJaChimChim
    $response = $this->post(route('system.demonstration-wipe.execute', ['open' => 'KhulJaChimChim']), [
        'open' => 'KhulJaChimChim',
        'secret_code' => DemonstrationResetService::SECRET_CODE,
    ]);

    $err = $response->getSession()->get('error');
    expect($err)->toBeNull();
    $response->assertSessionHas('success');

    // 1. Verify 30 Members and Users created
    expect(User::count())->toBe(30);
    expect(Member::count())->toBe(30);

    // 2. Verify Unified Password works for all accounts
    $admin = User::where('email', 'admin@nfuhdmv.org')->first();
    expect($admin)->not->toBeNull();
    expect(Hash::check('Password123!', $admin->password))->toBeTrue();
    expect($admin->hasRole('admin'))->toBeTrue();

    // 3. Verify Specific Executive Roles (Segregation of Duties)
    $treasurer = User::where('email', 'treasurer@nfuhdmv.org')->first();
    expect($treasurer)->not->toBeNull();
    expect($treasurer->hasRole('Treasurer'))->toBeTrue();
    expect($treasurer->officer_role_name)->toBe('Treasurer');

    $finsec = User::where('email', 'finsec@nfuhdmv.org')->first();
    expect($finsec)->not->toBeNull();
    expect($finsec->hasRole('Financial Secretary'))->toBeTrue();

    $secretary = User::where('email', 'secretary@nfuhdmv.org')->first();
    expect($secretary)->not->toBeNull();
    expect($secretary->hasRole('General Secretary'))->toBeTrue();

    $committee1 = User::where('email', 'committee1@nfuhdmv.org')->first();
    expect($committee1)->not->toBeNull();
    expect($committee1->hasRole('Loan Committee'))->toBeTrue();

    // 4. Verify Njangi Cycles & Sessions
    $activeCycle = NjangiCycle::where('status', 'active')->first();
    expect($activeCycle)->not->toBeNull();
    expect($activeCycle->name)->toBe('2026 Gold Executive Cycle');

    // 12 Sessions generated for 2026 cycle
    expect(NjangiSession::where('njangi_cycle_id', $activeCycle->id)->count())->toBe(12);

    // Verify all 4 session statuses exist in system
    expect(NjangiSession::where('status', 'closed')->count())->toBeGreaterThanOrEqual(10);
    expect(NjangiSession::where('status', 'open')->count())->toBeGreaterThanOrEqual(1);
    expect(NjangiSession::where('status', 'scheduled')->count())->toBeGreaterThanOrEqual(1);
    expect(NjangiSession::where('status', 'cancelled')->count())->toBeGreaterThanOrEqual(1);

    // Verify November 7, 2026 session exists and is open
    $novSession = NjangiSession::whereDate('session_date', '2026-11-07')->first();
    expect($novSession)->not->toBeNull();
    expect($novSession->status)->toBe('open');
    expect($novSession->check_in_opens_at)->toBe('17:00:00');
    expect($novSession->check_in_closes_at)->toBe('21:00:00');

    // 5. Verify Njangi Payment Submissions Across ALL Statuses (>= 15 per status)
    expect(App\Models\NjangiPaymentSubmission::where('status', 'pending')->count())->toBeGreaterThanOrEqual(15);
    expect(App\Models\NjangiPaymentSubmission::where('status', 'approved')->count())->toBeGreaterThanOrEqual(25);
    expect(App\Models\NjangiPaymentSubmission::where('status', 'rejected')->count())->toBeGreaterThanOrEqual(15);

    // 6. Verify Savings Module (ALL 6 TRANSACTION BADGES + ALL REQUEST STATUSES >= 15 EACH)
    $savingsTx = SavingsTransaction::all();
    expect($savingsTx->count())->toBeGreaterThanOrEqual(90);

    // Ensure every single approved savings transaction has non-null previous_balance and new_balance
    foreach ($savingsTx as $tx) {
        expect($tx->previous_balance)->not->toBeNull();
        expect($tx->new_balance)->not->toBeNull();
    }

    // 15 Adjustments (Balance Overrides)
    $adjustments = SavingsTransaction::where('type', 'adjustment')->get();
    expect($adjustments->count())->toBeGreaterThanOrEqual(15);
    foreach ($adjustments as $adj) {
        expect($adj->notes)->not->toBeNull();
        expect(strlen($adj->notes))->toBeGreaterThan(15);
    }

    // Savings Transactions across all types and statuses
    expect(SavingsTransaction::where('type', 'deposit')->where('status', 'approved')->count())->toBeGreaterThanOrEqual(30);
    expect(SavingsTransaction::where('type', 'withdrawal')->where('status', 'approved')->count())->toBeGreaterThanOrEqual(30);
    expect(SavingsTransaction::where('type', 'withdrawal')->where('status', 'pending')->count())->toBeGreaterThanOrEqual(15);
    expect(SavingsTransaction::where('status', 'rejected')->count())->toBeGreaterThanOrEqual(15);

    // Savings Deposit Requests Queue (15 pending, 15 approved, 15 rejected)
    expect(SavingsDepositRequest::where('status', 'pending')->count())->toBeGreaterThanOrEqual(15);
    expect(SavingsDepositRequest::where('status', 'approved')->count())->toBeGreaterThanOrEqual(15);
    expect(SavingsDepositRequest::where('status', 'rejected')->count())->toBeGreaterThanOrEqual(15);

    // Savings Withdrawal Requests Queue (15 pending, 15 approved, 15 disbursed, 15 rejected)
    expect(SavingsWithdrawalRequest::where('status', 'pending')->count())->toBeGreaterThanOrEqual(15);
    expect(SavingsWithdrawalRequest::where('status', 'approved')->count())->toBeGreaterThanOrEqual(15);
    expect(SavingsWithdrawalRequest::where('status', 'disbursed')->count())->toBeGreaterThanOrEqual(15);
    expect(SavingsWithdrawalRequest::where('status', 'rejected')->count())->toBeGreaterThanOrEqual(15);

    // 7. Verify Loans Module Across ALL 7 Statuses (>= 15 per status across 15+ borrowers)
    $allStatuses = ['pending_guarantors', 'pending_committee', 'approved', 'active', 'completed', 'defaulted', 'rejected'];
    foreach ($allStatuses as $st) {
        $count = LoanRequest::where('status', $st)->count();
        expect($count)->toBeGreaterThanOrEqual(15);
        $uniqueBorrowers = LoanRequest::where('status', $st)->distinct('member_id')->count('member_id');
        expect($uniqueBorrowers)->toBeGreaterThanOrEqual(10);
    }

    // Verify Loan Repayment Requests across ALL statuses (>= 15 each)
    expect(LoanRepaymentRequest::where('status', 'pending')->count())->toBeGreaterThanOrEqual(15);
    expect(LoanRepaymentRequest::where('status', 'approved')->count())->toBeGreaterThanOrEqual(15);
    expect(LoanRepaymentRequest::where('status', 'rejected')->count())->toBeGreaterThanOrEqual(15);

    // Verify Meeting Attendances Across ALL statuses (present, excused, absent)
    expect(MeetingAttendance::where('attendance_status', 'present')->count())->toBeGreaterThanOrEqual(20);
    expect(MeetingAttendance::where('attendance_status', 'excused')->count())->toBeGreaterThanOrEqual(15);
    expect(MeetingAttendance::where('attendance_status', 'absent')->count())->toBeGreaterThanOrEqual(15);
    expect(App\Models\MeetingGuest::count())->toBeGreaterThanOrEqual(15);
    expect(App\Models\AttendanceAuditLog::count())->toBeGreaterThanOrEqual(15);
});
