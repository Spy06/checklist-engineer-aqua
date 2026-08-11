<?php

use Illuminate\Support\Facades\Route;

// Root and live matrix are the same page
Route::get('/', [\App\Http\Controllers\GuestMatrixController::class, 'live'])->name('home');
Route::get('/guest/matrix/live', [\App\Http\Controllers\GuestMatrixController::class, 'live'])->name('guest.matrix.live');
Route::get('/guest/matrix/live/poll', [\App\Http\Controllers\GuestMatrixController::class, 'livePoll'])->name('guest.matrix.live.poll');
Route::get('matrix/submission-detail', [\App\Http\Controllers\GuestMatrixController::class, 'submissionDetail'])->name('matrix.submission-detail');
