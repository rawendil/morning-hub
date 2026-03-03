<?php

use App\Models\ClickUpConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('guest cannot access clickup api proxy', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->get(route('morning-hub.clickup.workspaces', $connection))->assertRedirect(route('login'));
});

test('user cannot access another users connection workspaces', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->get(route('morning-hub.clickup.workspaces', $connection))
        ->assertForbidden();
});

test('user can fetch workspaces', function () {
    Http::fake(['https://api.clickup.com/api/v2/team' => Http::response([
        'teams' => [
            ['id' => '123', 'name' => 'My Workspace'],
        ],
    ], 200)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('morning-hub.clickup.workspaces', $connection))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', '123')
        ->assertJsonPath('data.0.name', 'My Workspace');
});

test('user can fetch spaces for a workspace', function () {
    Http::fake(['https://api.clickup.com/api/v2/team/123/space*' => Http::response([
        'spaces' => [
            ['id' => 's1', 'name' => 'Engineering'],
        ],
    ], 200)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('morning-hub.clickup.spaces', ['connection' => $connection, 'workspace_id' => '123']))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('spaces endpoint requires workspace_id', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->getJson(route('morning-hub.clickup.spaces', $connection))
        ->assertStatus(422);
});

test('user can fetch folders for a space', function () {
    Http::fake(['https://api.clickup.com/api/v2/space/s1/folder*' => Http::response([
        'folders' => [
            ['id' => 'f1', 'name' => 'Sprint 1'],
        ],
    ], 200)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('morning-hub.clickup.folders', ['connection' => $connection, 'space_id' => 's1']))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('user can fetch lists for a folder', function () {
    Http::fake(['https://api.clickup.com/api/v2/folder/f1/list*' => Http::response([
        'lists' => [
            ['id' => 'l1', 'name' => 'Todo'],
        ],
    ], 200)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('morning-hub.clickup.lists', ['connection' => $connection, 'folder_id' => 'f1']))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('user can fetch folderless lists for a space', function () {
    Http::fake(['https://api.clickup.com/api/v2/space/s1/list*' => Http::response([
        'lists' => [
            ['id' => 'l2', 'name' => 'Backlog'],
        ],
    ], 200)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('morning-hub.clickup.lists', ['connection' => $connection, 'space_id' => 's1']))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
