<?php

use App\Models\User;

test('authenticated user can fetch their data', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'locale', 'appearance'])
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', $user->email);
});

test('unauthenticated request returns 401', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});
