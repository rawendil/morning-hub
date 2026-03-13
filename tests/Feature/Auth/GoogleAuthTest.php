<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;

function mockSocialiteUser(array $overrides = []): void
{
    $socialiteUser = new SocialiteUser;
    $socialiteUser->map([
        'id' => $overrides['id'] ?? 'google-123',
        'name' => $overrides['name'] ?? 'Test User',
        'email' => $overrides['email'] ?? 'test@example.com',
        'avatar' => $overrides['avatar'] ?? 'https://example.com/avatar.jpg',
    ]);

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);
    $provider->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
    $provider->shouldReceive('redirectUrl')->andReturnSelf();

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

test('google redirect sends user to google', function () {
    mockSocialiteUser();

    $response = $this->get(route('google.redirect'));

    $response->assertRedirect();
});

test('google callback creates new user when no match found', function () {
    mockSocialiteUser();

    $response = $this->get(route('google.callback'));

    $response->assertRedirect(config('fortify.home'));
    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->google_id)->toBe('google-123')
        ->and($user->name)->toBe('Test User')
        ->and($user->password)->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull();
});

test('google callback logs in existing user by google_id', function () {
    $user = User::factory()->withGoogle()->create([
        'google_id' => 'google-123',
    ]);

    mockSocialiteUser(['id' => 'google-123', 'email' => 'other@example.com']);

    $response = $this->get(route('google.callback'));

    $response->assertRedirect(config('fortify.home'));
    $this->assertAuthenticatedAs($user);
});

test('google callback merges accounts by email', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    expect($user->google_id)->toBeNull();

    mockSocialiteUser(['email' => 'test@example.com']);

    $response = $this->get(route('google.callback'));

    $response->assertRedirect(config('fortify.home'));
    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->google_id)->toBe('google-123')
        ->and($user->google_avatar)->not->toBeNull();
});

test('google callback redirects to 2fa challenge when user has 2fa enabled', function () {
    $user = User::factory()->withTwoFactor()->withGoogle()->create([
        'google_id' => 'google-123',
    ]);

    mockSocialiteUser(['id' => 'google-123', 'email' => $user->email]);

    $response = $this->get(route('google.callback'));

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('google callback handles invalid state gracefully', function () {
    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andThrow(
        new \Laravel\Socialite\Two\InvalidStateException
    );

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get(route('google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');
});

test('authenticated users cannot access google guest routes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('google.redirect'))
        ->assertRedirect(config('fortify.home'));
});

test('guests cannot access google auth routes', function () {
    $this->get(route('google.link'))
        ->assertRedirect(route('login'));

    $this->delete(route('google.unlink'))
        ->assertRedirect(route('login'));
});
