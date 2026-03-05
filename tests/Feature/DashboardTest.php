<?php

use App\Enums\BlockType;
use App\Models\ClickUpConnection;
use App\Models\RoutineBlock;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('dashboard passes blocks prop with ordered blocks', function () {
    $user = User::factory()->create();
    RoutineBlock::factory()->for($user)->create(['sort_order' => 1, 'name' => 'Second']);
    RoutineBlock::factory()->for($user)->create(['sort_order' => 0, 'name' => 'First']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('blocks', 2)
            ->where('blocks.0.name', 'First')
            ->where('blocks.1.name', 'Second')
        );
});

test('dashboard has deferred props for clickup blocks with configured connections', function () {
    Http::fake(['https://api.clickup.com/api/v2/*' => Http::response(['tasks' => [
        ['id' => 't1', 'name' => 'Task One'],
    ]], 200)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'default_list_id' => 'list-123',
    ]);
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Clickup,
        'clickup_connection_id' => $connection->id,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('blocks', 1)
            ->missing("tasks_{$block->id}")
            ->loadDeferredProps(fn ($reload) => $reload
                ->has("tasks_{$block->id}")
                ->where("tasks_{$block->id}.error", null)
                ->has("tasks_{$block->id}.tasks", 1)
            )
        );
});

test('dashboard does not have deferred props for non-clickup blocks', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Braindump,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('blocks', 1)
            ->missing("tasks_{$block->id}")
        );
});

test('deferred prop catches clickup api error and returns error message', function () {
    Http::fake(['https://api.clickup.com/api/v2/*' => Http::response(['err' => 'Token invalid'], 401)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'default_list_id' => 'list-123',
    ]);
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Clickup,
        'clickup_connection_id' => $connection->id,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->loadDeferredProps(fn ($reload) => $reload
                ->has("tasks_{$block->id}")
                ->where("tasks_{$block->id}.tasks", [])
                ->where("tasks_{$block->id}.error", null)
            )
        );
});

test('dashboard has deferred props for feed blocks with configured sources', function () {
    Http::fake([
        'https://example.com/feed' => Http::response(
            '<?xml version="1.0"?><rss version="2.0"><channel><title>Test</title>'
            .'<item><title>Test Article</title><link>https://example.com/1</link><pubDate>'.now()->subHour()->toRfc2822String().'</pubDate></item>'
            .'</channel></rss>'
        ),
    ]);

    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Feed,
        'sort_order' => 0,
        'config' => [
            'sources' => [['name' => 'Test', 'url' => 'https://example.com/feed']],
            'days' => 5,
        ],
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('blocks', 1)
            ->missing("feed_{$block->id}")
            ->loadDeferredProps(fn ($reload) => $reload
                ->has("feed_{$block->id}")
                ->where("feed_{$block->id}.error", null)
                ->has("feed_{$block->id}.items", 1)
                ->where("feed_{$block->id}.items.0.title", 'Test Article')
                ->where("feed_{$block->id}.items.0.source", 'Test')
            )
        );
});

test('feed block without sources has no deferred prop', function () {
    $user = User::factory()->create();
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Feed,
        'sort_order' => 0,
        'config' => ['sources' => [], 'days' => 5],
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing("feed_{$block->id}")
        );
});

test('dashboard has deferred props for clickup blocks with multiple lists', function () {
    Http::fake(['https://api.clickup.com/api/v2/*' => Http::response(['tasks' => [
        ['id' => 't1', 'name' => 'Task One', 'due_date' => '1700000000000'],
    ]], 200)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'default_list_ids' => ['list-1', 'list-2'],
        'default_list_id' => 'list-1',
    ]);
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Clickup,
        'clickup_connection_id' => $connection->id,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('blocks', 1)
            ->missing("tasks_{$block->id}")
            ->loadDeferredProps(fn ($reload) => $reload
                ->has("tasks_{$block->id}")
                ->where("tasks_{$block->id}.error", null)
                ->has("tasks_{$block->id}.tasks")
            )
        );
});

test('dashboard passes assignee filters from connection to clickup api', function () {
    Http::fake(['https://api.clickup.com/api/v2/*' => Http::response(['tasks' => [
        ['id' => 't1', 'name' => 'My Task'],
    ]], 200)]);

    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'default_list_id' => 'list-1',
        'default_filters' => ['assignees' => [123]],
    ]);
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Clickup,
        'clickup_connection_id' => $connection->id,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->loadDeferredProps(fn ($reload) => $reload
                ->has("tasks_{$block->id}")
                ->has("tasks_{$block->id}.tasks", 1)
            )
        );

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/list/list-1/task')
            && str_contains($request->url(), 'assignees');
    });
});

test('clickup block without default_list_id has no deferred prop', function () {
    $user = User::factory()->create();
    $connection = ClickUpConnection::factory()->for($user)->create([
        'default_list_id' => null,
    ]);
    $block = RoutineBlock::factory()->for($user)->create([
        'type' => BlockType::Clickup,
        'clickup_connection_id' => $connection->id,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing("tasks_{$block->id}")
        );
});
