<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ClickUpService
{
    private const BASE_URL = 'https://api.clickup.com/api/v2';

    public function __construct(private readonly string $apiToken) {}

    public function testConnection(): bool
    {
        return $this->client()->get(self::BASE_URL.'/team')->successful();
    }

    /** @return array<int, array<string, mixed>> */
    public function getWorkspaces(): array
    {
        return $this->client()->get(self::BASE_URL.'/team')->json('teams', []);
    }

    /** @return array<int, array<string, mixed>> */
    public function getSpaces(string $workspaceId): array
    {
        return $this->client()->get(self::BASE_URL."/team/{$workspaceId}/space")->json('spaces', []);
    }

    /** @return array<int, array<string, mixed>> */
    public function getFolders(string $spaceId): array
    {
        return $this->client()->get(self::BASE_URL."/space/{$spaceId}/folder")->json('folders', []);
    }

    /** @return array<int, array<string, mixed>> */
    public function getLists(string $folderId): array
    {
        return $this->client()->get(self::BASE_URL."/folder/{$folderId}/list")->json('lists', []);
    }

    /** @return array<int, array<string, mixed>> */
    public function getFolderlessLists(string $spaceId): array
    {
        return $this->client()->get(self::BASE_URL."/space/{$spaceId}/list")->json('lists', []);
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

        return $this->client()
            ->get(self::BASE_URL."/list/{$listId}/task", $query)
            ->json('tasks', []);
    }

    /** @return array<string, mixed> */
    public function getTask(string $taskId): array
    {
        return $this->client()
            ->get(self::BASE_URL."/task/{$taskId}", ['include_subtasks' => 'true'])
            ->json();
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => $this->apiToken,
            'Content-Type' => 'application/json',
        ]);
    }
}
