<?php

use App\Models\RoutineBlock;
use App\Models\User;

test('guest cannot access dashboard', function () {
    $this->getJson('/api/dashboard')->assertUnauthorized();
});

test('user can fetch dashboard data', function () {
    $user = User::factory()->create();
    RoutineBlock::factory()->for($user)->count(2)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonStructure(['blocks']);
});

test('guest cannot access todays tasks', function () {
    $this->getJson('/api/todays-tasks')->assertUnauthorized();
});

test('user can fetch todays tasks data', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/todays-tasks')
        ->assertOk()
        ->assertJsonStructure(['config', 'connections']);
});
