<?php

use App\Services\ClickUpOAuthService;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

test('buildAuthorizationUrl contains client_id, redirect_uri and state', function () {
    config([
        'services.clickup.client_id' => 'test_client',
        'services.clickup.redirect' => 'http://localhost/clickup/oauth/callback',
    ]);

    $service = new ClickUpOAuthService;
    $url = $service->buildAuthorizationUrl('state_abc123');

    expect($url)
        ->toContain('app.clickup.com/api')
        ->toContain('client_id=test_client')
        ->toContain('state=state_abc123')
        ->toContain('redirect_uri=http%3A%2F%2Flocalhost%2Fclickup%2Foauth%2Fcallback');
});

test('exchangeToken returns access token string on success', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/oauth/token' => Http::response([
            'access_token' => 'oauth_abc123',
        ], 200),
    ]);

    config([
        'services.clickup.client_id' => 'test_client',
        'services.clickup.client_secret' => 'test_secret',
    ]);

    $service = new ClickUpOAuthService;
    $token = $service->exchangeToken('auth_code_xyz');

    expect($token)->toBe('oauth_abc123');

    Http::assertSent(fn ($req) => $req->url() === 'https://api.clickup.com/api/v2/oauth/token'
        && $req['code'] === 'auth_code_xyz'
    );
});

test('exchangeToken throws RuntimeException on failed response', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/oauth/token' => Http::response([
            'error' => 'invalid_code',
        ], 400),
    ]);

    $service = new ClickUpOAuthService;

    expect(fn () => $service->exchangeToken('bad_code'))
        ->toThrow(\RuntimeException::class);
});

test('exchangeToken throws RuntimeException when access_token missing from response', function () {
    Http::fake([
        'https://api.clickup.com/api/v2/oauth/token' => Http::response([], 200),
    ]);

    $service = new ClickUpOAuthService;

    expect(fn () => $service->exchangeToken('code_no_token'))
        ->toThrow(\RuntimeException::class);
});
