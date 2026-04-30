<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('user can login with valid credentials', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
});

test('login returns 422 on wrong password', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrong',
    ])->assertUnprocessable();
});

test('login returns 422 on missing fields', function () {
    $this->postJson('/api/auth/login', [])->assertUnprocessable();
});

test('login returns temp_token when user has 2fa enabled', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);
    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()->assertJson(['requires_2fa' => true])->assertJsonStructure(['temp_token']);
});

test('login does not return token directly when user has 2fa enabled', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);
    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertJsonMissing(['token']);
});
