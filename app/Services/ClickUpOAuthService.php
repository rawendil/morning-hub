<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ClickUpOAuthService
{
    private const AUTH_URL = 'https://app.clickup.com/api';

    private const TOKEN_URL = 'https://api.clickup.com/api/v2/oauth/token';

    public function buildAuthorizationUrl(string $state): string
    {
        return self::AUTH_URL.'?'.http_build_query([
            'client_id' => config('services.clickup.client_id'),
            'redirect_uri' => config('services.clickup.redirect'),
            'state' => $state,
        ]);
    }

    public function exchangeToken(string $code): string
    {
        $response = Http::post(self::TOKEN_URL, [
            'client_id' => config('services.clickup.client_id'),
            'client_secret' => config('services.clickup.client_secret'),
            'code' => $code,
        ]);

        $token = $response->json('access_token');

        if (! $response->successful() || ! $token) {
            throw new \RuntimeException('ClickUp token exchange failed.');
        }

        return $token;
    }
}
