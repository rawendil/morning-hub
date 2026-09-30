<?php

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;

test('guests cannot access google link routes', function () {
    $this->get(route('google.link'))
        ->assertRedirect(route('login'));

    $this->delete(route('google.unlink'))
        ->assertRedirect(route('login'));
});

test('new user registered through the google callback has terms acceptance recorded', function () {
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('web-google-id');
    $socialiteUser->shouldReceive('getEmail')->andReturn('web@example.com');
    $socialiteUser->shouldReceive('getName')->andReturn('Web User');
    $socialiteUser->shouldReceive('getAvatar')->andReturn(null);

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->once()->andReturn($socialiteUser);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get(route('google.callback'))->assertRedirectContains('/login?google_token=');

    expect(User::where('google_id', 'web-google-id')->first()->terms_accepted_at)->not->toBeNull();
});
