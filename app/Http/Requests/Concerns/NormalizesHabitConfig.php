<?php

namespace App\Http\Requests\Concerns;

trait NormalizesHabitConfig
{
    /**
     * Accept habits as plain strings for backwards compatibility and reshape
     * them into the `{id, label}` form the rest of the validation expects.
     */
    protected function normalizeHabitConfig(): void
    {
        $habits = $this->input('config.habits');

        if (! is_array($habits)) {
            return;
        }

        $normalized = array_map(
            fn (mixed $habit): mixed => is_string($habit) ? ['label' => $habit] : $habit,
            $habits,
        );

        $this->merge(['config' => array_merge(
            (array) $this->input('config', []),
            ['habits' => $normalized],
        )]);
    }
}
