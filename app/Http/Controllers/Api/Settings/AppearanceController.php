<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AppearanceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'appearance' => $request->cookie('appearance', 'system'),
        ]);
    }

    public function update(Request $request): Response
    {
        $request->validate(['appearance' => ['required', 'in:light,dark,system']]);

        return response()->noContent()->cookie('appearance', $request->appearance, 60 * 24 * 365);
    }
}
