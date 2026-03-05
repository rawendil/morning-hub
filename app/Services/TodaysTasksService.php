<?php

namespace App\Services;

use App\Models\ClickUpConnection;

class TodaysTasksService
{
    /**
     * @param  array<int, int>  $connectionIds
     * @return array{groups: array<int, array{connectionId: int, connectionName: string, tasks: array<int, array<string, mixed>>, statuses: array<int, array<string, mixed>>, error: string|null}>}
     */
    public function fetchGroupedTasks(array $connectionIds): array
    {
        $connections = ClickUpConnection::whereIn('id', $connectionIds)->get();

        $todayStart = now()->startOfDay()->getTimestampMs();
        $todayEnd = now()->endOfDay()->getTimestampMs();

        $groups = [];

        foreach ($connections as $connection) {
            try {
                $service = new ClickUpService($connection->api_token);
                $user = $service->getAuthenticatedUser();

                $filters = [
                    'assignees' => [$user['id']],
                    'due_date_gt' => $todayStart - 1,
                    'due_date_lt' => $todayEnd + 1,
                ];

                $listIds = $connection->default_list_ids ?? [];
                $tasks = ! empty($listIds)
                    ? $service->getTasksFromLists($listIds, $filters)
                    : [];

                $statuses = ! empty($listIds)
                    ? $service->getListStatuses($listIds[0])
                    : [];

                $groups[] = [
                    'connectionId' => $connection->id,
                    'connectionName' => $connection->name,
                    'tasks' => $tasks,
                    'statuses' => $statuses,
                    'error' => null,
                ];
            } catch (\Throwable $e) {
                $groups[] = [
                    'connectionId' => $connection->id,
                    'connectionName' => $connection->name,
                    'tasks' => [],
                    'statuses' => [],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return ['groups' => $groups];
    }
}
