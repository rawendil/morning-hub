<?php

use App\Enums\BlockType;
use App\Models\GoogleCalendarConnection;
use App\Models\RoutineBlock;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('dashboard has deferred props for google calendar blocks', function () {
    Http::fake(['https://www.googleapis.com/calendar/v3/*' => Http::response([
        'summary' => 'My Calendar',
        'items' => [
            [
                'id' => 'evt1',
                'summary' => 'Team standup',
                'start' => ['dateTime' => now()->setTime(9, 0)->toRfc3339String()],
                'end' => ['dateTime' => now()->setTime(9, 30)->toRfc3339String()],
            ],
        ],
    ], 200)]);

    $user = User::factory()->create(['google_id' => 'google-123']);
    $connection = GoogleCalendarConnection::factory()->create([
        'user_id' => $user->id,
        'calendar_ids' => ['primary'],
    ]);
    $block = RoutineBlock::factory()->create([
        'user_id' => $user->id,
        'type' => BlockType::GoogleCalendar,
        'google_calendar_connection_id' => $connection->id,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('blocks', 1)
            ->missing("events_{$block->id}")
            ->loadDeferredProps(fn ($reload) => $reload
                ->has("events_{$block->id}")
                ->where("events_{$block->id}.error", null)
                ->has("events_{$block->id}.events", 1)
            )
        );
});

test('dashboard handles google calendar block without connection', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);
    $block = RoutineBlock::factory()->create([
        'user_id' => $user->id,
        'type' => BlockType::GoogleCalendar,
        'google_calendar_connection_id' => null,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing("events_{$block->id}")
        );
});

test('deferred prop catches google calendar auth error', function () {
    Http::fake(['https://www.googleapis.com/calendar/v3/*' => Http::response(['error' => 'invalid_grant'], 401)]);

    $user = User::factory()->create(['google_id' => 'google-123']);
    $connection = GoogleCalendarConnection::factory()->create([
        'user_id' => $user->id,
        'calendar_ids' => ['primary'],
    ]);
    $block = RoutineBlock::factory()->create([
        'user_id' => $user->id,
        'type' => BlockType::GoogleCalendar,
        'google_calendar_connection_id' => $connection->id,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->loadDeferredProps(fn ($reload) => $reload
                ->has("events_{$block->id}")
                ->where("events_{$block->id}.events", [])
                ->where("events_{$block->id}.error", 'google_calendar_fetch_failed')
            )
        );
});
