<?php

namespace App\Http\Controllers\Api;

use App\Enums\BlockType;
use App\Http\Controllers\Controller;
use App\Models\RoutineBlock;
use App\Services\ClickUpBlockTaskService;
use App\Services\DailyProgressService;
use App\Services\FeedService;
use App\Services\GoogleCalendarServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly GoogleCalendarServiceFactory $googleCalendarServiceFactory,
        private readonly ClickUpBlockTaskService $clickUpBlockTaskService,
        private readonly DailyProgressService $dailyProgressService,
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
            'daily_progress' => $this->dailyProgressService->stateFor($user),
        ]);
    }

    /** @param array<string, mixed> $blocksData */
    private function appendTasksData(RoutineBlock $block, array &$blocksData): void
    {
        if (! $block->clickUpConnection) {
            return;
        }

        $blocksData["tasks_{$block->id}"] = $this->clickUpBlockTaskService->forConnection($block->clickUpConnection);
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
