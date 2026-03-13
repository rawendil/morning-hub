<?php

namespace App\Http\Controllers\MorningHub;

use App\Exceptions\GoogleCalendarAuthException;
use App\Http\Controllers\Controller;
use App\Http\Requests\MorningHub\UpdateGoogleCalendarConnectionRequest;
use App\Services\GoogleCalendarServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GoogleCalendarConnectionController extends Controller
{
    public function __construct(
        private readonly GoogleCalendarServiceFactory $serviceFactory,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('morning-hub/GoogleCalendar', [
            'connection' => $request->user()->googleCalendarConnection,
            'hasGoogleAccount' => $request->user()->hasGoogleLinked(),
        ]);
    }

    public function update(UpdateGoogleCalendarConnectionRequest $request): RedirectResponse
    {
        $request->user()->googleCalendarConnection->update([
            'calendar_ids' => $request->validated('calendar_ids'),
        ]);

        return redirect()->route('morning-hub.google-calendar.index')
            ->with('success', __('Kalendarze zostały zapisane.'));
    }

    public function test(Request $request): JsonResponse
    {
        $connection = $request->user()->googleCalendarConnection;

        if (! $connection) {
            return response()->json(['success' => false, 'message' => 'No connection'], 404);
        }

        try {
            $service = $this->serviceFactory->make($connection);
            $success = $service->testConnection();

            return response()->json([
                'success' => $success,
                'message' => $success ? __('Połączenie działa.') : __('Połączenie nie działa.'),
            ]);
        } catch (GoogleCalendarAuthException) {
            return response()->json([
                'success' => false,
                'message' => __('Token wygasł. Połącz ponownie Google Calendar.'),
            ]);
        }
    }
}
