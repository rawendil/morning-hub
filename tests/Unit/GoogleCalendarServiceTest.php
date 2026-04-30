<?php

use App\Models\GoogleCalendarConnection;
use App\Services\GoogleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->connection = GoogleCalendarConnection::factory()->create([
        'token_expires_at' => now()->addHour(),
    ]);
    $this->service = new GoogleCalendarService($this->connection);
});

test('testConnection returns true on success', function () {
    Http::fake([
        'www.googleapis.com/calendar/v3/users/me/calendarList*' => Http::response([
            'items' => [],
        ]),
    ]);

    expect($this->service->testConnection())->toBeTrue();
});

test('testConnection returns false on failure', function () {
    Http::fake([
        'www.googleapis.com/calendar/v3/users/me/calendarList*' => Http::response([], 401),
    ]);

    expect($this->service->testConnection())->toBeFalse();
});

test('listCalendars returns formatted calendar list', function () {
    Http::fake([
        'www.googleapis.com/calendar/v3/users/me/calendarList*' => Http::response([
            'items' => [
                [
                    'id' => 'primary',
                    'summary' => 'My Calendar',
                    'backgroundColor' => '#4285f4',
                    'selected' => true,
                ],
                [
                    'id' => 'work@example.com',
                    'summary' => 'Work',
                    'backgroundColor' => '#0b8043',
                    'selected' => true,
                ],
            ],
        ]),
    ]);

    $calendars = $this->service->listCalendars();

    expect($calendars)->toHaveCount(2);
    expect($calendars[0])->toMatchArray([
        'id' => 'primary',
        'name' => 'My Calendar',
        'color' => '#4285f4',
    ]);
});

test('listEventsForDate returns mapped events sorted by time', function () {
    Http::fake([
        'www.googleapis.com/calendar/v3/calendars/*' => Http::response([
            'items' => [
                [
                    'id' => 'event-2',
                    'summary' => 'Afternoon meeting',
                    'start' => ['dateTime' => '2026-03-13T14:00:00+01:00'],
                    'end' => ['dateTime' => '2026-03-13T15:00:00+01:00'],
                    'location' => 'Room A',
                ],
                [
                    'id' => 'event-1',
                    'summary' => 'Morning standup',
                    'start' => ['dateTime' => '2026-03-13T09:00:00+01:00'],
                    'end' => ['dateTime' => '2026-03-13T09:30:00+01:00'],
                ],
            ],
            'summary' => 'My Calendar',
            'backgroundColor' => '#4285f4',
        ]),
    ]);

    $events = $this->service->listEventsForDate('2026-03-13', ['primary'], 'Europe/Warsaw');

    expect($events)->toHaveCount(2);
    expect($events[0]['title'])->toBe('Morning standup');
    expect($events[1]['title'])->toBe('Afternoon meeting');
    expect($events[1]['location'])->toBe('Room A');
    expect($events[0]['all_day'])->toBeFalse();
});

test('listEventsForDate handles all-day events', function () {
    Http::fake([
        'www.googleapis.com/calendar/v3/calendars/*' => Http::response([
            'items' => [
                [
                    'id' => 'event-allday',
                    'summary' => 'Holiday',
                    'start' => ['date' => '2026-03-13'],
                    'end' => ['date' => '2026-03-14'],
                ],
            ],
            'summary' => 'My Calendar',
            'backgroundColor' => '#4285f4',
        ]),
    ]);

    $events = $this->service->listEventsForDate('2026-03-13', ['primary'], 'Europe/Warsaw');

    expect($events[0]['all_day'])->toBeTrue();
    expect($events[0]['title'])->toBe('Holiday');
});

test('all-day events sort before timed events', function () {
    Http::fake([
        'www.googleapis.com/calendar/v3/calendars/*' => Http::response([
            'items' => [
                [
                    'id' => 'event-timed',
                    'summary' => 'Meeting',
                    'start' => ['dateTime' => '2026-03-13T09:00:00+01:00'],
                    'end' => ['dateTime' => '2026-03-13T10:00:00+01:00'],
                ],
                [
                    'id' => 'event-allday',
                    'summary' => 'Holiday',
                    'start' => ['date' => '2026-03-13'],
                    'end' => ['date' => '2026-03-14'],
                ],
            ],
            'summary' => 'Cal',
            'backgroundColor' => '#ccc',
        ]),
    ]);

    $events = $this->service->listEventsForDate('2026-03-13', ['primary'], 'Europe/Warsaw');

    expect($events[0]['title'])->toBe('Holiday');
    expect($events[1]['title'])->toBe('Meeting');
});

test('refreshTokenIfNeeded refreshes expired token', function () {
    $this->connection->update(['token_expires_at' => now()->subMinute()]);

    $mockDriver = Mockery::mock();
    $mockDriver->shouldReceive('refreshToken')
        ->once()
        ->with($this->connection->refresh_token)
        ->andReturn((object) [
            'token' => 'new-access-token',
            'refreshToken' => 'new-refresh-token',
            'expiresIn' => 3600,
        ]);
    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($mockDriver);

    $service = new GoogleCalendarService($this->connection->fresh());

    Http::fake([
        'www.googleapis.com/calendar/v3/users/me/calendarList*' => Http::response(['items' => []]),
    ]);

    $service->testConnection();

    $this->connection->refresh();
    expect($this->connection->token_expires_at->isFuture())->toBeTrue();
});

test('refreshTokenIfNeeded handles refresh failure gracefully', function () {
    $this->connection->update(['token_expires_at' => now()->subMinute()]);

    $mockDriver = Mockery::mock();
    $mockDriver->shouldReceive('refreshToken')
        ->once()
        ->andThrow(new \Exception('Token revoked'));
    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($mockDriver);

    $service = new GoogleCalendarService($this->connection->fresh());

    expect(fn () => $service->testConnection())->toThrow(\App\Exceptions\GoogleCalendarAuthException::class);
});

test('getEventsForDashboard returns events and null error on success', function () {
    $this->connection->update(['calendar_ids' => ['primary']]);

    Http::fake([
        'www.googleapis.com/calendar/v3/calendars/primary/events*' => Http::response([
            'items' => [
                ['id' => 'e1', 'summary' => 'Standup', 'start' => ['dateTime' => now()->toIso8601String()], 'end' => ['dateTime' => now()->addHour()->toIso8601String()]],
            ],
            'summary' => 'Primary',
            'backgroundColor' => '#4285f4',
        ]),
    ]);

    $result = $this->service->getEventsForDashboard();

    expect($result)->toHaveKeys(['events', 'error']);
    expect($result['error'])->toBeNull();
    expect($result['events'])->toHaveCount(1);
});

test('getEventsForDashboard returns auth error on GoogleCalendarAuthException', function () {
    $this->connection->update(['token_expires_at' => now()->subMinute()]);

    $mockDriver = Mockery::mock();
    $mockDriver->shouldReceive('refreshToken')
        ->once()
        ->andThrow(new \Exception('Token revoked'));
    Socialite::shouldReceive('driver')->with('google')->andReturn($mockDriver);

    $service = new GoogleCalendarService($this->connection->fresh());
    $result = $service->getEventsForDashboard();

    expect($result['events'])->toBe([]);
    expect($result['error'])->toBe('google_calendar_auth_expired');
});

test('listEventsForDate fetches from multiple calendars', function () {
    Http::fake([
        'www.googleapis.com/calendar/v3/calendars/primary/events*' => Http::response([
            'items' => [
                ['id' => 'e1', 'summary' => 'Event 1', 'start' => ['dateTime' => '2026-03-13T09:00:00+01:00'], 'end' => ['dateTime' => '2026-03-13T10:00:00+01:00']],
            ],
            'summary' => 'Primary',
            'backgroundColor' => '#4285f4',
        ]),
        'www.googleapis.com/calendar/v3/calendars/work%40example.com/events*' => Http::response([
            'items' => [
                ['id' => 'e2', 'summary' => 'Event 2', 'start' => ['dateTime' => '2026-03-13T11:00:00+01:00'], 'end' => ['dateTime' => '2026-03-13T12:00:00+01:00']],
            ],
            'summary' => 'Work',
            'backgroundColor' => '#0b8043',
        ]),
    ]);

    $events = $this->service->listEventsForDate('2026-03-13', ['primary', 'work@example.com'], 'Europe/Warsaw');

    expect($events)->toHaveCount(2);
    expect($events[0]['calendar_name'])->toBe('Primary');
    expect($events[1]['calendar_name'])->toBe('Work');
});
