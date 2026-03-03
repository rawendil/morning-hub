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

test('user can fetch task detail', function () {
    Http::fake(['https://api.clickup.com/api/v2/task/t1*' => Http::response([
        'id' => 't1',
        'name' => 'Fix bug',
        'description' => 'Need to fix the login bug',
        'status' => ['status' => 'open', 'color' => '#d3d3d3'],
    ], 200)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('morning-hub.clickup.task', ['connection' => $connection, 'taskId' => 't1']))
        ->assertOk()
        ->assertJsonPath('data.id', 't1')
        ->assertJsonPath('data.name', 'Fix bug')
        ->assertJsonPath('data.description', 'Need to fix the login bug');
});

test('user cannot fetch task detail from another users connection', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->get(route('morning-hub.clickup.task', ['connection' => $connection, 'taskId' => 't1']))
        ->assertForbidden();
});

// --- Phase 3: Write operations ---

test('user can update task status', function () {
    Http::fake(['https://api.clickup.com/api/v2/task/t1' => Http::response([
        'id' => 't1',
        'status' => ['status' => 'in progress', 'color' => '#4194f6'],
    ], 200)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->putJson(route('morning-hub.clickup.updateTask', ['connection' => $connection, 'taskId' => 't1']), [
            'status' => 'in progress',
        ])
        ->assertOk()
        ->assertJsonPath('data.id', 't1');
});

test('user can update task priority', function () {
    Http::fake(['https://api.clickup.com/api/v2/task/t1' => Http::response([
        'id' => 't1',
        'priority' => ['id' => '2', 'priority' => 'high', 'color' => '#ffcc00'],
    ], 200)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->putJson(route('morning-hub.clickup.updateTask', ['connection' => $connection, 'taskId' => 't1']), [
            'priority' => 2,
        ])
        ->assertOk();
});

test('update task validates priority range', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->putJson(route('morning-hub.clickup.updateTask', ['connection' => $connection, 'taskId' => 't1']), [
            'priority' => 5,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('priority');
});

test('user cannot update task on another users connection', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->putJson(route('morning-hub.clickup.updateTask', ['connection' => $connection, 'taskId' => 't1']), [
            'status' => 'done',
        ])
        ->assertForbidden();
});

test('guest cannot update task', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->putJson(route('morning-hub.clickup.updateTask', ['connection' => $connection, 'taskId' => 't1']), [
        'status' => 'done',
    ])->assertUnauthorized();
});

test('user can create task', function () {
    Http::fake(['https://api.clickup.com/api/v2/list/l1/task' => Http::response([
        'id' => 't-new',
        'name' => 'New task',
    ], 200)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson(route('morning-hub.clickup.createTask', $connection), [
            'list_id' => 'l1',
            'name' => 'New task',
        ])
        ->assertCreated()
        ->assertJsonPath('data.id', 't-new')
        ->assertJsonPath('data.name', 'New task');
});

test('create task requires name and list_id', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson(route('morning-hub.clickup.createTask', $connection), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['list_id', 'name']);
});

test('user cannot create task on another users connection', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->postJson(route('morning-hub.clickup.createTask', $connection), [
            'list_id' => 'l1',
            'name' => 'New task',
        ])
        ->assertForbidden();
});

test('user can add comment to task', function () {
    Http::fake(['https://api.clickup.com/api/v2/task/t1/comment' => Http::response([
        'id' => 'c-new',
        'comment_text' => 'Great work',
    ], 200)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson(route('morning-hub.clickup.createComment', ['connection' => $connection, 'taskId' => 't1']), [
            'comment_text' => 'Great work',
        ])
        ->assertCreated()
        ->assertJsonPath('data.id', 'c-new');
});

test('create comment requires comment_text', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson(route('morning-hub.clickup.createComment', ['connection' => $connection, 'taskId' => 't1']), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('comment_text');
});

test('user cannot add comment on another users connection', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->postJson(route('morning-hub.clickup.createComment', ['connection' => $connection, 'taskId' => 't1']), [
            'comment_text' => 'Hello',
        ])
        ->assertForbidden();
});

test('user can fetch task comments', function () {
    Http::fake(['https://api.clickup.com/api/v2/task/t1/comment*' => Http::response([
        'comments' => [
            ['id' => 'c1', 'comment_text' => 'Hello'],
        ],
    ], 200)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->getJson(route('morning-hub.clickup.comments', ['connection' => $connection, 'taskId' => 't1']))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('user can fetch list statuses', function () {
    Http::fake(['https://api.clickup.com/api/v2/list/l1' => Http::response([
        'id' => 'l1',
        'statuses' => [
            ['status' => 'open', 'color' => '#d3d3d3'],
            ['status' => 'in progress', 'color' => '#4194f6'],
        ],
    ], 200)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->getJson(route('morning-hub.clickup.statuses', ['connection' => $connection, 'list_id' => 'l1']))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('statuses endpoint requires list_id', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create();

    $this->actingAs($user)
        ->getJson(route('morning-hub.clickup.statuses', $connection))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('list_id');
});
