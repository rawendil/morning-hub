<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MorningHub\UpdateTodaysTasksConfigRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TodaysTasksConfigController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json([
            'config' => $user->todaysTasksConfig,
        ]);
    }

    public function update(UpdateTodaysTasksConfigRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $config = $user->todaysTasksConfig()->updateOrCreate(
            ['user_id' => $user->id],
            $request->validated()
        );

        return response()->json(['config' => $config]);
    }
}
