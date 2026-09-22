<?php

namespace App\Http\Requests\MorningHub;

use App\Models\RoutineBlock;
use Illuminate\Foundation\Http\FormRequest;

class StoreHabitCompletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->routineBlock()) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'habit_id' => ['required', 'string', 'max:64'],
            'completed' => ['required', 'boolean'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! in_array($this->input('habit_id'), $this->habitIds(), true)) {
                $validator->errors()->add('habit_id', 'This habit does not belong to the block.');
            }
        });
    }

    public function routineBlock(): RoutineBlock
    {
        /** @var RoutineBlock $block */
        $block = $this->route('block');

        return $block;
    }

    /**
     * @return array<int, string>
     */
    private function habitIds(): array
    {
        $config = $this->routineBlock()->config;
        $habits = is_array($config) ? ($config['habits'] ?? null) : null;

        if (! is_array($habits)) {
            return [];
        }

        $ids = [];

        foreach ($habits as $habit) {
            if (is_array($habit) && isset($habit['id']) && is_string($habit['id'])) {
                $ids[] = $habit['id'];
            }
        }

        return $ids;
    }
}
