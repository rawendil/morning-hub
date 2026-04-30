<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\GoogleCalendarAuthException;
use App\Http\Controllers\Controller;
use App\Services\GoogleCalendarServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleCalendarApiController extends Controller
{
    public function __construct(
        private readonly GoogleCalendarServiceFactory $googleCalendarServiceFactory,
    ) {}

    public function calendars(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $connection = $user->googleCalendarConnection;

        if (! $connection) {
            return response()->json(['calendars' => []]);
        }

        try {
            return response()->json([
                'calendars' => $this->googleCalendarServiceFactory->make($connection)->listCalendars(),
            ]);
        } catch (GoogleCalendarAuthException) {
            return response()->json(['error' => 'auth_expired', 'calendars' => []], 401);
        }
    }
}
