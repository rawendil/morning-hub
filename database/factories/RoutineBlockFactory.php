<?php

namespace Database\Factories;

use App\Enums\BlockType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoutineBlock>
 */
class RoutineBlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $sortOrder = 0;

        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(BlockType::cases()),
            'name' => fake()->words(2, true),
            'sort_order' => $sortOrder++,
            'timer_minutes' => fake()->optional(0.5)->numberBetween(5, 30),
            'clickup_connection_id' => null,
            'config' => null,
        ];
    }
}
