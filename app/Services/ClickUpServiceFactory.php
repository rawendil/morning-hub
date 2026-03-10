<?php

namespace App\Services;

class ClickUpServiceFactory
{
    public function make(string $apiToken): ClickUpService
    {
        return new ClickUpService($apiToken);
    }
}
