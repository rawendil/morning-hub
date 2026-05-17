<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('start requires authentication', function () {
    $this->postJson('/api/morning-hub/clickup/oauth/start')
        ->assertUnauthorized();
});

test('start stores state in cache and returns clickup url', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/clickup/oauth/start', ['name' => 'Praca']);

    $response->assertOk()->assertJsonStructure(['url']);

    $url = $response->json('url');
    expect($url)->toContain('app.clickup.com/api');

    parse_str((string) parse_url($url, PHP_URL_QUERY), $params);
    $state = $params['state'] ?? null;
    expect($state)->not->toBeNull();

    expect(Cache::get("clickup_oauth_state_{$state}"))->toMatchArray([
        'user_id' => $user->id,
        'name' => 'Praca',
    ]);
});

test('start uses default name when name is not provided', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/morning-hub/clickup/oauth/start');

    $response->assertOk();

    parse_str((string) parse_url($response->json('url'), PHP_URL_QUERY), $params);
    $state = $params['state'] ?? null;

    expect(Cache::get("clickup_oauth_state_{$state}")['name'])->toBe('ClickUp');
});

test('callback creates connection and redirects with connected=1', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/oauth/token' => Http::response([
            'access_token' => 'oauth_token_abc',
        ], 200),
    ]);

    $user = User::factory()->create();
    $state = 'test_state_xyz';

    Cache::put("clickup_oauth_state_{$state}", [
        'user_id' => $user->id,
        'name' => 'Praca',
    ], now()->addMinutes(15));

    $this->get("/clickup/oauth/callback?code=auth_code&state={$state}")
        ->assertRedirect('/morning-hub/clickup?connected=1');

    $this->assertDatabaseHas('clickup_connections', [
        'user_id' => $user->id,
        'name' => 'Praca',
    ]);
});

test('callback consumes cache state after use', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/oauth/token' => Http::response([
            'access_token' => 'oauth_token_abc',
        ], 200),
    ]);

    $user = User::factory()->create();
    $state = 'test_state_xyz';

    Cache::put("clickup_oauth_state_{$state}", [
        'user_id' => $user->id,
        'name' => 'Praca',
    ], now()->addMinutes(15));

    $this->get("/clickup/oauth/callback?code=auth_code&state={$state}");

    expect(Cache::get("clickup_oauth_state_{$state}"))->toBeNull();
});

test('callback redirects with no_code error when code is missing', function () {
    $user = User::factory()->create();
    $state = 'test_state_xyz';

    Cache::put("clickup_oauth_state_{$state}", [
        'user_id' => $user->id,
        'name' => 'Praca',
    ], now()->addMinutes(15));

    $this->get("/clickup/oauth/callback?state={$state}")
        ->assertRedirect('/morning-hub/clickup?error=no_code');
});

test('callback redirects with invalid_state when state is not in cache', function () {
    $this->get('/clickup/oauth/callback?code=abc&state=unknown_state')
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

    Cache::put("clickup_oauth_state_{$state}", [
        'user_id' => $user->id,
        'name' => 'Praca',
    ], now()->addMinutes(15));

    $this->get("/clickup/oauth/callback?code=bad_code&state={$state}")
        ->assertRedirect('/morning-hub/clickup?error=auth_failed');
});
