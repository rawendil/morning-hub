<?php

use App\Models\ClickUpConnection;
use App\Models\TodaysTasksConfig;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('guests are redirected to the login page', function () {
    $this->get(route('todays-tasks'))->assertRedirect(route('login'));
});

test('authenticated user can visit todays tasks page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('todays-tasks'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TodaysTasks')
            ->where('hasConfig', false)
        );
});

test('page has deferred props when config has connection ids', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/user' => Http::response([
            'user' => ['id' => 999, 'username' => 'testuser', 'email' => 'test@test.com'],
        ], 200),
        'https://api.clickup.com/api/v2/list/*/task*' => Http::response([
            'tasks' => [['id' => 't1', 'name' => 'Today Task', 'due_date' => (string) now()->getTimestampMs()]],
        ], 200),
        'https://api.clickup.com/api/v2/list/*' => Http::response([
            'statuses' => [
                ['status' => 'open', 'color' => '#d3d3d3', 'orderindex' => 0, 'type' => 'open'],
                ['status' => 'in progress', 'color' => '#4194f6', 'orderindex' => 1, 'type' => 'custom'],
                ['status' => 'closed', 'color' => '#6bc950', 'orderindex' => 2, 'type' => 'closed'],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'default_list_ids' => ['list-1'],
    ]);
    TodaysTasksConfig::factory()->create([
        'user_id' => $user->id,
        'connection_ids' => [$connection->id],
    ]);

    $this->actingAs($user)
        ->get(route('todays-tasks'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TodaysTasks')
            ->where('hasConfig', true)
            ->missing('todaysTasksData')
            ->loadDeferredProps(fn ($reload) => $reload
                ->has('todaysTasksData')
                ->has('todaysTasksData.groups', 1)
                ->where('todaysTasksData.groups.0.connectionId', $connection->id)
            )
        );
});

test('page has no deferred props when config has empty connection ids', function () {
    $user = User::factory()->create();
    TodaysTasksConfig::factory()->create([
        'user_id' => $user->id,
        'connection_ids' => [],
    ]);

    $this->actingAs($user)
        ->get(route('todays-tasks'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('hasConfig', false)
            ->missing('todaysTasksData')
        );
});

test('page has no deferred props when no config exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('todays-tasks'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('hasConfig', false)
            ->missing('todaysTasksData')
        );
});
