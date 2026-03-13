<?php

namespace App\Services;

class ClickUpServiceFactory
{
    public function make(string $apiToken, ?int $connectionId = null): ClickUpService
    {
        return new ClickUpService($apiToken, $connectionId);
    }
}
