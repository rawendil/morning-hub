<?php

use App\Models\User;

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('web');

    $this->withToken($token->plainTextToken)
        ->postJson('/api/auth/logout')
        ->assertNoContent();
});

test('token is deleted after logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('web');

    $this->withToken($token->plainTextToken)
        ->postJson('/api/auth/logout');

    expect($user->tokens()->count())->toBe(0);
});

test('unauthenticated user cannot logout', function () {
    $this->postJson('/api/auth/logout')->assertUnauthorized();
});
