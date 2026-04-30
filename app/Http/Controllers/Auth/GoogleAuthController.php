<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $result = $this->googleAuthService->handleCallback();
        } catch (InvalidStateException) {
            return redirect()->route('login')->with('status', __('Logowanie przez Google nie powiodło się. Spróbuj ponownie.'));
        }

        $user = $result['user'];

        if ($result['needs_2fa']) {
            $request->session()->put('login.id', $user->getKey());

            return redirect()->route('two-factor.login');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(config('fortify.home'));
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
