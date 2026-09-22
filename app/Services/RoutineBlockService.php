<?php

namespace App\Services;

use App\Models\RoutineBlock;
use App\Models\User;
use Illuminate\Support\Str;

class RoutineBlockService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $user, array $attributes): RoutineBlock
    {
        $nextSortOrder = (int) $user->routineBlocks()->max('sort_order') + 1;

        return $user->routineBlocks()->create(array_merge(
            $this->withHabitIdentities($attributes),
            ['sort_order' => $nextSortOrder],
        ));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(RoutineBlock $block, array $attributes): RoutineBlock
    {
        $block->update($this->withHabitIdentities($attributes));

        return $block->fresh();
    }

    /**
     * Give every habit a stable identifier so that completion history survives
     * renaming and reordering. Existing identifiers are never rewritten.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withHabitIdentities(array $attributes): array
    {
        if (! isset($attributes['config']['habits']) || ! is_array($attributes['config']['habits'])) {
            return $attributes;
        }

        $attributes['config']['habits'] = array_values(array_map(
            /**
             * @param  array<string, mixed>  $habit
             * @return array{id: string, label: mixed}
             */
            fn (array $habit): array => [
                'id' => isset($habit['id']) && is_string($habit['id']) && $habit['id'] !== ''
                    ? $habit['id']
                    : (string) Str::uuid(),
                'label' => $habit['label'] ?? '',
            ],
            $attributes['config']['habits'],
        ));

        return $attributes;
    }
}
