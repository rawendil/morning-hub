<?php

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

test('existing user can login with valid google access token', function () {
    $user = User::factory()->create(['google_id' => 'google-123', 'email' => 'user@example.com']);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('google-123');
    $socialiteUser->shouldReceive('getEmail')->andReturn('user@example.com');
    $socialiteUser->shouldReceive('getAvatar')->andReturn(null);

    Socialite::shouldReceive('driver->stateless->userFromToken')
        ->once()
        ->andReturn($socialiteUser);

    $this->postJson('/api/auth/google', ['access_token' => 'valid-token'])
        ->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
});

test('new user is registered via google', function () {
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('new-google-id');
    $socialiteUser->shouldReceive('getEmail')->andReturn('new@example.com');
    $socialiteUser->shouldReceive('getName')->andReturn('New User');
    $socialiteUser->shouldReceive('getAvatar')->andReturn(null);

    Socialite::shouldReceive('driver->stateless->userFromToken')
        ->once()
        ->andReturn($socialiteUser);

    $this->postJson('/api/auth/google', ['access_token' => 'valid-token'])
        ->assertOk()
        ->assertJsonStructure(['token', 'user']);

    expect(User::where('google_id', 'new-google-id')->exists())->toBeTrue();
});

test('google auth returns 422 without access_token', function () {
    $this->postJson('/api/auth/google', [])->assertUnprocessable();
});
