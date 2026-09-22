<?php

use App\Models\User;

test('guest cannot update the timezone', function () {
    $this->putJson('/api/settings/timezone', ['timezone' => 'Europe/Warsaw'])
        ->assertUnauthorized();
});

test('user can store their timezone', function () {
    $user = User::factory()->create(['timezone' => null]);

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/settings/timezone', ['timezone' => 'Europe/Warsaw'])
        ->assertNoContent();

    expect($user->fresh()->timezone)->toBe('Europe/Warsaw');
});

test('an unknown timezone identifier is rejected', function () {
    $user = User::factory()->create(['timezone' => 'Europe/Warsaw']);

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/settings/timezone', ['timezone' => 'Not/AZone'])
        ->assertJsonValidationErrors('timezone');

    expect($user->fresh()->timezone)->toBe('Europe/Warsaw');
});
