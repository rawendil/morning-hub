<?php

namespace App\Services;

use App\Enums\BlockCompletionStatus;
use App\Models\DailyBlockCompletion;
use App\Models\DailyHabitCompletion;
use App\Models\RoutineBlock;
use App\Models\User;

class DailyProgressService
{
    /**
     * The user's progress for their current local day.
     *
     * @return array{
     *     date: string,
     *     blocks: array<int, array{routine_block_id: int, status: string, elapsed_seconds: int|null}>,
     *     habits: array<int, array{routine_block_id: int, habit_id: string}>
     * }
     */
    public function stateFor(User $user): array
    {
        $localDate = $user->localDate()->toDateString();

        $blocks = DailyBlockCompletion::query()
            ->where('user_id', $user->id)
            ->where('local_date', $localDate)
            ->orderBy('routine_block_id')
            ->get()
            ->map(fn (DailyBlockCompletion $completion): array => [
                'routine_block_id' => $completion->routine_block_id,
                'status' => $completion->status->value,
                'elapsed_seconds' => $completion->elapsed_seconds,
            ])
            ->all();

        $habits = DailyHabitCompletion::query()
            ->where('user_id', $user->id)
            ->where('local_date', $localDate)
            ->orderBy('id')
            ->get()
            ->map(fn (DailyHabitCompletion $completion): array => [
                'routine_block_id' => $completion->routine_block_id,
                'habit_id' => $completion->habit_id,
            ])
            ->all();

        return [
            'date' => $localDate,
            'blocks' => $blocks,
            'habits' => $habits,
        ];
    }

    public function setHabitCompletion(User $user, RoutineBlock $block, string $habitId, bool $completed): void
    {
        $localDate = $user->localDate()->toDateString();

        if (! $completed) {
            DailyHabitCompletion::query()
                ->where('user_id', $user->id)
                ->where('habit_id', $habitId)
                ->where('local_date', $localDate)
                ->delete();

            return;
        }

        DailyHabitCompletion::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'habit_id' => $habitId,
                'local_date' => $localDate,
            ],
            [
                'routine_block_id' => $block->id,
                'completed_at' => now(),
            ],
        );
    }

    public function recordBlockCompletion(
        User $user,
        RoutineBlock $block,
        BlockCompletionStatus $status,
        ?int $elapsedSeconds,
    ): void {
        DailyBlockCompletion::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'routine_block_id' => $block->id,
                'local_date' => $user->localDate()->toDateString(),
            ],
            [
                'status' => $status,
                'elapsed_seconds' => $elapsedSeconds,
                'completed_at' => now(),
            ],
        );
    }

    public function clearBlockCompletion(User $user, RoutineBlock $block): void
    {
        DailyBlockCompletion::query()
            ->where('user_id', $user->id)
            ->where('routine_block_id', $block->id)
            ->where('local_date', $user->localDate()->toDateString())
            ->delete();
    }
}
