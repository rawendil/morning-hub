<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $blocks = $user->routineBlocks()
            ->ordered()
            ->with(['clickUpConnection'])
            ->get();

        return response()->json(['blocks' => $blocks]);
    }
}
