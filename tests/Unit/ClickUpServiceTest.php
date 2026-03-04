<?php

use App\Services\ClickUpService;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

test('testConnection returns true on successful response', function () {
    Http::fake(['https://api.clickup.com/api/v2/team' => Http::response(['teams' => []], 200)]);
    $service = new ClickUpService('test-token');
    expect($service->testConnection())->toBeTrue();
});

test('testConnection returns false on unauthorized response', function () {
    Http::fake(['https://api.clickup.com/api/v2/team' => Http::response(['err' => 'Token invalid'], 401)]);
    $service = new ClickUpService('bad-token');
    expect($service->testConnection())->toBeFalse();
});

test('getWorkspaces returns teams array', function () {
    Http::fake(['https://api.clickup.com/api/v2/team' => Http::response([
        'teams' => [
            ['id' => '123', 'name' => 'My Workspace'],
            ['id' => '456', 'name' => 'Other Workspace'],
        ],
    ], 200)]);
    $service = new ClickUpService('test-token');
    $workspaces = $service->getWorkspaces();
    expect($workspaces)->toHaveCount(2);
    expect($workspaces[0])->toMatchArray(['id' => '123', 'name' => 'My Workspace']);
});

test('getSpaces returns spaces for workspace', function () {
    Http::fake(['https://api.clickup.com/api/v2/team/123/space*' => Http::response([
        'spaces' => [
            ['id' => 's1', 'name' => 'Space One'],
        ],
    ], 200)]);
    $service = new ClickUpService('test-token');
    $spaces = $service->getSpaces('123');
    expect($spaces)->toHaveCount(1);
    expect($spaces[0]['name'])->toBe('Space One');
});

test('getFolders returns folders for space', function () {
    Http::fake(['https://api.clickup.com/api/v2/space/s1/folder*' => Http::response([
        'folders' => [
            ['id' => 'f1', 'name' => 'Folder One'],
        ],
    ], 200)]);
    $service = new ClickUpService('test-token');
    $folders = $service->getFolders('s1');
    expect($folders)->toHaveCount(1);
    expect($folders[0]['name'])->toBe('Folder One');
});

test('getLists returns lists for folder', function () {
    Http::fake(['https://api.clickup.com/api/v2/folder/f1/list*' => Http::response([
        'lists' => [
            ['id' => 'l1', 'name' => 'List One'],
        ],
    ], 200)]);
    $service = new ClickUpService('test-token');
    $lists = $service->getLists('f1');
    expect($lists)->toHaveCount(1);
    expect($lists[0]['name'])->toBe('List One');
});

test('getFolderlessLists returns folderless lists for space', function () {
    Http::fake(['https://api.clickup.com/api/v2/space/s1/list*' => Http::response([
        'lists' => [
            ['id' => 'l2', 'name' => 'Folderless List'],
        ],
    ], 200)]);
    $service = new ClickUpService('test-token');
    $lists = $service->getFolderlessLists('s1');
    expect($lists)->toHaveCount(1);
    expect($lists[0]['name'])->toBe('Folderless List');
});

test('service sends correct authorization header', function () {
    Http::fake();
    $service = new ClickUpService('pk_my_secret_token');
    $service->getWorkspaces();
    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'pk_my_secret_token');
    });
});

test('getTasks returns tasks for list', function () {
    Http::fake(['https://api.clickup.com/api/v2/list/l1/task*' => Http::response([
        'tasks' => [
            ['id' => 't1', 'name' => 'Task One', 'status' => ['status' => 'open', 'color' => '#d3d3d3']],
            ['id' => 't2', 'name' => 'Task Two', 'status' => ['status' => 'in progress', 'color' => '#4194f6']],
        ],
    ], 200)]);
    $service = new ClickUpService('test-token');
    $tasks = $service->getTasks('l1');
    expect($tasks)->toHaveCount(2);
    expect($tasks[0])->toMatchArray(['id' => 't1', 'name' => 'Task One']);
});

test('getTasks passes default and custom filters as query params', function () {
    Http::fake(['https://api.clickup.com/api/v2/list/l1/task*' => Http::response(['tasks' => []], 200)]);
    $service = new ClickUpService('test-token');
    $service->getTasks('l1', ['due_date_lt' => '1709510400000']);
    Http::assertSent(function ($request) {
        $url = $request->url();

        return str_contains($url, 'include_closed=false')
            && str_contains($url, 'subtasks=true')
            && str_contains($url, 'due_date_lt=1709510400000');
    });
});

test('getTasks returns empty array on error', function () {
    Http::fake(['https://api.clickup.com/api/v2/list/l1/task*' => Http::response(['err' => 'Unauthorized'], 401)]);
    $service = new ClickUpService('bad-token');
    $tasks = $service->getTasks('l1');
    expect($tasks)->toBe([]);
});

test('getTask returns single task object', function () {
    Http::fake(['https://api.clickup.com/api/v2/task/t1*' => Http::response([
        'id' => 't1',
        'name' => 'Task One',
        'description' => 'Some description',
        'status' => ['status' => 'open', 'color' => '#d3d3d3'],
        'subtasks' => [['id' => 'st1', 'name' => 'Subtask']],
    ], 200)]);
    $service = new ClickUpService('test-token');
    $task = $service->getTask('t1');
    expect($task)->toMatchArray(['id' => 't1', 'name' => 'Task One']);
    expect($task['description'])->toBe('Some description');
});

test('getTask includes subtasks query param', function () {
    Http::fake(['https://api.clickup.com/api/v2/task/t1*' => Http::response(['id' => 't1', 'name' => 'Task'], 200)]);
    $service = new ClickUpService('test-token');
    $service->getTask('t1');
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'include_subtasks=true');
    });
});

test('updateTask sends PUT request with data', function () {
    Http::fake(['https://api.clickup.com/api/v2/task/t1' => Http::response([
        'id' => 't1',
        'name' => 'Task One',
        'status' => ['status' => 'in progress', 'color' => '#4194f6'],
    ], 200)]);
    $service = new ClickUpService('test-token');
    $result = $service->updateTask('t1', ['status' => 'in progress']);
    expect($result)->toMatchArray(['id' => 't1']);
    Http::assertSent(function ($request) {
        return $request->method() === 'PUT'
            && str_contains($request->url(), '/task/t1')
            && $request['status'] === 'in progress';
    });
});

test('createTask sends POST to list endpoint', function () {
    Http::fake(['https://api.clickup.com/api/v2/list/l1/task' => Http::response([
        'id' => 't-new',
        'name' => 'New task',
    ], 200)]);
    $service = new ClickUpService('test-token');
    $result = $service->createTask('l1', ['name' => 'New task']);
    expect($result)->toMatchArray(['id' => 't-new', 'name' => 'New task']);
    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && str_contains($request->url(), '/list/l1/task')
            && $request['name'] === 'New task';
    });
});

test('createComment sends POST with comment text', function () {
    Http::fake(['https://api.clickup.com/api/v2/task/t1/comment' => Http::response([
        'id' => 'c-new',
        'comment_text' => 'Great work',
    ], 200)]);
    $service = new ClickUpService('test-token');
    $result = $service->createComment('t1', 'Great work');
    expect($result)->toMatchArray(['id' => 'c-new']);
    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && str_contains($request->url(), '/task/t1/comment')
            && $request['comment_text'] === 'Great work'
            && $request['notify_all'] === false;
    });
});

test('getComments returns comments array', function () {
    Http::fake(['https://api.clickup.com/api/v2/task/t1/comment*' => Http::response([
        'comments' => [
            ['id' => 'c1', 'comment_text' => 'Hello'],
            ['id' => 'c2', 'comment_text' => 'World'],
        ],
    ], 200)]);
    $service = new ClickUpService('test-token');
    $comments = $service->getComments('t1');
    expect($comments)->toHaveCount(2);
    expect($comments[0])->toMatchArray(['id' => 'c1', 'comment_text' => 'Hello']);
});

test('getListStatuses returns statuses from list endpoint', function () {
    Http::fake(['https://api.clickup.com/api/v2/list/l1' => Http::response([
        'id' => 'l1',
        'name' => 'My List',
        'statuses' => [
            ['status' => 'open', 'color' => '#d3d3d3', 'orderindex' => 0, 'type' => 'open'],
            ['status' => 'in progress', 'color' => '#4194f6', 'orderindex' => 1, 'type' => 'custom'],
        ],
    ], 200)]);
    $service = new ClickUpService('test-token');
    $statuses = $service->getListStatuses('l1');
    expect($statuses)->toHaveCount(2);
    expect($statuses[0])->toMatchArray(['status' => 'open', 'color' => '#d3d3d3']);
});

test('getAllListsInSpace returns folders with lists and folderless lists', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/space/s1/folder*' => Http::response([
            'folders' => [
                [
                    'id' => 'f1',
                    'name' => 'Sprint',
                    'lists' => [
                        ['id' => 'l1', 'name' => 'Backlog'],
                        ['id' => 'l2', 'name' => 'In Progress'],
                    ],
                ],
            ],
        ], 200),
        'https://api.clickup.com/api/v2/space/s1/list*' => Http::response([
            'lists' => [
                ['id' => 'l3', 'name' => 'Folderless Tasks'],
            ],
        ], 200),
    ]);

    $service = new ClickUpService('test-token');
    $result = $service->getAllListsInSpace('s1');

    expect($result)->toHaveKeys(['folders', 'folderless']);
    expect($result['folders'])->toHaveCount(1);
    expect($result['folders'][0])->toMatchArray(['id' => 'f1', 'name' => 'Sprint']);
    expect($result['folders'][0]['lists'])->toHaveCount(2);
    expect($result['folderless'])->toHaveCount(1);
    expect($result['folderless'][0])->toMatchArray(['id' => 'l3', 'name' => 'Folderless Tasks']);
});

test('getTasksFromLists fetches from multiple lists in parallel', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/list/l1/task*' => Http::response([
            'tasks' => [
                ['id' => 't1', 'name' => 'Task A', 'due_date' => '1709510400000'],
            ],
        ], 200),
        'https://api.clickup.com/api/v2/list/l2/task*' => Http::response([
            'tasks' => [
                ['id' => 't2', 'name' => 'Task B', 'due_date' => '1709424000000'],
            ],
        ], 200),
    ]);

    $service = new ClickUpService('test-token');
    $tasks = $service->getTasksFromLists(['l1', 'l2']);

    expect($tasks)->toHaveCount(2);
    // Sorted by due_date ascending — t2 (earlier) before t1
    expect($tasks[0]['id'])->toBe('t2');
    expect($tasks[1]['id'])->toBe('t1');
});

test('getTasksFromLists with single list delegates to getTasks', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/list/l1/task*' => Http::response([
            'tasks' => [
                ['id' => 't1', 'name' => 'Task A'],
            ],
        ], 200),
    ]);

    $service = new ClickUpService('test-token');
    $tasks = $service->getTasksFromLists(['l1']);

    expect($tasks)->toHaveCount(1);
    expect($tasks[0]['id'])->toBe('t1');
});

test('getTasksFromLists with empty array returns empty', function () {
    Http::fake();
    $service = new ClickUpService('test-token');
    $tasks = $service->getTasksFromLists([]);
    expect($tasks)->toBe([]);
    Http::assertNothingSent();
});

test('getAuthenticatedUser returns user data', function () {
    Http::fake(['https://api.clickup.com/api/v2/user' => Http::response([
        'user' => ['id' => 123, 'username' => 'Test User', 'email' => 'test@example.com'],
    ], 200)]);
    $service = new ClickUpService('test-token');
    $user = $service->getAuthenticatedUser();
    expect($user)->toMatchArray(['id' => 123, 'username' => 'Test User', 'email' => 'test@example.com']);
});

test('getTasksFromLists handles partial failures', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/list/l1/task*' => Http::response([
            'tasks' => [
                ['id' => 't1', 'name' => 'Task A', 'due_date' => null],
            ],
        ], 200),
        'https://api.clickup.com/api/v2/list/l2/task*' => Http::response(['err' => 'Not found'], 500),
    ]);

    $service = new ClickUpService('test-token');
    $tasks = $service->getTasksFromLists(['l1', 'l2']);

    expect($tasks)->toHaveCount(1);
    expect($tasks[0]['id'])->toBe('t1');
});
