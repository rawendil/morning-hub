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
