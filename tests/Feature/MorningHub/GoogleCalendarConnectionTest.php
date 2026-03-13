<?php

use App\Models\GoogleCalendarConnection;
use App\Models\RoutineBlock;
use App\Models\User;

test('google calendar connection belongs to user', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);
    $connection = GoogleCalendarConnection::factory()->create(['user_id' => $user->id]);

    expect($connection->user->id)->toBe($user->id);
});

test('user has one google calendar connection', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);
    $connection = GoogleCalendarConnection::factory()->create(['user_id' => $user->id]);

    expect($user->googleCalendarConnection->id)->toBe($connection->id);
});

test('routine block belongs to google calendar connection', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);
    $connection = GoogleCalendarConnection::factory()->create(['user_id' => $user->id]);
    $block = RoutineBlock::factory()->create([
        'user_id' => $user->id,
        'type' => 'google_calendar',
        'google_calendar_connection_id' => $connection->id,
    ]);

    expect($block->googleCalendarConnection->id)->toBe($connection->id);
});

test('deleting connection nullifies routine block FK', function () {
    $user = User::factory()->create(['google_id' => 'google-123']);
    $connection = GoogleCalendarConnection::factory()->create(['user_id' => $user->id]);
    $block = RoutineBlock::factory()->create([
        'user_id' => $user->id,
        'type' => 'google_calendar',
        'google_calendar_connection_id' => $connection->id,
    ]);

    $connection->delete();

    expect($block->fresh()->google_calendar_connection_id)->toBeNull();
});

test('tokens are hidden from serialization', function () {
    $connection = GoogleCalendarConnection::factory()->create();
    $array = $connection->toArray();

    expect($array)->not->toHaveKey('access_token');
    expect($array)->not->toHaveKey('refresh_token');
});

test('isTokenExpired returns true when token is expired', function () {
    $connection = GoogleCalendarConnection::factory()->create([
        'token_expires_at' => now()->subMinute(),
    ]);

    expect($connection->isTokenExpired())->toBeTrue();
});

test('isTokenExpired returns false when token is valid', function () {
    $connection = GoogleCalendarConnection::factory()->create([
        'token_expires_at' => now()->addHour(),
    ]);

    expect($connection->isTokenExpired())->toBeFalse();
});

test('getCredentialAttributes returns access_token and refresh_token', function () {
    $connection = new GoogleCalendarConnection;

    expect($connection->getCredentialAttributes())->toBe(['access_token', 'refresh_token']);
});
