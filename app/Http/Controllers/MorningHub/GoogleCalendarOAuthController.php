<?php

namespace App\Http\Controllers\MorningHub;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

class GoogleCalendarOAuthController extends Controller
{
    private const STATE_TTL_MINUTES = 15;

    public function connect(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! $user->hasGoogleLinked()) {
            return response()->json(['error' => __('Najpierw połącz konto Google w ustawieniach profilu.')], 422);
        }

        $state = Str::random(40);

        Cache::put(
            "google_calendar_oauth_state_{$state}",
            $user->id,
            now()->addMinutes(self::STATE_TTL_MINUTES),
        );

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('morning-hub.google-calendar.callback'),
            'response_type' => 'code',
            'scope' => 'openid profile email https://www.googleapis.com/auth/calendar.readonly',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return response()->json(['url' => $url]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $state = (string) $request->query('state', '');
        $userId = Cache::pull("google_calendar_oauth_state_{$state}");

        if (! $request->has('code')) {
            return redirect()->route('spa')->with('error', 'no_code');
        }

        if (! $userId) {
            return redirect()->route('spa')->with('error', 'invalid_state');
        }

        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('spa')->with('error', 'auth_failed');
        }

        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider $provider */
            $provider = Socialite::driver('google');
            /** @var SocialiteUser $socialiteUser */
            $socialiteUser = $provider
                ->stateless()
                ->redirectUrl(route('morning-hub.google-calendar.callback'))
                ->user();
        } catch (\Throwable) {
            return redirect('/morning-hub/google-calendar?error=auth_failed');
        }

        if ($socialiteUser->id !== $user->google_id) {
            return redirect('/morning-hub/google-calendar?error=wrong_account');
        }

        $user->googleCalendarConnection()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'google_id' => $socialiteUser->id,
                'name' => $socialiteUser->email,
                'access_token' => $socialiteUser->token,
                'refresh_token' => $socialiteUser->refreshToken,
                'token_expires_at' => now()->addSeconds($socialiteUser->expiresIn),
            ],
        );

        return redirect('/morning-hub/google-calendar?connected=1');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $request->user()->googleCalendarConnection?->delete();

        return redirect()->route('morning-hub.google-calendar.index')
            ->with('success', __('Google Calendar został odłączony.'));
    }
}
