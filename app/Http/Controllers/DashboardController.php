<?php

namespace App\Http\Controllers;

use App\Enums\BlockType;
use App\Models\RoutineBlock;
use App\Services\ClickUpService;
use App\Services\FeedService;
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

            $hasLists = ! empty($block->clickUpConnection->default_list_ids)
                || $block->clickUpConnection->default_list_id;

            if (! $block->clickUpConnection || ! $hasLists) {
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

        foreach ($blocks as $block) {
            if ($block->type !== BlockType::Feed) {
                continue;
            }

            $sources = $block->config['sources'] ?? [];
            $days = (int) ($block->config['days'] ?? 5);

            if (empty($sources)) {
                continue;
            }

            $props["feed_{$block->id}"] = Inertia::defer(
                fn () => $this->fetchFeed($sources, $days),
                "feed_{$block->id}",
            );
        }

        return Inertia::render('Dashboard', $props);
    }

    /** @return array{tasks: array<int, array<string, mixed>>, error: string|null} */
    private function fetchTasks(RoutineBlock $block): array
    {
        try {
            $service = new ClickUpService($block->clickUpConnection->api_token);

            $endOfToday = Carbon::now()->endOfDay()->getTimestampMs();
            $filters = ['due_date_lt' => (string) $endOfToday];

            $listIds = $block->clickUpConnection->default_list_ids;

            if (! empty($listIds)) {
                $tasks = $service->getTasksFromLists($listIds, $filters);
            } else {
                $tasks = $service->getTasks($block->clickUpConnection->default_list_id, $filters);
            }

            return ['tasks' => $tasks, 'error' => null];
        } catch (\Throwable $e) {
            return ['tasks' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $sources
     * @return array{items: array<int, array{title: string, link: string, source: string, published_at: string}>, error: string|null}
     */
    private function fetchFeed(array $sources, int $days): array
    {
        try {
            $service = new FeedService;

            return ['items' => $service->fetchArticles($sources, $days), 'error' => null];
        } catch (\Throwable $e) {
            return ['items' => [], 'error' => $e->getMessage()];
        }
    }
}
