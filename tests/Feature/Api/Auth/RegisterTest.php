<?php

use App\Models\User;

test('user can register with valid data', function () {
    $this->postJson('/api/auth/register', [
        'name' => 'Jan Kowalski',
        'email' => 'jan@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

    expect(User::where('email', 'jan@example.com')->exists())->toBeTrue();
});

test('register returns 422 on duplicate email', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $this->postJson('/api/auth/register', [
        'name' => 'Test',
        'email' => 'existing@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])->assertUnprocessable();
});

test('register returns 422 on missing fields', function () {
    $this->postJson('/api/auth/register', [])->assertUnprocessable();
});
