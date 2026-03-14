<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SetLocaleController;
use App\Http\Controllers\TodaysTasksController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('todays-tasks', TodaysTasksController::class)->name('todays-tasks');
});

Route::post('locale', SetLocaleController::class)->name('locale.update');

// Google OAuth - guest routes
Route::middleware('guest')->group(function () {
    Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
});

// Google OAuth - authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('auth/google/link', [GoogleAuthController::class, 'linkRedirect'])->name('google.link');
    Route::get('auth/google/link/callback', [GoogleAuthController::class, 'linkCallback'])->name('google.link.callback');
    Route::delete('auth/google/unlink', [GoogleAuthController::class, 'unlink'])->name('google.unlink');
});

require __DIR__.'/settings.php';
