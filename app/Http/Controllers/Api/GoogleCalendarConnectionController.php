<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MorningHub\UpdateGoogleCalendarConnectionRequest;
use App\Services\GoogleCalendarServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleCalendarConnectionController extends Controller
{
    public function __construct(
        private readonly GoogleCalendarServiceFactory $googleCalendarServiceFactory,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json([
            'connection' => $user->googleCalendarConnection,
            'hasGoogleAccount' => $user->hasGoogleLinked(),
        ]);
    }

    public function update(UpdateGoogleCalendarConnectionRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $connection = $user->googleCalendarConnection;

        if (! $connection) {
            return response()->json(['message' => 'No Google Calendar connection found.'], 404);
        }

        $connection->update($request->validated());

        return response()->json(['connection' => $connection->fresh()]);
    }

    public function destroy(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->googleCalendarConnection?->delete();

        return response()->json(['message' => 'Disconnected.']);
    }

    public function test(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $connection = $user->googleCalendarConnection;

        if (! $connection) {
            return response()->json(['success' => false, 'message' => 'No connection found.']);
        }

        try {
            $service = $this->googleCalendarServiceFactory->make($connection);
            $service->listCalendars();
            $success = true;
            $message = 'Connection successful.';
        } catch (\Throwable) {
            $success = false;
            $message = 'Connection failed.';
        }

        return response()->json(['success' => $success, 'message' => $message]);
    }
}
