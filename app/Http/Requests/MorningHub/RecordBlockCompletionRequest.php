<?php

namespace App\Http\Requests\MorningHub;

use App\Enums\BlockCompletionStatus;
use App\Models\RoutineBlock;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordBlockCompletionRequest extends FormRequest
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
            'status' => ['required', Rule::enum(BlockCompletionStatus::class)],
            'elapsed_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ];
    }

    public function routineBlock(): RoutineBlock
    {
        /** @var RoutineBlock $block */
        $block = $this->route('block');

        return $block;
    }
}
