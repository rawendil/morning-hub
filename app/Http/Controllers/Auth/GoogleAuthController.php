<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly GoogleAuthService $googleAuthService,
    ) {}

    /**
     * Redirect to Google consent screen (guest).
     */
    public function redirect(): SymfonyRedirectResponse
    {
        return $this->googleAuthService->getRedirectUrl();
    }

    /**
     * Handle Google callback (guest login/register).
     *
     * Issues a Sanctum token and redirects to the SPA.
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $result = $this->googleAuthService->handleCallback();
        } catch (InvalidStateException) {
            return redirect('/login?error=google_failed');
        }

        $user = $result['user'];

        if ($result['needs_2fa']) {
            $tempToken = $user->createToken('google-2fa-temp')->plainTextToken;

            return redirect('/two-factor?temp_token='.urlencode($tempToken));
        }

        $token = $user->createToken('google-auth')->plainTextToken;

        return redirect('/login?google_token='.urlencode($token));
    }

    /**
     * Redirect to Google for account linking (auth).
     */
    public function linkRedirect(): SymfonyRedirectResponse
    {
        return $this->googleAuthService->getLinkRedirectUrl();
    }

    /**
     * Handle Google linking callback (auth).
     */
    public function linkCallback(Request $request): RedirectResponse
    {
        try {
            $this->googleAuthService->linkAccount($request->user());
        } catch (InvalidStateException) {
            return redirect()->to('/settings/profile')->with('error', __('Powiązanie z Google nie powiodło się. Spróbuj ponownie.'));
        } catch (\InvalidArgumentException $e) {
            return redirect()->to('/settings/profile')->with('error', $e->getMessage());
        }

        return redirect()->to('/settings/profile')->with('status', __('Konto Google zostało powiązane.'));
    }

    /**
     * Unlink Google account (auth).
     */
    public function unlink(Request $request): RedirectResponse
    {
        try {
            $this->googleAuthService->unlinkAccount($request->user());
        } catch (\InvalidArgumentException $e) {
            return redirect()->to('/settings/profile')->with('error', $e->getMessage());
        }

        return redirect()->to('/settings/profile')->with('status', __('Konto Google zostało odłączone.'));
    }
}
