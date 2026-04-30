<?php

use App\Models\GoogleCalendarConnection;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\GoogleCalendarServiceFactory;

test('guest cannot access google calendar settings', function () {
    $this->getJson('/api/morning-hub/google-calendar')->assertUnauthorized();
});

test('user can fetch their google calendar connection', function () {
    /** @var User $user */
    $user = User::factory()->create();
    GoogleCalendarConnection::factory()->for($user)->create(['name' => 'work@example.com']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/morning-hub/google-calendar')
        ->assertOk()
        ->assertJsonPath('connection.name', 'work@example.com');
});

test('user can update calendar ids', function () {
    /** @var User $user */
    $user = User::factory()->create();
    GoogleCalendarConnection::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/morning-hub/google-calendar', ['calendar_ids' => ['cal-1', 'cal-2']])
        ->assertOk();

    expect($user->googleCalendarConnection()->value('calendar_ids'))->toBe(['cal-1', 'cal-2']);
});

test('update is forbidden when no connection exists', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/morning-hub/google-calendar', ['calendar_ids' => ['cal-1']])
        ->assertForbidden();
});

test('user can disconnect google calendar', function () {
    /** @var User $user */
    $user = User::factory()->create();
    GoogleCalendarConnection::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/morning-hub/google-calendar')
        ->assertOk()
        ->assertJsonPath('message', 'Disconnected.');

    expect($user->googleCalendarConnection()->exists())->toBeFalse();
});

test('disconnect is idempotent when no connection exists', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/morning-hub/google-calendar')
        ->assertOk();
});

test('test endpoint returns success when connection is valid', function () {
    /** @var User $user */
    $user = User::factory()->create();
    GoogleCalendarConnection::factory()->for($user)->create();

    $mockService = Mockery::mock(GoogleCalendarService::class);
    $mockService->shouldReceive('listCalendars')->once()->andReturn([]);

    $mockFactory = Mockery::mock(GoogleCalendarServiceFactory::class);
    $mockFactory->shouldReceive('make')->once()->andReturn($mockService);

    $this->app->instance(GoogleCalendarServiceFactory::class, $mockFactory);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/google-calendar/test')
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('test endpoint returns failure when service throws', function () {
    /** @var User $user */
    $user = User::factory()->create();
    GoogleCalendarConnection::factory()->for($user)->create();

    $mockService = Mockery::mock(GoogleCalendarService::class);
    $mockService->shouldReceive('listCalendars')->once()->andThrow(new \RuntimeException('error'));

    $mockFactory = Mockery::mock(GoogleCalendarServiceFactory::class);
    $mockFactory->shouldReceive('make')->once()->andReturn($mockService);

    $this->app->instance(GoogleCalendarServiceFactory::class, $mockFactory);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/google-calendar/test')
        ->assertOk()
        ->assertJsonPath('success', false);
});

test('test endpoint returns not connected when no connection exists', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/google-calendar/test')
        ->assertOk()
        ->assertJsonPath('success', false);
});
