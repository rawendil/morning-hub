<?php

use App\Http\Controllers\MorningHub\ClickUpApiController;
use App\Http\Controllers\MorningHub\ClickUpConnectionController;
use App\Http\Controllers\MorningHub\RoutineBlockController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // ClickUp Connections CRUD
    Route::get('morning-hub/clickup', [ClickUpConnectionController::class, 'index'])
        ->name('morning-hub.clickup.index');
    Route::post('morning-hub/clickup/connections', [ClickUpConnectionController::class, 'store'])
        ->name('morning-hub.clickup.store');
    Route::put('morning-hub/clickup/connections/{connection}', [ClickUpConnectionController::class, 'update'])
        ->name('morning-hub.clickup.update');
    Route::delete('morning-hub/clickup/connections/{connection}', [ClickUpConnectionController::class, 'destroy'])
        ->name('morning-hub.clickup.destroy');
    Route::post('morning-hub/clickup/connections/{connection}/test', [ClickUpConnectionController::class, 'test'])
        ->name('morning-hub.clickup.test');

    // ClickUp API proxy (JSON responses)
    Route::get('morning-hub/clickup/{connection}/workspaces', [ClickUpApiController::class, 'workspaces'])
        ->name('morning-hub.clickup.workspaces');
    Route::get('morning-hub/clickup/{connection}/spaces', [ClickUpApiController::class, 'spaces'])
        ->name('morning-hub.clickup.spaces');
    Route::get('morning-hub/clickup/{connection}/folders', [ClickUpApiController::class, 'folders'])
        ->name('morning-hub.clickup.folders');
    Route::get('morning-hub/clickup/{connection}/lists', [ClickUpApiController::class, 'lists'])
        ->name('morning-hub.clickup.lists');
    Route::get('morning-hub/clickup/{connection}/tasks/{taskId}', [ClickUpApiController::class, 'task'])
        ->name('morning-hub.clickup.task');

    // Routine Blocks CRUD
    Route::get('morning-hub/routine', [RoutineBlockController::class, 'index'])
        ->name('morning-hub.routine.index');
    Route::post('morning-hub/routine/blocks', [RoutineBlockController::class, 'store'])
        ->name('morning-hub.routine.store');
    Route::put('morning-hub/routine/blocks/{block}', [RoutineBlockController::class, 'update'])
        ->name('morning-hub.routine.update');
    Route::delete('morning-hub/routine/blocks/{block}', [RoutineBlockController::class, 'destroy'])
        ->name('morning-hub.routine.destroy');
    Route::patch('morning-hub/routine/blocks/reorder', [RoutineBlockController::class, 'reorder'])
        ->name('morning-hub.routine.reorder');
});
