<?php

use App\Exceptions\GoogleCalendarAuthException;
use App\Models\GoogleCalendarConnection;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\GoogleCalendarServiceFactory;

test('guest cannot access calendar list', function () {
    $this->getJson('/api/morning-hub/google-calendar/calendars')->assertUnauthorized();
});

test('returns empty calendars when no connection exists', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/morning-hub/google-calendar/calendars')
        ->assertOk()
        ->assertJsonPath('calendars', []);
});

test('returns calendars from service when connection exists', function () {
    /** @var User $user */
    $user = User::factory()->create();
    GoogleCalendarConnection::factory()->for($user)->create();

    $mockService = Mockery::mock(GoogleCalendarService::class);
    $mockService->shouldReceive('listCalendars')->once()->andReturn([
        ['id' => 'primary', 'summary' => 'My Calendar'],
        ['id' => 'work@example.com', 'summary' => 'Work'],
    ]);

    $mockFactory = Mockery::mock(GoogleCalendarServiceFactory::class);
    $mockFactory->shouldReceive('make')->once()->andReturn($mockService);

    $this->app->instance(GoogleCalendarServiceFactory::class, $mockFactory);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/morning-hub/google-calendar/calendars')
        ->assertOk();

    expect($response->json('calendars'))->toHaveCount(2);
    expect($response->json('calendars.0.id'))->toBe('primary');
});

test('returns 401 with auth_expired error when token is expired', function () {
    /** @var User $user */
    $user = User::factory()->create();
    GoogleCalendarConnection::factory()->for($user)->create();

    $mockService = Mockery::mock(GoogleCalendarService::class);
    $mockService->shouldReceive('listCalendars')->once()->andThrow(new GoogleCalendarAuthException);

    $mockFactory = Mockery::mock(GoogleCalendarServiceFactory::class);
    $mockFactory->shouldReceive('make')->once()->andReturn($mockService);

    $this->app->instance(GoogleCalendarServiceFactory::class, $mockFactory);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/morning-hub/google-calendar/calendars')
        ->assertUnauthorized()
        ->assertJsonPath('error', 'auth_expired');
});
