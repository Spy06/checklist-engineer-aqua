<?php

use App\Http\Controllers\FormController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::get('/guest/matrix', [\App\Http\Controllers\GuestMatrixController::class, 'index'])->name('guest.matrix');

Route::middleware(['auth', 'verified', \App\Http\Middleware\RedirectAdminToPanel::class])->group(function () {
    Route::get('dashboard', [FormController::class, 'index'])->name('dashboard');
    Route::get('matrix', [\App\Http\Controllers\MatrixController::class, 'index'])->name('matrix');
    Route::get('forms/{path}', [FormController::class, 'show'])->where('path', '.*')->name('forms.show');
    Route::post('forms/{path}', [FormController::class, 'store'])->where('path', '.*')->name('forms.store');
});

require __DIR__.'/settings.php';
