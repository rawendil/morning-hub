<?php

use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\GoogleAuthController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\Auth\TwoFactorController;
use App\Http\Controllers\Api\ClickUpApiController;
use App\Http\Controllers\Api\ClickUpConnectionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GoogleCalendarApiController;
use App\Http\Controllers\Api\GoogleCalendarConnectionController;
use App\Http\Controllers\Api\RoutineBlockController;
use App\Http\Controllers\Api\Settings\AppearanceController;
use App\Http\Controllers\Api\Settings\PasswordController;
use App\Http\Controllers\Api\Settings\ProfileController;
use App\Http\Controllers\Api\Settings\TwoFactorAuthenticationController;
use App\Http\Controllers\Api\TodaysTasksConfigController;
use App\Http\Controllers\Api\TodaysTasksController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', LoginController::class)->name('api.auth.login')->middleware('throttle:10,1');
Route::post('/auth/two-factor', TwoFactorController::class)->name('api.auth.two-factor')->middleware('throttle:5,1');
Route::post('/auth/register', RegisterController::class)->name('api.auth.register')->middleware('throttle:10,1');
Route::middleware('auth:sanctum')->post('/auth/logout', LogoutController::class)->name('api.auth.logout');
Route::post('/auth/forgot-password', ForgotPasswordController::class)->name('api.auth.forgot-password')->middleware('throttle:5,1');
Route::post('/auth/reset-password', ResetPasswordController::class)->name('api.auth.reset-password')->middleware('throttle:5,1');
Route::post('/auth/google', GoogleAuthController::class)->name('api.auth.google')->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->get('/user', UserController::class)->name('api.user');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/morning-hub/routine', [RoutineBlockController::class, 'index']);
    Route::post('/morning-hub/routine/blocks', [RoutineBlockController::class, 'store']);
    Route::patch('/morning-hub/routine/blocks/reorder', [RoutineBlockController::class, 'reorder']);
    Route::put('/morning-hub/routine/blocks/{block}', [RoutineBlockController::class, 'update']);
    Route::delete('/morning-hub/routine/blocks/{block}', [RoutineBlockController::class, 'destroy']);

    Route::get('/morning-hub/clickup', [ClickUpConnectionController::class, 'index']);
    Route::put('/morning-hub/clickup/connections/{connection}', [ClickUpConnectionController::class, 'update']);
    Route::delete('/morning-hub/clickup/connections/{connection}', [ClickUpConnectionController::class, 'destroy']);
    Route::post('/morning-hub/clickup/connections/{connection}/test', [ClickUpConnectionController::class, 'test'])->middleware('throttle:5,1');

    // Google Calendar connection
    Route::get('/morning-hub/google-calendar', [GoogleCalendarConnectionController::class, 'index']);
    Route::put('/morning-hub/google-calendar', [GoogleCalendarConnectionController::class, 'update']);
    Route::delete('/morning-hub/google-calendar', [GoogleCalendarConnectionController::class, 'destroy']);
    Route::post('/morning-hub/google-calendar/test', [GoogleCalendarConnectionController::class, 'test'])->middleware('throttle:5,1');

    // Google Calendar API proxy
    Route::get('/morning-hub/google-calendar/calendars', [GoogleCalendarApiController::class, 'calendars'])->middleware('throttle:60,1');

    // ClickUp API proxy
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/morning-hub/clickup/{connection}/workspaces', [ClickUpApiController::class, 'workspaces']);
        Route::get('/morning-hub/clickup/{connection}/spaces', [ClickUpApiController::class, 'spaces']);
        Route::get('/morning-hub/clickup/{connection}/folders', [ClickUpApiController::class, 'folders']);
        Route::get('/morning-hub/clickup/{connection}/lists', [ClickUpApiController::class, 'lists']);
        Route::get('/morning-hub/clickup/{connection}/all-lists', [ClickUpApiController::class, 'allLists']);
        Route::get('/morning-hub/clickup/{connection}/me', [ClickUpApiController::class, 'me']);
        Route::get('/morning-hub/clickup/{connection}/tasks/{taskId}', [ClickUpApiController::class, 'task']);
        Route::put('/morning-hub/clickup/{connection}/tasks/{taskId}', [ClickUpApiController::class, 'updateTask']);
        Route::post('/morning-hub/clickup/{connection}/tasks', [ClickUpApiController::class, 'createTask']);
        Route::post('/morning-hub/clickup/{connection}/tasks/{taskId}/comments', [ClickUpApiController::class, 'createComment']);
        Route::get('/morning-hub/clickup/{connection}/tasks/{taskId}/comments', [ClickUpApiController::class, 'comments']);
        Route::get('/morning-hub/clickup/{connection}/statuses', [ClickUpApiController::class, 'statuses']);
    });

    Route::get('/morning-hub/todays-tasks', [TodaysTasksConfigController::class, 'index']);
    Route::put('/morning-hub/todays-tasks', [TodaysTasksConfigController::class, 'update']);

    Route::get('/settings/profile', [ProfileController::class, 'show']);
    Route::patch('/settings/profile', [ProfileController::class, 'update']);
    Route::delete('/settings/profile', [ProfileController::class, 'destroy']);

    Route::put('/settings/password', [PasswordController::class, 'update'])->middleware('throttle:6,1');

    Route::get('/settings/two-factor', [TwoFactorAuthenticationController::class, 'show']);

    Route::get('/settings/appearance', [AppearanceController::class, 'show']);
    Route::patch('/settings/appearance', [AppearanceController::class, 'update']);

    Route::get('/dashboard', DashboardController::class);
    Route::get('/todays-tasks', TodaysTasksController::class);
});
