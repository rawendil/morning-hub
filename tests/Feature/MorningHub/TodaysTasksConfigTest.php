<?php

use App\Models\ClickUpConnection;
use App\Models\TodaysTasksConfig;
use App\Models\User;

test('guest cannot access config page', function () {
    $this->get(route('morning-hub.todays-tasks.index'))->assertRedirect(route('login'));
});

test('user can view config page with connections', function () {
    $user = User::factory()->create();
    ClickUpConnection::factory()->for($user)->create(['name' => 'Work']);

    $this->actingAs($user)
        ->get(route('morning-hub.todays-tasks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('morning-hub/TodaysTasksConfig')
            ->has('connections', 1)
            ->where('connections.0.name', 'Work')
        );
});

test('config page returns existing config data', function () {
    $user = User::factory()->create();
    $conn = ClickUpConnection::factory()->for($user)->create();
    TodaysTasksConfig::factory()->create([
        'user_id' => $user->id,
        'connection_ids' => [$conn->id],
    ]);

    $this->actingAs($user)
        ->get(route('morning-hub.todays-tasks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('config.connection_ids', [$conn->id])
        );
});

test('user can update config with valid connection ids', function () {
    $user = User::factory()->create();
    $conn1 = ClickUpConnection::factory()->for($user)->create();
    $conn2 = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('morning-hub.todays-tasks.update'), [
            'connection_ids' => [$conn1->id, $conn2->id],
        ])
        ->assertRedirect(route('morning-hub.todays-tasks.index'));

    $config = $user->todaysTasksConfig;
    expect($config)->not->toBeNull();
    expect($config->connection_ids)->toBe([$conn1->id, $conn2->id]);
});

test('update rejects other users connection ids', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherConn = ClickUpConnection::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->put(route('morning-hub.todays-tasks.update'), [
            'connection_ids' => [$otherConn->id],
        ])
        ->assertSessionHasErrors('connection_ids');
});

test('update allows empty connection ids', function () {
    $user = User::factory()->create();
    $conn = ClickUpConnection::factory()->for($user)->create();
    TodaysTasksConfig::factory()->create([
        'user_id' => $user->id,
        'connection_ids' => [$conn->id],
    ]);

    $this->actingAs($user)
        ->put(route('morning-hub.todays-tasks.update'), [
            'connection_ids' => [],
        ])
        ->assertRedirect(route('morning-hub.todays-tasks.index'));

    expect($user->todaysTasksConfig->fresh()->connection_ids)->toBe([]);
});
