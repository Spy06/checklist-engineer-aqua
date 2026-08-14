<?php

use App\Http\Controllers\GuestMatrixController;
use App\Http\Controllers\PanduAdminController;
use Illuminate\Support\Facades\Route;

// Root and live matrix are the same page
Route::get('/', [GuestMatrixController::class, 'live'])->name('home');
Route::get('/guest/matrix/live', [GuestMatrixController::class, 'live'])->name('guest.matrix.live');
Route::get('/guest/matrix/live/poll/init', [GuestMatrixController::class, 'livePollInit'])->name('guest.matrix.live.poll.init');
Route::post('/guest/matrix/live/poll/chunk', [GuestMatrixController::class, 'livePollChunk'])->name('guest.matrix.live.poll.chunk');
Route::post('/guest/matrix/live/poll/finish', [GuestMatrixController::class, 'livePollFinish'])->name('guest.matrix.live.poll.finish');
Route::get('matrix/submission-detail', [GuestMatrixController::class, 'submissionDetail'])->name('matrix.submission-detail');
Route::get('/guest/matrix/export', [GuestMatrixController::class, 'exportExcel'])->name('guest.matrix.export');
Route::get('/guest/matrix/view', [GuestMatrixController::class, 'viewByDateRange'])->name('guest.matrix.view');

// --------------------------------------------------------------------------
// Pandu Admin Panel (Hidden Config)
// --------------------------------------------------------------------------
Route::get('/pandu-admin/login', [PanduAdminController::class, 'showLogin'])->name('pandu-admin.login');
Route::post('/pandu-admin/login', [PanduAdminController::class, 'processLogin']);

Route::middleware(['pandu.auth'])->group(function () {
    Route::get('/pandu-admin', [PanduAdminController::class, 'index'])->name('pandu-admin.dashboard');
    Route::post('/pandu-admin/config', [PanduAdminController::class, 'updateConfig'])->name('pandu-admin.config.update');
    Route::post('/pandu-admin/logout', [PanduAdminController::class, 'logout'])->name('pandu-admin.logout');

    // Form management: preview, sync dry-run, finalize
    Route::get('/pandu-admin/form/preview', [PanduAdminController::class, 'previewForm'])->name('pandu-admin.form.preview');
    Route::post('/pandu-admin/form/sync', [PanduAdminController::class, 'syncNewForm'])->name('pandu-admin.form.sync');
    Route::post('/pandu-admin/form/finalize', [PanduAdminController::class, 'finalizeForm'])->name('pandu-admin.form.finalize');
});

