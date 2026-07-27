<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FormController;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [FormController::class, 'index'])->name('dashboard');
    Route::get('forms/{path}', [FormController::class, 'show'])->where('path', '.*')->name('forms.show');
    Route::post('forms/{path}', [FormController::class, 'store'])->where('path', '.*')->name('forms.store');
});

require __DIR__.'/settings.php';
