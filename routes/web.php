<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NjangiCycleController;
use App\Http\Controllers\NjangiPaymentSubmissionController;
use App\Http\Controllers\NjangiContributionController;
use App\Imports\MembersImport;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberPortalController;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::post('/member/submissions', [MemberPortalController::class, 'storeSubmission'])
        ->name('member.submissions.store');
    Route::get('/member/njangi-report', [MemberPortalController::class, 'report'])
        ->name('member.njangi-report');
    Route::get('/member/savings', [\App\Http\Controllers\SavingsController::class, 'mySavings'])
        ->name('member.savings');
    Route::get('/member/savings/requests', [\App\Http\Controllers\SavingsController::class, 'mySavingsRequests'])
        ->name('member.savings.requests');
    Route::post('/member/savings/request', [\App\Http\Controllers\SavingsController::class, 'requestDeposit'])
        ->name('member.savings.request');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/import-members', function () {
        Excel::import(new MembersImport, storage_path('app/members.xlsx'));
        return 'Imported!';
    });

    Route::resource('members', MemberController::class);
    Route::resource('njangi-cycles', NjangiCycleController::class);

    Route::post('njangi-cycles/{njangiCycle}/add-members', [NjangiCycleController::class, 'addMembers'])
        ->name('njangi-cycles.add-members');

    Route::post('njangi-cycles/{njangiCycle}/assign-benefit-order', [NjangiCycleController::class, 'assignBenefitOrder'])
        ->name('njangi-cycles.assign-benefit-order');

    Route::post('njangi-cycles/{njangiCycle}/generate-sessions', [NjangiCycleController::class, 'generateSessions'])
        ->name('njangi-cycles.generate-sessions');

    Route::post(
        'njangi-submissions/{submission}/approve',
        [NjangiPaymentSubmissionController::class, 'approve']
    )->name('njangi-submissions.approve');

    Route::post('/njangi-submissions/{submission}/reject', [NjangiPaymentSubmissionController::class, 'reject'])
        ->name('njangi-submissions.reject');

    Route::get('/njangi-submissions', [NjangiPaymentSubmissionController::class, 'index'])
        ->name('njangi-submissions.index');

    Route::get('/njangi-contributions', [NjangiContributionController::class, 'index'])
        ->name('njangi-contributions.index');

    Route::get('/njangi-sessions/{njangiSession}/beneficiaries', [\App\Http\Controllers\NjangiSessionBeneficiaryController::class, 'edit'])
        ->name('njangi-sessions.beneficiaries.edit');
    Route::post('/njangi-sessions/{njangiSession}/beneficiaries', [\App\Http\Controllers\NjangiSessionBeneficiaryController::class, 'update'])
        ->name('njangi-sessions.beneficiaries.update');

    Route::get('/savings', [\App\Http\Controllers\SavingsController::class, 'index'])
        ->name('savings.index');
    Route::get('/savings/transactions', [\App\Http\Controllers\SavingsController::class, 'transactions'])
        ->name('savings.transactions');
    Route::get('/savings/requests', [\App\Http\Controllers\SavingsController::class, 'adminRequests'])
        ->name('savings.requests');
    Route::post('/savings', [\App\Http\Controllers\SavingsController::class, 'store'])
        ->name('savings.store');
    Route::post('/savings/requests/{depositRequest}/approve', [\App\Http\Controllers\SavingsController::class, 'approve'])
        ->name('savings.approve');
    Route::post('/savings/requests/{depositRequest}/reject', [\App\Http\Controllers\SavingsController::class, 'reject'])
        ->name('savings.reject');

    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'edit'])
        ->name('settings.edit');
    Route::post('/settings', [\App\Http\Controllers\SettingsController::class, 'update'])
        ->name('settings.update');
});

// Web-Based Visual Setup Wizard (Installer)
use App\Http\Controllers\InstallerController;

Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallerController::class, 'welcome'])->name('welcome');
    Route::get('/requirements', [InstallerController::class, 'requirements'])->name('requirements');
    Route::get('/permissions', [InstallerController::class, 'permissions'])->name('permissions');
    Route::get('/database', [InstallerController::class, 'database'])->name('database');
    Route::post('/database', [InstallerController::class, 'saveDatabase'])->name('database.save');
    Route::get('/admin', [InstallerController::class, 'admin'])->name('admin');
    Route::post('/admin', [InstallerController::class, 'saveAdmin'])->name('admin.save');
    Route::get('/complete', [InstallerController::class, 'complete'])->name('complete');
});