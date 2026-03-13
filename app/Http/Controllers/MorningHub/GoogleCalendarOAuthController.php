<?php

namespace App\Http\Controllers\MorningHub;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;

class GoogleCalendarOAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
    {
        if (! $request->user()->hasGoogleLinked()) {
            return redirect()->route('morning-hub.google-calendar.index')
                ->with('error', __('Najpierw połącz konto Google w ustawieniach profilu.'));
        }

        /** @var GoogleProvider $driver */
        $driver = Socialite::driver('google');

        return $driver
            ->scopes([
                'openid',
                'profile',
                'email',
                'https://www.googleapis.com/auth/calendar.readonly',
            ])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
            ])
            ->redirectUrl(route('morning-hub.google-calendar.callback'))
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            /** @var GoogleProvider $driver */
            $driver = Socialite::driver('google');
            /** @var SocialiteUser $socialiteUser */
            $socialiteUser = $driver
                ->redirectUrl(route('morning-hub.google-calendar.callback'))
                ->user();
        } catch (\Throwable) {
            return redirect()->route('morning-hub.google-calendar.index')
                ->with('error', __('Autoryzacja Google nie powiodła się. Spróbuj ponownie.'));
        }

        $user = $request->user();

        if ($socialiteUser->id !== $user->google_id) {
            return redirect()->route('morning-hub.google-calendar.index')
                ->with('error', __('Użyj tego samego konta Google, które jest połączone z Twoim profilem.'));
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

        return redirect()->route('morning-hub.google-calendar.index')
            ->with('success', __('Google Calendar został połączony.'));
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $request->user()->googleCalendarConnection?->delete();

        return redirect()->route('morning-hub.google-calendar.index')
            ->with('success', __('Google Calendar został odłączony.'));
    }
}
