<?php

namespace App\Http\Controllers\MorningHub;

use App\Http\Controllers\Controller;
use App\Models\ClickUpConnection;
use App\Services\ClickUpServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClickUpApiController extends Controller
{
    public function __construct(
        private readonly ClickUpServiceFactory $clickUpServiceFactory,
    ) {}

    public function workspaces(Request $request, ClickUpConnection $connection): JsonResponse
    {
        Gate::authorize('view', $connection);

        $service = $this->clickUpServiceFactory->make($connection->api_token, $connection->id);

        return response()->json(['data' => $service->getWorkspaces()]);
    }

    public function spaces(Request $request, ClickUpConnection $connection): JsonResponse
    {
        Gate::authorize('view', $connection);
        $request->validate(['workspace_id' => 'required|string']);

        $service = $this->clickUpServiceFactory->make($connection->api_token, $connection->id);

        return response()->json(['data' => $service->getSpaces($request->input('workspace_id'))]);
    }

    public function folders(Request $request, ClickUpConnection $connection): JsonResponse
    {
        Gate::authorize('view', $connection);
        $request->validate(['space_id' => 'required|string']);

        $service = $this->clickUpServiceFactory->make($connection->api_token, $connection->id);

        return response()->json(['data' => $service->getFolders($request->input('space_id'))]);
    }

    public function lists(Request $request, ClickUpConnection $connection): JsonResponse
    {
        Gate::authorize('view', $connection);

        $service = $this->clickUpServiceFactory->make($connection->api_token, $connection->id);

        if ($request->has('folder_id')) {
            return response()->json(['data' => $service->getLists($request->input('folder_id'))]);
        }

        $request->validate(['space_id' => 'required|string']);

        return response()->json(['data' => $service->getFolderlessLists($request->input('space_id'))]);
    }

    public function allLists(Request $request, ClickUpConnection $connection): JsonResponse
    {
        Gate::authorize('view', $connection);
        $request->validate(['space_id' => 'required|string']);

        $service = $this->clickUpServiceFactory->make($connection->api_token, $connection->id);

        return response()->json(['data' => $service->getAllListsInSpace($request->input('space_id'))]);
    }

    public function me(ClickUpConnection $connection): JsonResponse
    {
        Gate::authorize('view', $connection);

        $service = $this->clickUpServiceFactory->make($connection->api_token, $connection->id);

        return response()->json(['data' => $service->getAuthenticatedUser()]);
    }

    public function task(Request $request, ClickUpConnection $connection, string $taskId): JsonResponse
    {
        Gate::authorize('view', $connection);

        $service = $this->clickUpServiceFactory->make($connection->api_token, $connection->id);

        return response()->json(['data' => $service->getTask($taskId)]);
    }

    public function updateTask(Request $request, ClickUpConnection $connection, string $taskId): JsonResponse
    {
        Gate::authorize('view', $connection);

        $validated = $request->validate([
            'status' => ['sometimes', 'string'],
            'priority' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:4'],
            'due_date' => ['sometimes', 'nullable', 'integer'],
            'name' => ['sometimes', 'string', 'max:500'],
        ]);

        $service = $this->clickUpServiceFactory->make($connection->api_token, $connection->id);

        return response()->json(['data' => $service->updateTask($taskId, $validated)]);
    }

    public function createTask(Request $request, ClickUpConnection $connection): JsonResponse
    {
        Gate::authorize('view', $connection);

        $validated = $request->validate([
            'list_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:500'],
            'description' => ['sometimes', 'string', 'max:5000'],
        ]);

        $listId = $validated['list_id'];
        unset($validated['list_id']);

        $service = $this->clickUpServiceFactory->make($connection->api_token, $connection->id);

        return response()->json(['data' => $service->createTask($listId, $validated)], 201);
    }

    public function createComment(Request $request, ClickUpConnection $connection, string $taskId): JsonResponse
    {
        Gate::authorize('view', $connection);

        $validated = $request->validate([
            'comment_text' => ['required', 'string', 'max:10000'],
        ]);

        $service = $this->clickUpServiceFactory->make($connection->api_token, $connection->id);

        return response()->json(['data' => $service->createComment($taskId, $validated['comment_text'])], 201);
    }

    public function comments(Request $request, ClickUpConnection $connection, string $taskId): JsonResponse
    {
        Gate::authorize('view', $connection);

        $service = $this->clickUpServiceFactory->make($connection->api_token, $connection->id);

        return response()->json(['data' => $service->getComments($taskId)]);
    }

    public function statuses(Request $request, ClickUpConnection $connection): JsonResponse
    {
        Gate::authorize('view', $connection);
        $request->validate(['list_id' => ['required', 'string']]);

        $service = $this->clickUpServiceFactory->make($connection->api_token, $connection->id);

        return response()->json(['data' => $service->getListStatuses($request->input('list_id'))]);
    }
}
