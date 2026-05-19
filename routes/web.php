<?php

use App\Http\Controllers\MorningHub\ClickUpOAuthController;
use App\Http\Controllers\MorningHub\GoogleCalendarOAuthController;
use Illuminate\Support\Facades\Route;

// Google Calendar OAuth callback — no auth required (user identified via cache state)
Route::get('morning-hub/google-calendar/callback', [GoogleCalendarOAuthController::class, 'callback'])
    ->name('morning-hub.google-calendar.callback');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('morning-hub/google-calendar/disconnect', [GoogleCalendarOAuthController::class, 'disconnect'])
        ->middleware('throttle:5,1')
        ->name('morning-hub.google-calendar.disconnect');
});

// Google login — server-side redirect (OAuth state requires session)
Route::get('auth/google', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'redirect'])->name('google.redirect')->middleware('throttle:10,1');
Route::get('auth/google/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'callback'])->name('google.callback');

// Google account linking — server-side redirect (same reason)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('auth/google/link', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'linkRedirect'])->name('google.link');
    Route::get('auth/google/link/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'linkCallback'])->name('google.link.callback');
    Route::delete('auth/google/unlink', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'unlink'])->name('google.unlink');
});

// ClickUp OAuth callback — no auth required (user identified via cache state)
Route::get('clickup/oauth/callback', [ClickUpOAuthController::class, 'callback'])
    ->name('clickup.oauth.callback');

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy-policy');

Route::get('/terms-of-service', function () {
    return view('terms-of-service');
})->name('terms-of-service');

// SPA catch-all — all other paths handled by Vue Router
Route::get('/{any}', function () {
    return view('spa');
})->where('any', '.*')->name('spa');
