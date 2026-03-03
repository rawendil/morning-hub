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
