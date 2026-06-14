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

    // Member Loan Routes
    Route::get('/member/loans', [\App\Http\Controllers\LoanController::class, 'myLoans'])
        ->name('member.loans');
    Route::get('/member/loans/applications', [\App\Http\Controllers\LoanController::class, 'myApplications'])
        ->name('member.loans.applications');
    Route::post('/member/loans/request', [\App\Http\Controllers\LoanController::class, 'requestLoan'])
        ->name('member.loans.request');
    Route::post('/member/loans/guarantee/{guarantor}/approve', [\App\Http\Controllers\LoanController::class, 'approveGuarantee'])
        ->name('member.loans.guarantee.approve');
    Route::post('/member/loans/guarantee/{guarantor}/decline', [\App\Http\Controllers\LoanController::class, 'declineGuarantee'])
        ->name('member.loans.guarantee.decline');
    Route::get('/member/loans/statement', [\App\Http\Controllers\LoanController::class, 'myStatement'])
        ->name('member.loans.statement');
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

    // Admin Loan & Repayment Routes
    Route::get('/loans', [\App\Http\Controllers\LoanController::class, 'index'])
        ->name('loans.index');
    Route::get('/loans/member/{member}/statement', [\App\Http\Controllers\LoanController::class, 'memberStatement'])
        ->name('loans.statement');
    Route::post('/loans/{loan}/approve', [\App\Http\Controllers\LoanController::class, 'approve'])
        ->name('loans.approve');
    Route::post('/loans/{loan}/disburse', [\App\Http\Controllers\LoanController::class, 'disburse'])
        ->name('loans.disburse');
    Route::post('/loans/{loan}/reject', [\App\Http\Controllers\LoanController::class, 'reject'])
        ->name('loans.reject');
    Route::post('/loans/{loan}/repay', [\App\Http\Controllers\LoanController::class, 'repay'])
        ->name('loans.repay');
    Route::post('/loans/{loan}/sub-status', [\App\Http\Controllers\LoanController::class, 'updateSubStatus'])
        ->name('loans.update-sub-status');
    Route::post('/loans/{loan}/mark-defaulted', [\App\Http\Controllers\LoanController::class, 'markAsDefaulted'])
        ->name('loans.mark-defaulted');
    Route::post('/loans/{loan}/mark-active', [\App\Http\Controllers\LoanController::class, 'markAsActive'])
        ->name('loans.mark-active');

    // Admin Custom Loan Sub-Status routes
    Route::get('/loans/sub-statuses', [\App\Http\Controllers\LoanController::class, 'subStatusesIndex'])
        ->name('loans.sub-statuses');
    Route::post('/settings/loan-sub-statuses', [\App\Http\Controllers\LoanController::class, 'storeSubStatus'])
        ->name('admin.settings.store-sub-status');
    Route::patch('/settings/loan-sub-statuses/{subStatus}', [\App\Http\Controllers\LoanController::class, 'updateSubStatusDefinition'])
        ->name('admin.settings.update-sub-status');
    Route::delete('/settings/loan-sub-statuses/{subStatus}', [\App\Http\Controllers\LoanController::class, 'destroySubStatus'])
        ->name('admin.settings.destroy-sub-status');

    // Reports and CSV Exports
    Route::get('/reports/export/loans', [\App\Http\Controllers\ReportsController::class, 'exportLoansCsv'])
        ->name('reports.export.loans');
    Route::get('/reports/export/savings', [\App\Http\Controllers\ReportsController::class, 'exportSavingsCsv'])
        ->name('reports.export.savings');

    // System Tools routes
    Route::get('/admin/tools', [\App\Http\Controllers\SystemToolsController::class, 'index'])
        ->name('admin.tools');
    Route::post('/admin/tools/migrate', [\App\Http\Controllers\SystemToolsController::class, 'runMigrations'])
        ->name('admin.tools.migrate');
    Route::post('/admin/tools/clear-cache', [\App\Http\Controllers\SystemToolsController::class, 'clearCache'])
        ->name('admin.tools.clear-cache');
    Route::post('/admin/tools/storage-link', [\App\Http\Controllers\SystemToolsController::class, 'storageLink'])
        ->name('admin.tools.storage-link');

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