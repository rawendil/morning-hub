<?php

use App\Models\User;

test('guest cannot access appearance settings', function () {
    $this->getJson('/api/settings/appearance')->assertUnauthorized();
});

test('user can fetch appearance setting', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/settings/appearance')
        ->assertOk()
        ->assertJsonStructure(['appearance']);
});

test('user can update appearance setting', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/settings/appearance', ['appearance' => 'dark'])
        ->assertNoContent();
});

test('update appearance returns 422 for invalid value', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/settings/appearance', ['appearance' => 'invalid'])
        ->assertUnprocessable();
});
