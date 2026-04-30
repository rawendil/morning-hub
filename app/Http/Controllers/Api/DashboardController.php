<?php

namespace App\Http\Controllers\Api;

use App\Enums\BlockType;
use App\Http\Controllers\Controller;
use App\Models\RoutineBlock;
use App\Services\ClickUpService;
use App\Services\FeedService;
use App\Services\GoogleCalendarServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly GoogleCalendarServiceFactory $googleCalendarServiceFactory,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $blocks = $user->routineBlocks()
            ->ordered()
            ->with(['clickUpConnection', 'googleCalendarConnection'])
            ->get();

        $blocksData = [];

        foreach ($blocks as $block) {
            match ($block->type) {
                BlockType::Clickup => $this->appendTasksData($block, $blocksData),
                BlockType::Feed => $this->appendFeedData($block, $blocksData),
                BlockType::GoogleCalendar => $this->appendCalendarData($block, $blocksData),
                default => null,
            };
        }

        return response()->json([
            'blocks' => $blocks,
            'blocks_data' => $blocksData,
        ]);
    }

    /** @param array<string, mixed> $blocksData */
    private function appendTasksData(RoutineBlock $block, array &$blocksData): void
    {
        if (! $block->clickUpConnection) {
            return;
        }

        $hasLists = ! empty($block->clickUpConnection->default_list_ids)
            || $block->clickUpConnection->default_list_id;

        if (! $hasLists) {
            return;
        }

        try {
            $service = new ClickUpService($block->clickUpConnection->api_token);

            /** @var array<int, string> $listIds */
            $listIds = $block->clickUpConnection->default_list_ids;
            $filters = $block->clickUpConnection->default_filters ?? [];

            $tasks = ! empty($listIds)
                ? $service->getTasksFromLists($listIds, $filters)
                : $service->getTasks($block->clickUpConnection->default_list_id, $filters);

            $blocksData["tasks_{$block->id}"] = ['tasks' => $tasks, 'error' => null];
        } catch (\Throwable $e) {
            $blocksData["tasks_{$block->id}"] = ['tasks' => [], 'error' => $e->getMessage()];
        }
    }

    /** @param array<string, mixed> $blocksData */
    private function appendFeedData(RoutineBlock $block, array &$blocksData): void
    {
        $sources = $block->config['sources'] ?? [];
        $days = (int) ($block->config['days'] ?? 5);

        if (empty($sources)) {
            return;
        }

        try {
            $service = new FeedService;
            $blocksData["feed_{$block->id}"] = ['items' => $service->fetchArticles($sources, $days), 'error' => null];
        } catch (\Throwable $e) {
            $blocksData["feed_{$block->id}"] = ['items' => [], 'error' => $e->getMessage()];
        }
    }

    /** @param array<string, mixed> $blocksData */
    private function appendCalendarData(RoutineBlock $block, array &$blocksData): void
    {
        if (! $block->googleCalendarConnection) {
            return;
        }

        try {
            $events = $this->googleCalendarServiceFactory
                ->make($block->googleCalendarConnection)
                ->getEventsForDashboard();

            $blocksData["events_{$block->id}"] = $events;
        } catch (\Throwable $e) {
            $blocksData["events_{$block->id}"] = ['events' => [], 'error' => $e->getMessage()];
        }
    }
}
