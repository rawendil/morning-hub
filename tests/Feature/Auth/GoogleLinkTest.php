<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;

function mockSocialiteLinkUser(array $overrides = []): void
{
    $socialiteUser = new SocialiteUser;
    $socialiteUser->map([
        'id' => $overrides['id'] ?? 'google-link-123',
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

test('link redirect sends authenticated user to google', function () {
    $user = User::factory()->create();

    mockSocialiteLinkUser();

    $response = $this->actingAs($user)->get(route('google.link'));

    $response->assertRedirect();
});

test('link callback sets google_id on user', function () {
    $user = User::factory()->create();

    mockSocialiteLinkUser();

    $response = $this->actingAs($user)->get(route('google.link.callback'));

    $response->assertRedirect(route('profile.edit'));
    $response->assertSessionHas('status');

    $user->refresh();
    expect($user->google_id)->toBe('google-link-123')
        ->and($user->google_avatar)->toBe('https://example.com/avatar.jpg');
});

test('link callback rejects already used google_id', function () {
    User::factory()->withGoogle()->create([
        'google_id' => 'google-link-123',
    ]);

    $user = User::factory()->create();

    mockSocialiteLinkUser(['id' => 'google-link-123']);

    $response = $this->actingAs($user)->get(route('google.link.callback'));

    $response->assertRedirect(route('profile.edit'));
    $response->assertSessionHas('error');

    expect($user->refresh()->google_id)->toBeNull();
});

test('unlink removes google_id when user has password', function () {
    $user = User::factory()->withGoogle()->create();

    $response = $this->actingAs($user)->delete(route('google.unlink'));

    $response->assertRedirect(route('profile.edit'));
    $response->assertSessionHas('status');

    $user->refresh();
    expect($user->google_id)->toBeNull()
        ->and($user->google_avatar)->toBeNull();
});

test('unlink is blocked when user has no password', function () {
    $user = User::factory()->withoutPassword()->withGoogle()->create();

    $response = $this->actingAs($user)->delete(route('google.unlink'));

    $response->assertRedirect(route('profile.edit'));
    $response->assertSessionHas('error');

    expect($user->refresh()->google_id)->not->toBeNull();
});

test('unlink is blocked when google is not linked', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete(route('google.unlink'));

    $response->assertRedirect(route('profile.edit'));
    $response->assertSessionHas('error');
});
