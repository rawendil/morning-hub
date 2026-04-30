<?php

use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', LoginController::class)->name('api.auth.login')->middleware('throttle:10,1');
Route::post('/auth/two-factor', TwoFactorController::class)->name('api.auth.two-factor')->middleware('throttle:5,1');
Route::post('/auth/register', RegisterController::class)->name('api.auth.register')->middleware('throttle:10,1');
Route::middleware('auth:sanctum')->post('/auth/logout', LogoutController::class)->name('api.auth.logout');
Route::post('/auth/forgot-password', ForgotPasswordController::class)->name('api.auth.forgot-password')->middleware('throttle:5,1');
Route::post('/auth/reset-password', ResetPasswordController::class)->name('api.auth.reset-password')->middleware('throttle:5,1');
