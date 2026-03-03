<?php

namespace App\Policies;

use App\Models\ClickUpConnection;
use App\Models\User;

class ClickUpConnectionPolicy
{
    /** Determine whether the user can view the model. */
    public function view(User $user, ClickUpConnection $clickUpConnection): bool
    {
        return $clickUpConnection->user_id === $user->id;
    }

    /** Determine whether the user can update the model. */
    public function update(User $user, ClickUpConnection $clickUpConnection): bool
    {
        return $clickUpConnection->user_id === $user->id;
    }

    /** Determine whether the user can delete the model. */
    public function delete(User $user, ClickUpConnection $clickUpConnection): bool
    {
        return $clickUpConnection->user_id === $user->id;
    }
}
