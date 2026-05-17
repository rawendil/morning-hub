<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MorningHub\UpdateClickUpConnectionRequest;
use App\Models\ClickUpConnection;
use App\Services\ClickUpServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ClickUpConnectionController extends Controller
{
    public function __construct(
        private readonly ClickUpServiceFactory $clickUpServiceFactory,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json([
            'connections' => $user->clickUpConnections()->latest()->get(),
        ]);
    }

    public function update(UpdateClickUpConnectionRequest $request, ClickUpConnection $connection): JsonResponse
    {
        Gate::authorize('update', $connection);

        $data = $request->validated();

        if (array_key_exists('default_list_ids', $data)) {
            $data['default_list_id'] = $data['default_list_ids'][0] ?? null;
        }

        $connection->update($data);

        return response()->json(['connection' => $connection->fresh()]);
    }

    public function destroy(Request $request, ClickUpConnection $connection): Response
    {
        Gate::authorize('delete', $connection);
        $connection->delete();

        return response()->noContent();
    }

    public function test(Request $request, ClickUpConnection $connection): JsonResponse
    {
        Gate::authorize('view', $connection);

        $service = $this->clickUpServiceFactory->make($connection->api_token);
        $success = $service->testConnection();

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Connection successful.' : 'Connection failed.',
        ]);
    }
}
