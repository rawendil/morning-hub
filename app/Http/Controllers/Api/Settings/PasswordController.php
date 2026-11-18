<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    public function update(PasswordUpdateRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->update(['password' => $request->password]);

        return response()->json(['message' => 'Password updated.']);
    }
}
