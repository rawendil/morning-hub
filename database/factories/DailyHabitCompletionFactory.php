<?php

namespace Database\Factories;

use App\Models\DailyHabitCompletion;
use App\Models\RoutineBlock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DailyHabitCompletion>
 */
class DailyHabitCompletionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'routine_block_id' => RoutineBlock::factory(),
            'habit_id' => (string) Str::uuid(),
            'local_date' => now()->toDateString(),
            'completed_at' => now(),
        ];
    }
}
