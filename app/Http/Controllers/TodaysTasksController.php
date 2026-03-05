<?php

namespace App\Http\Controllers;

use App\Services\TodaysTasksService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TodaysTasksController extends Controller
{
    public function __construct(
        private TodaysTasksService $todaysTasksService,
    ) {}

    public function __invoke(Request $request): Response
    {
        $config = $request->user()->todaysTasksConfig;
        $connectionIds = $config?->connection_ids ?? [];

        $props = [
            'hasConfig' => ! empty($connectionIds),
        ];

        if (! empty($connectionIds)) {
            $props['todaysTasksData'] = Inertia::defer(
                fn () => $this->todaysTasksService->fetchGroupedTasks($connectionIds),
                'todaysTasksData',
            );
        }

        return Inertia::render('TodaysTasks', $props);
    }
}
