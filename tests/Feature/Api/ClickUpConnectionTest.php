<?php

use App\Models\ClickUpConnection;
use App\Models\User;

test('guest cannot access clickup connections', function () {
    $this->getJson('/api/morning-hub/clickup')->assertUnauthorized();
});

test('user can fetch their clickup connections', function () {
    $user = User::factory()->create();
    ClickUpConnection::factory()->for($user)->create(['name' => 'My Connection']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/morning-hub/clickup')
        ->assertOk()
        ->assertJsonCount(1, 'connections')
        ->assertJsonPath('connections.0.name', 'My Connection');
});

test('user cannot access another user\'s connection', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($owner)->create();

    $this->actingAs($other, 'sanctum')
        ->deleteJson("/api/morning-hub/clickup/connections/{$connection->id}")
        ->assertForbidden();
});
