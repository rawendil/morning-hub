<?php

namespace App\Http\Controllers;

use App\Enums\BlockType;
use App\Models\RoutineBlock;
use App\Services\ClickUpService;
use App\Services\FeedService;
use App\Services\GoogleCalendarServiceFactory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly GoogleCalendarServiceFactory $googleCalendarServiceFactory,
    ) {}

    public function __invoke(Request $request): Response
    {
        $blocks = $request->user()
            ->routineBlocks()
            ->ordered()
            ->with(['clickUpConnection', 'googleCalendarConnection'])
            ->get();

        $props = ['blocks' => $blocks];

        foreach ($blocks as $block) {
            if ($block->type !== BlockType::Clickup) {
                continue;
            }

            if (! $block->clickUpConnection) {
                continue;
            }

            $hasLists = ! empty($block->clickUpConnection->default_list_ids)
                || $block->clickUpConnection->default_list_id;

            if (! $hasLists) {
                continue;
            }

            $props["tasks_{$block->id}"] = Inertia::defer(
                fn () => $this->fetchTasks($block),
                "tasks_{$block->id}",
            );
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

        foreach ($blocks as $block) {
            if ($block->type !== BlockType::GoogleCalendar) {
                continue;
            }

            if (! $block->googleCalendarConnection) {
                continue;
            }

            $props["events_{$block->id}"] = Inertia::defer(
                fn () => $this->googleCalendarServiceFactory
                    ->make($block->googleCalendarConnection)
                    ->getEventsForDashboard(),
                "events_{$block->id}",
            );
        }

        return Inertia::render('Dashboard', $props);
    }

    /** @return array{tasks: array<int, array<string, mixed>>, error: string|null} */
    private function fetchTasks(RoutineBlock $block): array
    {
        try {
            $service = new ClickUpService($block->clickUpConnection->api_token);

            /** @var array<int, string> $listIds */
            $listIds = $block->clickUpConnection->default_list_ids;
            $filters = $block->clickUpConnection->default_filters ?? [];

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
