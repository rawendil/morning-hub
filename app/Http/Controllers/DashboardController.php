<?php

namespace App\Http\Controllers;

use App\Enums\BlockType;
use App\Models\RoutineBlock;
use App\Services\ClickUpService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $blocks = $request->user()
            ->routineBlocks()
            ->ordered()
            ->with('clickUpConnection')
            ->get();

        $props = ['blocks' => $blocks];

        foreach ($blocks as $block) {
            if ($block->type !== BlockType::Clickup) {
                continue;
            }

            if (! $block->clickUpConnection || ! $block->clickUpConnection->default_list_id) {
                continue;
            }

            $props["tasks_{$block->id}"] = Inertia::defer(
                fn () => $this->fetchTasks($block),
                "tasks_{$block->id}",
            );
        }

        $today = now()->toDateString();

        foreach ($blocks as $block) {
            if ($block->type !== BlockType::Habits) {
                continue;
            }

            $sessionKey = "habits_block_{$block->id}";
            $state = $request->session()->get($sessionKey, []);

            $props["habits_{$block->id}"] = ($state['date'] ?? null) === $today
                ? ($state['completed'] ?? [])
                : [];
        }

        return Inertia::render('Dashboard', $props);
    }

    /** @return array{tasks: array<int, array<string, mixed>>, error: string|null} */
    private function fetchTasks(RoutineBlock $block): array
    {
        try {
            $service = new ClickUpService($block->clickUpConnection->api_token);

            $endOfToday = Carbon::now()->endOfDay()->getTimestampMs();

            $tasks = $service->getTasks($block->clickUpConnection->default_list_id, [
                'due_date_lt' => (string) $endOfToday,
            ]);

            return ['tasks' => $tasks, 'error' => null];
        } catch (\Throwable $e) {
            return ['tasks' => [], 'error' => $e->getMessage()];
        }
    }
}
