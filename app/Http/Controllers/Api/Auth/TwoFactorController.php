<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorAuthenticationProvider $provider,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'temp_token' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $cacheKey = "2fa_challenge:{$request->temp_token}";
        $userId = Cache::get($cacheKey);

        if (! $userId) {
            throw ValidationException::withMessages([
                'temp_token' => [__('The session has expired. Please log in again.')],
            ]);
        }

        $user = User::findOrFail($userId);

        if (! $user->two_factor_secret) {
            Cache::forget($cacheKey);
            throw ValidationException::withMessages([
                'temp_token' => [__('The session has expired. Please log in again.')],
            ]);
        }

        if (! $this->provider->verify(decrypt($user->two_factor_secret), $request->code)) {
            Cache::forget($cacheKey);
            throw ValidationException::withMessages([
                'code' => [__('The provided two factor authentication code was invalid.')],
            ]);
        }

        Cache::forget($cacheKey);

        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email', 'google_avatar']),
        ]);
    }
}
