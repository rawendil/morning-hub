<?php

namespace App\Services;

use App\Models\User;

class TodaysTasksService
{
    public function __construct(
        private readonly ClickUpServiceFactory $clickUpServiceFactory,
        private readonly GoogleCalendarServiceFactory $googleCalendarServiceFactory,
    ) {}

    /**
     * @param  array<int, int>  $connectionIds
     * @return array{groups: array<int, array{connectionId: int, connectionName: string, tasks: array<int, array<string, mixed>>, statuses: array<int, array<string, mixed>>, error: string|null}>}
     */
    public function fetchGroupedTasks(User $user, array $connectionIds): array
    {
        $connections = $user->clickUpConnections()->whereIn('id', $connectionIds)->get();

        $todayStart = now()->startOfDay()->getTimestampMs();
        $todayEnd = now()->endOfDay()->getTimestampMs();

        $groups = [];

        foreach ($connections as $connection) {
            try {
                $service = $this->clickUpServiceFactory->make($connection->api_token, $connection->id);
                $clickUpUser = $service->getAuthenticatedUser();

                $filters = [
                    'assignees' => [$clickUpUser['id']],
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

    /**
     * @return array{events: array<int, array<string, mixed>>, error: string|null}
     */
    public function fetchCalendarEvents(User $user): array
    {
        $connection = $user->googleCalendarConnection;

        if (! $connection) {
            return ['events' => [], 'error' => null];
        }

        $service = $this->googleCalendarServiceFactory->make($connection);

        return $service->getEventsForDashboard();
    }
}
