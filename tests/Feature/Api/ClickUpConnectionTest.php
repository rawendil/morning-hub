<?php

use App\Models\ClickUpConnection;
use App\Models\User;
use App\Services\ClickUpServiceFactory;

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

test('user can create a clickup connection', function () {
    $user = User::factory()->create();

    $factory = Mockery::mock(ClickUpServiceFactory::class);
    $service = Mockery::mock(\App\Services\ClickUpService::class);
    $service->shouldReceive('testConnection')->andReturn(true);
    $factory->shouldReceive('make')->andReturn($service);
    app()->instance(ClickUpServiceFactory::class, $factory);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/clickup/connections', [
            'name' => 'New Connection',
            'api_token' => 'pk_valid_token',
        ])
        ->assertCreated();
});

test('user cannot create connection with invalid token', function () {
    $user = User::factory()->create();

    $factory = Mockery::mock(ClickUpServiceFactory::class);
    $service = Mockery::mock(\App\Services\ClickUpService::class);
    $service->shouldReceive('testConnection')->andReturn(false);
    $factory->shouldReceive('make')->andReturn($service);
    app()->instance(ClickUpServiceFactory::class, $factory);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/clickup/connections', [
            'name' => 'Bad Connection',
            'api_token' => 'pk_bad_token',
        ])
        ->assertUnprocessable();
});

test('user cannot access another user\'s connection', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($owner)->create();

    $this->actingAs($other, 'sanctum')
        ->deleteJson("/api/morning-hub/clickup/connections/{$connection->id}")
        ->assertForbidden();
});
