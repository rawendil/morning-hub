<?php

namespace App\Policies;

use App\Models\GoogleCalendarConnection;
use App\Models\User;

class GoogleCalendarConnectionPolicy
{
    public function view(User $user, GoogleCalendarConnection $model): bool
    {
        return $model->user_id === $user->id;
    }

    public function update(User $user, GoogleCalendarConnection $model): bool
    {
        return $model->user_id === $user->id;
    }

    public function delete(User $user, GoogleCalendarConnection $model): bool
    {
        return $model->user_id === $user->id;
    }
}
