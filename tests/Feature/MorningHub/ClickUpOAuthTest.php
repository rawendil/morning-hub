<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

test('redirect requires authentication', function () {
    $this->get('/clickup/oauth/redirect')
        ->assertRedirect('/login');
});

test('redirect stores state and name in session and redirects to clickup', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get('/clickup/oauth/redirect?name=Praca');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('app.clickup.com/api');
    expect(session('clickup_oauth_state'))->not->toBeNull();
    expect(session('clickup_oauth_name'))->toBe('Praca');
});

test('redirect uses default name when name param is missing', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/clickup/oauth/redirect');

    expect(session('clickup_oauth_name'))->toBe('ClickUp');
});

test('callback creates connection and redirects with connected=1', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/oauth/token' => Http::response([
            'access_token' => 'oauth_token_abc',
        ], 200),
    ]);

    $user = User::factory()->create();
    $state = 'test_state_xyz';

    $response = $this->actingAs($user)
        ->withSession([
            'clickup_oauth_state' => $state,
            'clickup_oauth_name' => 'Praca',
        ])
        ->get('/clickup/oauth/callback?code=auth_code&state='.$state);

    $response->assertRedirect('/morning-hub/clickup?connected=1');

    $this->assertDatabaseHas('clickup_connections', [
        'user_id' => $user->id,
        'name' => 'Praca',
    ]);
});

test('callback clears oauth session keys after success', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/oauth/token' => Http::response([
            'access_token' => 'oauth_token_abc',
        ], 200),
    ]);

    $user = User::factory()->create();
    $state = 'test_state_xyz';

    $this->actingAs($user)
        ->withSession([
            'clickup_oauth_state' => $state,
            'clickup_oauth_name' => 'Praca',
        ])
        ->get('/clickup/oauth/callback?code=auth_code&state='.$state);

    expect(session('clickup_oauth_state'))->toBeNull();
    expect(session('clickup_oauth_name'))->toBeNull();
});

test('callback redirects with no_code error when code is missing', function () {
    $user = User::factory()->create();
    $state = 'test_state_xyz';

    $this->actingAs($user)
        ->withSession(['clickup_oauth_state' => $state])
        ->get('/clickup/oauth/callback?state='.$state)
        ->assertRedirect('/morning-hub/clickup?error=no_code');

    expect(session('clickup_oauth_state'))->toBeNull();
    expect(session('clickup_oauth_name'))->toBeNull();
});

test('callback redirects with invalid_state error on state mismatch', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['clickup_oauth_state' => 'correct_state'])
        ->get('/clickup/oauth/callback?code=abc&state=wrong_state')
        ->assertRedirect('/morning-hub/clickup?error=invalid_state');
});

test('callback redirects with auth_failed when token exchange fails', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/oauth/token' => Http::response([
            'error' => 'invalid_code',
        ], 400),
    ]);

    $user = User::factory()->create();
    $state = 'test_state_xyz';

    $this->actingAs($user)
        ->withSession([
            'clickup_oauth_state' => $state,
            'clickup_oauth_name' => 'Praca',
        ])
        ->get('/clickup/oauth/callback?code=bad_code&state='.$state)
        ->assertRedirect('/morning-hub/clickup?error=auth_failed');

    expect(session('clickup_oauth_name'))->toBeNull();
});
