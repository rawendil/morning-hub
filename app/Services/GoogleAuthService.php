<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GoogleAuthService
{
    /**
     * Get redirect response to Google consent screen for login/register.
     */
    public function getRedirectUrl(): RedirectResponse
    {
        return $this->driver()->redirect();
    }

    /**
     * Get redirect response to Google for account linking.
     */
    public function getLinkRedirectUrl(): RedirectResponse
    {
        return $this->driver()
            ->redirectUrl(route('google.link.callback'))
            ->redirect();
    }

    /**
     * Handle the OAuth callback for guest login/register.
     *
     * @return array{user: User, is_new: bool, needs_2fa: bool}
     */
    public function handleCallback(): array
    {
        $googleUser = $this->driver()->user();

        return DB::transaction(function () use ($googleUser) {
            // 1. Find by google_id (returning Google user)
            $user = User::where('google_id', $googleUser->getId())->first();
            if ($user) {
                $this->updateAvatar($user, $googleUser);

                return [
                    'user' => $user,
                    'is_new' => false,
                    'needs_2fa' => $user->two_factor_confirmed_at !== null,
                ];
            }

            // 2. Find by email (merge accounts)
            $user = User::where('email', $googleUser->getEmail())->first();
            if ($user) {
                $user->forceFill([
                    'google_id' => $googleUser->getId(),
                    'google_avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();

                return [
                    'user' => $user,
                    'is_new' => false,
                    'needs_2fa' => $user->two_factor_confirmed_at !== null,
                ];
            }

            // 3. Create new user (no password, email verified)
            $user = new User;
            $user->forceFill([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'google_avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ]);
            $user->save();

            return [
                'user' => $user,
                'is_new' => true,
                'needs_2fa' => false,
            ];
        });
    }

    /**
     * Link Google account to an authenticated user.
     *
     * @throws \InvalidArgumentException
     */
    public function linkAccount(User $user): void
    {
        $googleUser = $this->driver()
            ->redirectUrl(route('google.link.callback'))
            ->user();

        $existingUser = User::where('google_id', $googleUser->getId())
            ->where('id', '!=', $user->id)
            ->exists();

        if ($existingUser) {
            throw new \InvalidArgumentException(__('To konto Google jest już powiązane z innym użytkownikiem.'));
        }

        $user->update([
            'google_id' => $googleUser->getId(),
            'google_avatar' => $googleUser->getAvatar(),
        ]);
    }

    /**
     * Unlink Google account from user.
     *
     * @throws \InvalidArgumentException
     */
    public function unlinkAccount(User $user): void
    {
        if (! $user->hasGoogleLinked()) {
            throw new \InvalidArgumentException(__('Konto Google nie jest powiązane.'));
        }

        if (! $user->hasPassword()) {
            throw new \InvalidArgumentException(__('Nie można odłączyć Google bez ustawionego hasła.'));
        }

        $user->googleCalendarConnection?->delete();

        $user->update([
            'google_id' => null,
            'google_avatar' => null,
        ]);
    }

    /**
     * Handle API login via Google access token (stateless, frontend-initiated OAuth).
     *
     * @return array{user: User, is_new: bool}
     */
    public function handleApiLogin(string $accessToken): array
    {
        /** @var GoogleProvider $provider */
        $provider = Socialite::driver('google');
        $googleUser = $provider->stateless()->userFromToken($accessToken);

        return DB::transaction(function () use ($googleUser) {
            $user = User::where('google_id', $googleUser->getId())->first();
            if ($user) {
                $this->updateAvatar($user, $googleUser);

                return ['user' => $user, 'is_new' => false];
            }

            $user = User::where('email', $googleUser->getEmail())->first();
            if ($user) {
                $user->forceFill([
                    'google_id' => $googleUser->getId(),
                    'google_avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();

                return ['user' => $user, 'is_new' => false];
            }

            $user = new User;
            $user->forceFill([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'google_avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ])->save();

            return ['user' => $user, 'is_new' => true];
        });
    }

    private function driver(): GoogleProvider
    {
        /** @var GoogleProvider */
        return Socialite::driver('google');
    }

    private function updateAvatar(User $user, SocialiteUser $googleUser): void
    {
        if ($user->google_avatar !== $googleUser->getAvatar()) {
            $user->update(['google_avatar' => $googleUser->getAvatar()]);
        }
    }
}
