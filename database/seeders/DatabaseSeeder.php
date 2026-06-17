<?php

namespace Database\Seeders;

use App\Enums\BlockType;
use App\Models\RoutineBlock;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
        ]);

        RoutineBlock::factory()->for($user)->create([
            'type' => BlockType::Habits,
            'name' => 'Codzienne nawyki',
            'sort_order' => 0,
            'timer_minutes' => 5,
            'config' => [
                'habits' => [
                    'Wypij szklankę wody',
                    'Medytacja 5 minut',
                    'Rozciąganie',
                ],
            ],
        ]);

        RoutineBlock::factory()->for($user)->create([
            'type' => BlockType::Braindump,
            'name' => 'Brain dump',
            'sort_order' => 1,
            'timer_minutes' => 10,
        ]);

        RoutineBlock::factory()->for($user)->create([
            'type' => BlockType::Plan,
            'name' => 'Plan the day',
            'sort_order' => 2,
            'timer_minutes' => 5,
        ]);
    }
}
