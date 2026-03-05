<?php

namespace App\Http\Controllers\MorningHub;

use App\Http\Controllers\Controller;
use App\Http\Requests\MorningHub\UpdateTodaysTasksConfigRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TodaysTasksConfigController extends Controller
{
    public function index(Request $request): Response
    {
        $config = $request->user()->todaysTasksConfig;

        return Inertia::render('morning-hub/TodaysTasksConfig', [
            'config' => $config,
            'connections' => $request->user()->clickUpConnections()->latest()->get(),
        ]);
    }

    public function update(UpdateTodaysTasksConfigRequest $request): RedirectResponse
    {
        $config = $request->user()->todaysTasksConfig()->firstOrCreate(
            ['user_id' => $request->user()->id],
        );

        $config->update($request->validated());

        return to_route('morning-hub.todays-tasks.index');
    }
}
