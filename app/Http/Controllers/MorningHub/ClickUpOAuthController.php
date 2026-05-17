<?php

namespace App\Http\Controllers\MorningHub;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ClickUpOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ClickUpOAuthController extends Controller
{
    private const FRONTEND_URL = '/morning-hub/clickup';

    private const STATE_TTL_MINUTES = 15;

    public function __construct(
        private readonly ClickUpOAuthService $oauthService,
    ) {}

    public function start(Request $request): JsonResponse
    {
        $name = trim((string) $request->input('name', 'ClickUp')) ?: 'ClickUp';
        $state = Str::random(40);

        /** @var \App\Models\User $user */
        $user = $request->user();

        Cache::put(
            "clickup_oauth_state_{$state}",
            ['user_id' => $user->id, 'name' => $name],
            now()->addMinutes(self::STATE_TTL_MINUTES),
        );

        return response()->json(['url' => $this->oauthService->buildAuthorizationUrl($state)]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $state = (string) $request->query('state', '');

        /** @var array{user_id: int, name: string}|null $cached */
        $cached = Cache::pull("clickup_oauth_state_{$state}");

        if (! $request->has('code')) {
            return redirect(self::FRONTEND_URL.'?error=no_code');
        }

        if (! $cached) {
            return redirect(self::FRONTEND_URL.'?error=invalid_state');
        }

        $user = User::find($cached['user_id']);

        if (! $user) {
            return redirect(self::FRONTEND_URL.'?error=auth_failed');
        }

        try {
            $token = $this->oauthService->exchangeToken((string) $request->query('code'));
        } catch (\RuntimeException) {
            return redirect(self::FRONTEND_URL.'?error=auth_failed');
        }

        $user->clickUpConnections()->create([
            'name' => $cached['name'],
            'api_token' => $token,
        ]);

        return redirect(self::FRONTEND_URL.'?connected=1');
    }
}
