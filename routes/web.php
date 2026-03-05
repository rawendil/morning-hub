<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SetLocaleController;
use App\Http\Controllers\TodaysTasksController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
    'contactEmail' => fn () => config('app.contact_email'),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('todays-tasks', TodaysTasksController::class)->name('todays-tasks');
});

Route::post('locale', SetLocaleController::class)->name('locale.update');

require __DIR__.'/settings.php';
