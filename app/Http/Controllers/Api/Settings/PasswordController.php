<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    public function update(PasswordUpdateRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->update(['password' => $request->password]);

        return response()->json(['message' => 'Password updated.']);
    }
}
