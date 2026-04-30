<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TodaysTasksController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $config = $user->todaysTasksConfig;
        $connectionIds = $config !== null ? ($config->connection_ids ?? []) : [];

        $connections = $user->clickUpConnections()
            ->whereIn('id', $connectionIds)
            ->get();

        return response()->json([
            'config' => $config,
            'connections' => $connections,
        ]);
    }
}
