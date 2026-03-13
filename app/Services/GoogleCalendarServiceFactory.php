<?php

namespace App\Services;

use App\Models\GoogleCalendarConnection;

class GoogleCalendarServiceFactory
{
    public function make(GoogleCalendarConnection $connection): GoogleCalendarService
    {
        return new GoogleCalendarService($connection);
    }
}
