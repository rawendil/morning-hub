<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly GoogleAuthService $googleAuthService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'access_token' => ['required', 'string'],
        ]);

        $result = $this->googleAuthService->handleApiLogin($request->access_token);

        $token = $result['user']->createToken('web')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $result['user']->only(['id', 'name', 'email', 'google_avatar']),
        ]);
    }
}
