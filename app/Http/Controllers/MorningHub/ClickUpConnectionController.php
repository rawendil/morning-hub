<?php

namespace App\Http\Controllers\MorningHub;

use App\Http\Controllers\Controller;
use App\Http\Requests\MorningHub\StoreClickUpConnectionRequest;
use App\Http\Requests\MorningHub\UpdateClickUpConnectionRequest;
use App\Models\ClickUpConnection;
use App\Services\ClickUpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClickUpConnectionController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('morning-hub/ClickUp', [
            'connections' => $request->user()->clickUpConnections()->latest()->get(),
        ]);
    }

    public function store(StoreClickUpConnectionRequest $request): RedirectResponse
    {
        $service = new ClickUpService($request->validated('api_token'));

        if (! $service->testConnection()) {
            return back()->withErrors(['api_token' => 'The API token is invalid or the connection failed.']);
        }

        $request->user()->clickUpConnections()->create($request->validated());

        return to_route('morning-hub.clickup.index');
    }

    public function update(UpdateClickUpConnectionRequest $request, ClickUpConnection $connection): RedirectResponse
    {
        $data = $request->validated();

        if (isset($data['api_token'])) {
            $service = new ClickUpService($data['api_token']);
            if (! $service->testConnection()) {
                return back()->withErrors(['api_token' => 'The API token is invalid or the connection failed.']);
            }
        }

        if (array_key_exists('default_list_ids', $data)) {
            $data['default_list_id'] = $data['default_list_ids'][0] ?? null;
        }

        $connection->update($data);

        return to_route('morning-hub.clickup.index');
    }

    public function destroy(Request $request, ClickUpConnection $connection): RedirectResponse
    {
        Gate::authorize('delete', $connection);

        $connection->delete();

        return to_route('morning-hub.clickup.index');
    }

    public function test(Request $request, ClickUpConnection $connection): JsonResponse
    {
        Gate::authorize('view', $connection);

        $service = new ClickUpService($connection->api_token);
        $success = $service->testConnection();

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Połączenie udane.' : 'Połączenie nieudane. Sprawdź token API.',
        ]);
    }
}
