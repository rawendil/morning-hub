<?php

namespace App\Policies;

use App\Models\RoutineBlock;
use App\Models\User;

class RoutineBlockPolicy
{
    /** Determine whether the user can view the model. */
    public function view(User $user, RoutineBlock $routineBlock): bool
    {
        return $routineBlock->user_id === $user->id;
    }

    /** Determine whether the user can update the model. */
    public function update(User $user, RoutineBlock $routineBlock): bool
    {
        return $routineBlock->user_id === $user->id;
    }

    /** Determine whether the user can delete the model. */
    public function delete(User $user, RoutineBlock $routineBlock): bool
    {
        return $routineBlock->user_id === $user->id;
    }
}
