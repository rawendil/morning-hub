<?php

use App\Enums\BlockType;
use App\Models\ClickUpConnection;
use App\Models\RoutineBlock;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('guest cannot access dashboard', function () {
    $this->getJson('/api/dashboard')->assertUnauthorized();
});

test('user can fetch dashboard data', function () {
    /** @var User $user */
    $user = User::factory()->create();
    RoutineBlock::factory()->for($user)->count(2)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonStructure(['blocks', 'blocks_data']);
});

test('dashboard only returns blocks belonging to the user', function () {
    /** @var User $user */
    $user = User::factory()->create();
    /** @var User $other */
    $other = User::factory()->create();

    RoutineBlock::factory()->for($user)->count(2)->create();
    RoutineBlock::factory()->for($other)->count(3)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/dashboard')
        ->assertOk();

    expect($response->json('blocks'))->toHaveCount(2);
});

test('blocks_data includes feed data for feed blocks', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Feed,
        'config' => [
            'sources' => [['name' => 'Test', 'url' => 'https://example.com/feed']],
            'days' => 7,
        ],
    ]);

    Http::fake(['https://example.com/feed' => Http::response(
        '<?xml version="1.0"?><rss version="2.0"><channel><title>Test</title>
        <item><title>Article</title><link>https://example.com/1</link><pubDate>'.now()->format('D, d M Y H:i:s O').'</pubDate></item>
        </channel></rss>',
        200,
        ['Content-Type' => 'application/rss+xml'],
    )]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/dashboard')
        ->assertOk();

    expect($response->json("blocks_data.feed_{$block->id}"))->toHaveKeys(['items', 'error']);
    expect($response->json("blocks_data.feed_{$block->id}.error"))->toBeNull();
});

test('blocks_data degrades gracefully when feed source is unreachable', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Feed,
        'config' => [
            'sources' => [['name' => 'Bad', 'url' => 'https://unreachable.invalid/feed']],
            'days' => 7,
        ],
    ]);

    Http::fake(['*' => Http::response('', 500)]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/dashboard')
        ->assertOk();

    // FeedService catches source errors internally — returns empty items, no exception
    expect($response->json("blocks_data.feed_{$block->id}.items"))->toBe([]);
    expect($response->json("blocks_data.feed_{$block->id}.error"))->toBeNull();
});

test('blocks_data includes tasks data for clickup blocks', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'default_list_id' => 'list-1',
        'default_list_ids' => ['list-1'],
    ]);
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Clickup,
        'clickup_connection_id' => $connection->id,
    ]);

    Http::fake(['https://api.clickup.com/api/v2/list/list-1/task*' => Http::response([
        'tasks' => [['id' => 't1', 'name' => 'Task One', 'status' => ['status' => 'open', 'color' => '#fff'], 'due_date' => null]],
    ])]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/dashboard')
        ->assertOk();

    expect($response->json("blocks_data.tasks_{$block->id}"))->toHaveKeys(['tasks', 'error']);
    expect($response->json("blocks_data.tasks_{$block->id}.tasks"))->toHaveCount(1);
    expect($response->json("blocks_data.tasks_{$block->id}.error"))->toBeNull();
});

test('blocks_data includes error when clickup token is invalid', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'default_list_id' => 'list-1',
        'default_list_ids' => ['list-1'],
    ]);
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Clickup,
        'clickup_connection_id' => $connection->id,
    ]);

    Http::fake(['https://api.clickup.com/*' => Http::response(['err' => 'Token invalid'], 401)]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/dashboard')
        ->assertOk();

    expect($response->json("blocks_data.tasks_{$block->id}.tasks"))->toBe([]);
    expect($response->json("blocks_data.tasks_{$block->id}.error"))->not->toBeNull();
});

test('guest cannot access todays tasks', function () {
    $this->getJson('/api/todays-tasks')->assertUnauthorized();
});

test('user can fetch todays tasks data', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/todays-tasks')
        ->assertOk()
        ->assertJsonStructure(['config', 'connections']);
});
