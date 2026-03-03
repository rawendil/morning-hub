<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClickUpConnection>
 */
class ClickUpConnectionFactory extends Factory
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
            'name' => fake()->company(),
            'api_token' => 'pk_'.fake()->sha256(),
            'workspace_id' => null,
            'default_space_id' => null,
            'default_folder_id' => null,
            'default_list_id' => null,
            'default_list_ids' => null,
            'default_filters' => null,
        ];
    }
}
