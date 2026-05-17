<?php

namespace App\Http\Controllers\MorningHub;

use App\Http\Controllers\Controller;
use App\Services\ClickUpOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClickUpOAuthController extends Controller
{
    private const FRONTEND_URL = '/morning-hub/clickup';

    public function __construct(
        private readonly ClickUpOAuthService $oauthService,
    ) {}

    public function redirect(Request $request): RedirectResponse
    {
        $name = $request->query('name', 'ClickUp');
        $state = Str::random(40);

        $request->session()->put('clickup_oauth_state', $state);
        $request->session()->put('clickup_oauth_name', $name);

        return redirect($this->oauthService->buildAuthorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $request->has('code')) {
            $request->session()->forget(['clickup_oauth_state', 'clickup_oauth_name']);

            return redirect(self::FRONTEND_URL.'?error=no_code');
        }

        if ($request->query('state') !== $request->session()->pull('clickup_oauth_state')) {
            $request->session()->forget('clickup_oauth_name');

            return redirect(self::FRONTEND_URL.'?error=invalid_state');
        }

        $name = $request->session()->pull('clickup_oauth_name', 'ClickUp');

        try {
            $token = $this->oauthService->exchangeToken($request->query('code'));
        } catch (\RuntimeException) {
            return redirect(self::FRONTEND_URL.'?error=auth_failed');
        }

        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->clickUpConnections()->create([
            'name' => $name,
            'api_token' => $token,
        ]);

        return redirect(self::FRONTEND_URL.'?connected=1');
    }
}
