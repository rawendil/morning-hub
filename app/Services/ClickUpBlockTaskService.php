<?php

namespace App\Services;

use App\Models\ClickUpConnection;

class ClickUpBlockTaskService
{
    public function __construct(
        private readonly ClickUpServiceFactory $clickUpServiceFactory,
    ) {}

    /**
     * Fetch the tasks for a ClickUp connection using its configured default lists and filters.
     *
     * @return array{tasks: array<int, array<string, mixed>>, error: string|null}
     */
    public function forConnection(ClickUpConnection $connection): array
    {
        $hasLists = ! empty($connection->default_list_ids) || $connection->default_list_id;

        if (! $hasLists) {
            return ['tasks' => [], 'error' => null];
        }

        try {
            $service = $this->clickUpServiceFactory->make($connection->api_token, $connection->id);

            /** @var array<int, string> $listIds */
            $listIds = $connection->default_list_ids;
            $filters = $connection->default_filters ?? [];

            $tasks = ! empty($listIds)
                ? $service->getTasksFromLists($listIds, $filters)
                : $service->getTasks($connection->default_list_id, $filters);

            return ['tasks' => $tasks, 'error' => null];
        } catch (\Throwable $e) {
            return ['tasks' => [], 'error' => $e->getMessage()];
        }
    }
}
