<?php

namespace App\Http\Controllers\Api;

use App\Enums\BlockCompletionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\MorningHub\RecordBlockCompletionRequest;
use App\Http\Requests\MorningHub\StoreHabitCompletionRequest;
use App\Models\RoutineBlock;
use App\Models\User;
use App\Services\DailyProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DailyProgressController extends Controller
{
    public function __construct(
        private readonly DailyProgressService $dailyProgressService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json($this->dailyProgressService->stateFor($user));
    }

    public function storeHabit(StoreHabitCompletionRequest $request, RoutineBlock $block): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->dailyProgressService->setHabitCompletion(
            $user,
            $block,
            $request->validated('habit_id'),
            $request->boolean('completed'),
        );

        return response()->json($this->dailyProgressService->stateFor($user));
    }

    public function update(RecordBlockCompletionRequest $request, RoutineBlock $block): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->dailyProgressService->recordBlockCompletion(
            $user,
            $block,
            BlockCompletionStatus::from($request->validated('status')),
            $request->validated('elapsed_seconds'),
        );

        return response()->json($this->dailyProgressService->stateFor($user));
    }

    public function destroy(Request $request, RoutineBlock $block): JsonResponse
    {
        Gate::authorize('view', $block);

        /** @var User $user */
        $user = $request->user();

        $this->dailyProgressService->clearBlockCompletion($user, $block);

        return response()->json($this->dailyProgressService->stateFor($user));
    }
}
