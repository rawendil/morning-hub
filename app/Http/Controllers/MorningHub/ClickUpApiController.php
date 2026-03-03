<?php

namespace App\Http\Controllers\MorningHub;

use App\Http\Controllers\Controller;
use App\Models\ClickUpConnection;
use App\Services\ClickUpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClickUpApiController extends Controller
{
    public function workspaces(Request $request, ClickUpConnection $connection): JsonResponse
    {
        Gate::authorize('view', $connection);

        $service = new ClickUpService($connection->api_token);

        return response()->json(['data' => $service->getWorkspaces()]);
    }

    public function spaces(Request $request, ClickUpConnection $connection): JsonResponse
    {
        Gate::authorize('view', $connection);
        $request->validate(['workspace_id' => 'required|string']);

        $service = new ClickUpService($connection->api_token);

        return response()->json(['data' => $service->getSpaces($request->input('workspace_id'))]);
    }

    public function folders(Request $request, ClickUpConnection $connection): JsonResponse
    {
        Gate::authorize('view', $connection);
        $request->validate(['space_id' => 'required|string']);

        $service = new ClickUpService($connection->api_token);

        return response()->json(['data' => $service->getFolders($request->input('space_id'))]);
    }

    public function lists(Request $request, ClickUpConnection $connection): JsonResponse
    {
        Gate::authorize('view', $connection);

        $service = new ClickUpService($connection->api_token);

        if ($request->has('folder_id')) {
            return response()->json(['data' => $service->getLists($request->input('folder_id'))]);
        }

        $request->validate(['space_id' => 'required|string']);

        return response()->json(['data' => $service->getFolderlessLists($request->input('space_id'))]);
    }

    public function task(Request $request, ClickUpConnection $connection, string $taskId): JsonResponse
    {
        Gate::authorize('view', $connection);

        $service = new ClickUpService($connection->api_token);

        return response()->json(['data' => $service->getTask($taskId)]);
    }
}
