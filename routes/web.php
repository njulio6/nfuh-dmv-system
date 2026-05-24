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

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/import-members', function () {
    Excel::import(new MembersImport, storage_path('app/members.xlsx'));

    return 'Imported!';
});

require __DIR__ . '/auth.php';

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