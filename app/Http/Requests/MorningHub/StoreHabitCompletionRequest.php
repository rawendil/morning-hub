<?php

namespace App\Http\Requests\MorningHub;

use App\Models\RoutineBlock;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreHabitCompletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->routineBlock()) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'habit_id' => ['required', 'string', 'max:64'],
            'completed' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
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
