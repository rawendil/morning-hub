<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'user' => $user->only(['id', 'name', 'email', 'google_id', 'google_avatar', 'email_verified_at']),
            'locale' => $request->cookie('locale', config('app.locale')),
            'appearance' => $request->cookie('appearance', 'system'),
        ]);
    }
}
