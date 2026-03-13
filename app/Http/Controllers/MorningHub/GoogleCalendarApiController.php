<?php

namespace App\Http\Controllers\MorningHub;

use App\Exceptions\GoogleCalendarAuthException;
use App\Http\Controllers\Controller;
use App\Services\GoogleCalendarServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleCalendarApiController extends Controller
{
    public function __construct(
        private readonly GoogleCalendarServiceFactory $serviceFactory,
    ) {}

    public function calendars(Request $request): JsonResponse
    {
        $connection = $request->user()->googleCalendarConnection;

        if (! $connection) {
            return response()->json(['error' => 'No connection'], 404);
        }

        try {
            $service = $this->serviceFactory->make($connection);

            return response()->json([
                'calendars' => $service->listCalendars(),
            ]);
        } catch (GoogleCalendarAuthException) {
            return response()->json([
                'error' => 'auth_expired',
                'calendars' => [],
            ], 401);
        }
    }
}
