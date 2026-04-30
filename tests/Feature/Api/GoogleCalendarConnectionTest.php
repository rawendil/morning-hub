<?php

use App\Models\GoogleCalendarConnection;
use App\Models\User;

test('guest cannot access google calendar settings', function () {
    $this->getJson('/api/morning-hub/google-calendar')->assertUnauthorized();
});

test('user can fetch their google calendar connection', function () {
    $user = User::factory()->create();
    GoogleCalendarConnection::factory()->for($user)->create(['name' => 'work@example.com']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/morning-hub/google-calendar')
        ->assertOk()
        ->assertJsonPath('connection.name', 'work@example.com');
});

test('user can update calendar ids', function () {
    $user = User::factory()->create();
    GoogleCalendarConnection::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/morning-hub/google-calendar', ['calendar_ids' => ['cal-1', 'cal-2']])
        ->assertOk();
});
