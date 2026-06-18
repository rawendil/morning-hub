<?php

use App\Models\ClickUpConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('guest cannot fetch block tasks', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->getJson("/api/morning-hub/clickup/{$connection->id}/tasks")->assertUnauthorized();
});

test('user cannot fetch tasks from another users connection', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($otherUser)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/morning-hub/clickup/{$connection->id}/tasks")
        ->assertForbidden();
});

test('user can fetch tasks for a connection list', function () {
    Http::fake(['https://api.clickup.com/api/v2/list/list-1/task*' => Http::response([
        'tasks' => [
            ['id' => 't1', 'name' => 'Task One', 'status' => ['status' => 'open', 'color' => '#fff'], 'due_date' => null],
        ],
    ])]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'default_list_id' => 'list-1',
        'default_list_ids' => ['list-1'],
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/morning-hub/clickup/{$connection->id}/tasks")
        ->assertOk()
        ->assertJsonCount(1, 'tasks')
        ->assertJsonPath('tasks.0.id', 't1')
        ->assertJsonPath('error', null);
});

test('user can fetch aggregated tasks from multiple lists', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/list/list-1/task*' => Http::response([
            'tasks' => [['id' => 't1', 'name' => 'One', 'due_date' => null]],
        ]),
        'https://api.clickup.com/api/v2/list/list-2/task*' => Http::response([
            'tasks' => [['id' => 't2', 'name' => 'Two', 'due_date' => null]],
        ]),
    ]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'default_list_ids' => ['list-1', 'list-2'],
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/morning-hub/clickup/{$connection->id}/tasks")
        ->assertOk()
        ->assertJsonCount(2, 'tasks')
        ->assertJsonPath('error', null);
});

test('returns empty tasks when connection has no lists configured', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/morning-hub/clickup/{$connection->id}/tasks")
        ->assertOk()
        ->assertExactJson(['tasks' => [], 'error' => null]);
});

test('returns error when clickup token is invalid', function () {
    Http::fake(['https://api.clickup.com/*' => Http::response(['err' => 'Token invalid'], 401)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'default_list_id' => 'list-1',
        'default_list_ids' => ['list-1'],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/morning-hub/clickup/{$connection->id}/tasks")
        ->assertOk();

    expect($response->json('tasks'))->toBe([]);
    expect($response->json('error'))->not->toBeNull();
});
