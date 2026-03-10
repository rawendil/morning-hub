<?php

namespace App\Observers;

use App\Models\ClickUpConnection;
use Illuminate\Support\Facades\Log;

class ClickUpConnectionObserver
{
    public function created(ClickUpConnection $clickUpConnection): void
    {
        Log::channel('security')->info('ClickUp connection created', [
            'user_id' => $clickUpConnection->user_id,
            'connection_id' => $clickUpConnection->id,
            'connection_name' => $clickUpConnection->name,
        ]);
    }

    public function updated(ClickUpConnection $clickUpConnection): void
    {
        Log::channel('security')->info('ClickUp connection updated', [
            'user_id' => $clickUpConnection->user_id,
            'connection_id' => $clickUpConnection->id,
            'connection_name' => $clickUpConnection->name,
            'token_changed' => $clickUpConnection->wasChanged('api_token'),
        ]);
    }

    public function deleted(ClickUpConnection $clickUpConnection): void
    {
        Log::channel('security')->info('ClickUp connection deleted', [
            'user_id' => $clickUpConnection->user_id,
            'connection_id' => $clickUpConnection->id,
            'connection_name' => $clickUpConnection->name,
        ]);
    }
}
