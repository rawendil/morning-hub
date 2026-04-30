<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('forgot password sends reset email', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
        ->assertNoContent();

    Notification::assertSentTo($user, ResetPassword::class);
});

test('forgot password returns 204 even for unknown email', function () {
    $this->postJson('/api/auth/forgot-password', ['email' => 'unknown@example.com'])
        ->assertNoContent();
});

test('reset password with valid token updates password', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertNoContent();
});

test('reset password with invalid token returns 422', function () {
    $user = User::factory()->create();

    $this->postJson('/api/auth/reset-password', [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertUnprocessable();
});
