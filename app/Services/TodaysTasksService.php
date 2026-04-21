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

        $todayEnd = now()->endOfDay()->getTimestampMs();

        $groups = [];

        foreach ($connections as $connection) {
            try {
                $service = $this->clickUpServiceFactory->make($connection->api_token, $connection->id);
                $clickUpUser = $service->getAuthenticatedUser();
                /** @var array<int, string> $listIds */
                $listIds = $connection->default_list_ids ?? [];

                if (empty($listIds)) {
                    $groups[] = [
                        'connectionId' => $connection->id,
                        'connectionName' => $connection->name,
                        'tasks' => [],
                        'statuses' => [],
                        'error' => null,
                    ];

                    continue;
                }

                $allListStatuses = $service->getMultipleListStatuses($listIds);

                $activeStatusNames = collect($allListStatuses)
                    ->flatten(1)
                    ->filter(fn (array $s) => $s['type'] === 'custom')
                    ->pluck('status')
                    ->unique()
                    ->values()
                    ->all();

                $statuses = collect($allListStatuses)
                    ->flatten(1)
                    ->unique('status')
                    ->values()
                    ->all();

                /** @var array<string, mixed> $connectionFilters */
                $connectionFilters = $connection->default_filters ?? [];
                $selectedStatuses = isset($connectionFilters['statuses']) && is_array($connectionFilters['statuses']) && count($connectionFilters['statuses']) > 0
                    ? $connectionFilters['statuses']
                    : $activeStatusNames;

                // Query 1: overdue + due today
                $dueDateTasks = $service->getTasksFromLists($listIds, [
                    'assignees' => [$clickUpUser['id']],
                    'due_date_lt' => $todayEnd + 1,
                ]);

                // Query 2: tasks in selected/active statuses (regardless of due date)
                $statusTasks = [];
                if (! empty($selectedStatuses)) {
                    $statusTasks = $service->getTasksFromLists($listIds, [
                        'assignees' => [$clickUpUser['id']],
                        'statuses' => $selectedStatuses,
                    ]);
                }

                $tasks = $this->mergeAndDeduplicateTasks($dueDateTasks, $statusTasks);

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
     * @param  array<int, array<string, mixed>>  $set1
     * @param  array<int, array<string, mixed>>  $set2
     * @return array<int, array<string, mixed>>
     */
    private function mergeAndDeduplicateTasks(array $set1, array $set2): array
    {
        $merged = collect([...$set1, ...$set2])
            ->unique('id')
            ->sortBy(fn (array $task) => $task['due_date'] ?? PHP_INT_MAX)
            ->values()
            ->all();

        return $merged;
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
