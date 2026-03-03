<?php

namespace Database\Seeders;

use App\Enums\BlockType;
use App\Models\ClickUpConnection;
use App\Models\RoutineBlock;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $connection = ClickUpConnection::factory()->for($user)->create([
            'name' => 'Work',
        ]);

        RoutineBlock::factory()->for($user)->create([
            'type' => BlockType::Clickup,
            'name' => 'Review tasks',
            'sort_order' => 0,
            'timer_minutes' => 15,
            'clickup_connection_id' => $connection->id,
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
