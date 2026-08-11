<?php

use App\Http\Controllers\FormController;
use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\GuestMatrixController::class, 'index'])->name('home');

Route::get('/guest/matrix', [\App\Http\Controllers\GuestMatrixController::class, 'index'])->name('guest.matrix');
Route::get('/guest/matrix/live', [\App\Http\Controllers\GuestMatrixController::class, 'live'])->name('guest.matrix.live');
Route::get('/guest/matrix/live/poll', [\App\Http\Controllers\GuestMatrixController::class, 'livePoll'])->name('guest.matrix.live.poll');
Route::get('matrix/submission-detail', [\App\Http\Controllers\MatrixController::class, 'submissionDetail'])->name('matrix.submission-detail');

Route::middleware(['auth', 'verified', \App\Http\Middleware\RedirectAdminToPanel::class])->group(function () {
    Route::get('dashboard', [FormController::class, 'index'])->name('dashboard');
    Route::get('matrix', [\App\Http\Controllers\MatrixController::class, 'index'])->name('matrix');
    Route::get('forms/{path}', [FormController::class, 'show'])->where('path', '.*')->name('forms.show');
    Route::post('forms/{path}', [FormController::class, 'store'])->where('path', '.*')->name('forms.store');
});

require __DIR__.'/settings.php';
