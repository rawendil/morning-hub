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
