<?php

namespace Database\Factories;

use App\Enums\BlockCompletionStatus;
use App\Models\RoutineBlock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DailyBlockCompletion>
 */
class DailyBlockCompletionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'routine_block_id' => RoutineBlock::factory(),
            'local_date' => now()->toDateString(),
            'status' => BlockCompletionStatus::Completed,
            'elapsed_seconds' => fake()->numberBetween(30, 1800),
            'completed_at' => now(),
        ];
    }

    public function skipped(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => BlockCompletionStatus::Skipped,
        ]);
    }
}
