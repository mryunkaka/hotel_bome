<?php

use App\Http\Controllers\Auth\PendingRegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [PendingRegisteredUserController::class, 'store'])
    ->middleware('guest')
    ->name('register.store');

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
