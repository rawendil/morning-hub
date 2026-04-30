<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', LoginController::class)->name('api.auth.login');
Route::post('/auth/two-factor', TwoFactorController::class)->name('api.auth.two-factor');
