<?php

namespace Database\Factories;

use App\Models\TodaysTasksConfig;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TodaysTasksConfig>
 */
class TodaysTasksConfigFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'connection_ids' => null,
        ];
    }
}
