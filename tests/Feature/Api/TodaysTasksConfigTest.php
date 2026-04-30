<?php

use App\Models\ClickUpConnection;
use App\Models\TodaysTasksConfig;
use App\Models\User;

test('guest cannot access todays tasks config', function () {
    $this->getJson('/api/morning-hub/todays-tasks')->assertUnauthorized();
});

test('user can fetch todays tasks config', function () {
    $user = User::factory()->create();
    TodaysTasksConfig::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/morning-hub/todays-tasks')
        ->assertOk()
        ->assertJsonStructure(['config']);
});

test('user can update todays tasks config', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/morning-hub/todays-tasks', ['connection_ids' => [$connection->id]])
        ->assertOk();
});
