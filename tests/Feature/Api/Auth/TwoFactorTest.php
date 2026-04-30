<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

test('returns token with valid 2fa code', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $tempToken = Str::random(40);
    Cache::put("2fa_challenge:{$tempToken}", $user->id, 300);

    $this->mock(TwoFactorAuthenticationProvider::class, function ($mock) {
        $mock->shouldReceive('verify')->once()->andReturn(true);
    });

    $this->postJson('/api/auth/two-factor', [
        'temp_token' => $tempToken,
        'code' => '123456',
    ])->assertOk()->assertJsonStructure(['token', 'user']);
});

test('returns 422 with invalid 2fa code', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $tempToken = Str::random(40);
    Cache::put("2fa_challenge:{$tempToken}", $user->id, 300);

    $this->mock(TwoFactorAuthenticationProvider::class, function ($mock) {
        $mock->shouldReceive('verify')->once()->andReturn(false);
    });

    $this->postJson('/api/auth/two-factor', [
        'temp_token' => $tempToken,
        'code' => '000000',
    ])->assertUnprocessable()->assertJsonValidationErrors(['code']);
});

test('returns 422 when temp_token expired or not found', function () {
    $this->postJson('/api/auth/two-factor', [
        'temp_token' => 'nonexistent-token',
        'code' => '123456',
    ])->assertUnprocessable();
});

test('temp_token is deleted after successful verification', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $tempToken = Str::random(40);
    Cache::put("2fa_challenge:{$tempToken}", $user->id, 300);

    $this->mock(TwoFactorAuthenticationProvider::class, function ($mock) {
        $mock->shouldReceive('verify')->once()->andReturn(true);
    });

    $this->postJson('/api/auth/two-factor', [
        'temp_token' => $tempToken,
        'code' => '123456',
    ]);

    expect(Cache::has("2fa_challenge:{$tempToken}"))->toBeFalse();
});

test('returns 422 when temp_token is missing', function () {
    $this->postJson('/api/auth/two-factor', [
        'code' => '123456',
    ])->assertUnprocessable()->assertJsonValidationErrors(['temp_token']);
});

test('returns 422 when code is missing', function () {
    $this->postJson('/api/auth/two-factor', [
        'temp_token' => 'some-token',
    ])->assertUnprocessable()->assertJsonValidationErrors(['code']);
});
