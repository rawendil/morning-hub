<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClickUpService
{
    private const BASE_URL = 'https://api.clickup.com/api/v2';

    public function __construct(
        private readonly string $apiToken,
        private readonly ?int $connectionId = null,
    ) {}

    public function testConnection(): bool
    {
        try {
            return $this->request('get', self::BASE_URL.'/team')->successful();
        } catch (\RuntimeException) {
            return false;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function getWorkspaces(): array
    {
        return $this->request('get', self::BASE_URL.'/team')->json('teams', []);
    }

    /** @return array<int, array<string, mixed>> */
    public function getSpaces(string $workspaceId): array
    {
        return $this->request('get', self::BASE_URL."/team/{$workspaceId}/space")->json('spaces', []);
    }

    /** @return array<int, array<string, mixed>> */
    public function getFolders(string $spaceId): array
    {
        return $this->request('get', self::BASE_URL."/space/{$spaceId}/folder")->json('folders', []);
    }

    /** @return array<int, array<string, mixed>> */
    public function getLists(string $folderId): array
    {
        return $this->request('get', self::BASE_URL."/folder/{$folderId}/list")->json('lists', []);
    }

    /** @return array<int, array<string, mixed>> */
    public function getFolderlessLists(string $spaceId): array
    {
        return $this->request('get', self::BASE_URL."/space/{$spaceId}/list")->json('lists', []);
    }

    /** @return array{folders: array<int, array{id: string, name: string, lists: array<int, array<string, mixed>>}>, folderless: array<int, array<string, mixed>>} */
    public function getAllListsInSpace(string $spaceId): array
    {
        $folders = $this->getFolders($spaceId);
        $folderlessLists = $this->getFolderlessLists($spaceId);

        $groupedFolders = array_map(fn (array $folder) => [
            'id' => $folder['id'],
            'name' => $folder['name'],
            'lists' => $folder['lists'] ?? [],
        ], $folders);

        return [
            'folders' => $groupedFolders,
            'folderless' => $folderlessLists,
        ];
    }

    /**
     * @param  array<int, string>  $listIds
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getTasksFromLists(array $listIds, array $filters = []): array
    {
        if (empty($listIds)) {
            return [];
        }

        if (count($listIds) === 1) {
            return $this->getTasks($listIds[0], $filters);
        }

        $query = array_merge([
            'include_closed' => 'false',
            'subtasks' => 'true',
        ], $filters);

        $responses = Http::pool(fn (Pool $pool) => array_map(
            fn (string $listId) => $pool
                ->as($listId)
                ->withHeaders([
                    'Authorization' => $this->apiToken,
                    'Content-Type' => 'application/json',
                ])
                ->get(self::BASE_URL."/list/{$listId}/task", $query),
            $listIds
        ));

        $allTasks = [];
        foreach ($listIds as $listId) {
            if (isset($responses[$listId])) {
                if ($responses[$listId]->status() === 401) {
                    $this->logAuthFailure("/list/{$listId}/task");
                    throw new \RuntimeException('Token ClickUp jest nieważny lub wygasł. Połącz konto ponownie w ustawieniach.');
                }

                if ($responses[$listId]->ok()) {
                    $allTasks = array_merge($allTasks, $responses[$listId]->json('tasks', []));
                }
            }
        }

        usort($allTasks, function ($a, $b) {
            $aDue = $a['due_date'] ?? PHP_INT_MAX;
            $bDue = $b['due_date'] ?? PHP_INT_MAX;

            return $aDue <=> $bDue;
        });

        return $allTasks;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getTasks(string $listId, array $filters = []): array
    {
        $query = array_merge([
            'include_closed' => 'false',
            'subtasks' => 'true',
        ], $filters);

        return $this->request('get', self::BASE_URL."/list/{$listId}/task", $query)
            ->json('tasks', []);
    }

    /** @return array<string, mixed> */
    public function getTask(string $taskId): array
    {
        return $this->request('get', self::BASE_URL."/task/{$taskId}", ['include_subtasks' => 'true'])
            ->json();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateTask(string $taskId, array $data): array
    {
        return $this->request('put', self::BASE_URL."/task/{$taskId}", $data)
            ->json();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createTask(string $listId, array $data): array
    {
        return $this->request('post', self::BASE_URL."/list/{$listId}/task", $data)
            ->json();
    }

    /** @return array<string, mixed> */
    public function createComment(string $taskId, string $commentText): array
    {
        return $this->request('post', self::BASE_URL."/task/{$taskId}/comment", [
            'comment_text' => $commentText,
            'notify_all' => false,
        ])->json();
    }

    /** @return array<int, array<string, mixed>> */
    public function getComments(string $taskId): array
    {
        return $this->request('get', self::BASE_URL."/task/{$taskId}/comment")
            ->json('comments', []);
    }

    /** @return array<int, array<string, mixed>> */
    public function getListStatuses(string $listId): array
    {
        return $this->request('get', self::BASE_URL."/list/{$listId}")
            ->json('statuses', []);
    }

    /**
     * @param  array<int, string>  $listIds
     * @return array<string, array<int, array<string, mixed>>> Keyed by listId
     */
    public function getMultipleListStatuses(array $listIds): array
    {
        if (empty($listIds)) {
            return [];
        }

        if (count($listIds) === 1) {
            return [$listIds[0] => $this->getListStatuses($listIds[0])];
        }

        $responses = Http::pool(fn (Pool $pool) => array_map(
            fn (string $listId) => $pool
                ->as($listId)
                ->withHeaders([
                    'Authorization' => $this->apiToken,
                    'Content-Type' => 'application/json',
                ])
                ->get(self::BASE_URL."/list/{$listId}"),
            $listIds
        ));

        $result = [];
        foreach ($listIds as $listId) {
            if (isset($responses[$listId]) && $responses[$listId]->ok()) {
                $result[$listId] = $responses[$listId]->json('statuses', []);
            } else {
                $result[$listId] = [];
            }
        }

        return $result;
    }

    /** @return array{id: int, username: string, email: string} */
    public function getAuthenticatedUser(): array
    {
        return $this->request('get', self::BASE_URL.'/user')->json('user', []);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function request(string $method, string $url, array $data = []): Response
    {
        /** @var Response $response */
        $response = $this->client()->{$method}($url, $data);

        if ($response->status() === 401) {
            $this->logAuthFailure(Str::after($url, self::BASE_URL));
            throw new \RuntimeException('Token ClickUp jest nieważny lub wygasł. Połącz konto ponownie w ustawieniach.');
        }

        return $response;
    }

    private function logAuthFailure(string $endpoint): void
    {
        Log::channel('security')->warning('API authentication failed (401)', [
            'provider' => 'clickup',
            'connection_id' => $this->connectionId,
            'endpoint' => $endpoint,
        ]);
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => $this->apiToken,
            'Content-Type' => 'application/json',
        ]);
    }
}
