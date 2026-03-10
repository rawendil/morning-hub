<?php

use App\Models\ClickUpConnection;
use App\Models\User;
use App\Services\TodaysTasksService;
use Illuminate\Support\Facades\Http;

test('TodaysTasksService filters out connections not owned by user', function () {
    Http::fake(['https://api.clickup.com/*' => Http::response(['user' => ['id' => 123]], 200)]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $connectionA = ClickUpConnection::factory()->for($userA)->create();
    $connectionB = ClickUpConnection::factory()->for($userB)->create();

    $service = app(TodaysTasksService::class);
    $result = $service->fetchGroupedTasks($userA, [$connectionA->id, $connectionB->id]);

    expect($result['groups'])->toHaveCount(1);
    expect($result['groups'][0]['connectionId'])->toBe($connectionA->id);
});

test('api_token is never exposed in connection index page props', function () {
    $user = User::factory()->create();
    ClickUpConnection::factory()->for($user)->create(['api_token' => 'pk_secret_token']);

    $this->actingAs($user)
        ->get(route('morning-hub.clickup.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('connections', 1)
            ->missing('connections.0.api_token')
        );
});

test('audit log is written when connection is created', function () {
    $logFile = storage_path('logs/security-'.now()->format('Y-m-d').'.log');
    if (file_exists($logFile)) {
        unlink($logFile);
    }

    Http::fake(['https://api.clickup.com/api/v2/team' => Http::response(['teams' => []], 200)]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('morning-hub.clickup.store'), [
            'name' => 'Audit Test',
            'api_token' => 'pk_audit_token',
        ]);

    expect(file_exists($logFile))->toBeTrue();
    $logContent = file_get_contents($logFile);
    expect($logContent)->toContain('ClickUp connection created');
    expect($logContent)->toContain('Audit Test');
    expect($logContent)->not->toContain('pk_audit_token');
});

test('audit log is written when connection is deleted', function () {
    $logFile = storage_path('logs/security-'.now()->format('Y-m-d').'.log');
    if (file_exists($logFile)) {
        unlink($logFile);
    }

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create(['name' => 'Delete Me']);

    $this->actingAs($user)
        ->delete(route('morning-hub.clickup.destroy', $connection));

    expect(file_exists($logFile))->toBeTrue();
    $logContent = file_get_contents($logFile);
    expect($logContent)->toContain('ClickUp connection deleted');
    expect($logContent)->toContain('Delete Me');
});

test('cross-user access to connection API proxy is forbidden', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->get(route('morning-hub.clickup.workspaces', $connection))
        ->assertForbidden();
});

test('store endpoint is rate limited', function () {
    Http::fake(['https://api.clickup.com/api/v2/team' => Http::response(['teams' => []], 200)]);

    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($user)
            ->post(route('morning-hub.clickup.store'), [
                'name' => "Connection $i",
                'api_token' => 'pk_valid_token',
            ]);
    }

    $this->actingAs($user)
        ->post(route('morning-hub.clickup.store'), [
            'name' => 'Connection overflow',
            'api_token' => 'pk_valid_token',
        ])
        ->assertStatus(429);
});
