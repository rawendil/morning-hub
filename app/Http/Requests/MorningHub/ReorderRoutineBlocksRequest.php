<?php

namespace App\Http\Requests\MorningHub;

use Illuminate\Foundation\Http\FormRequest;

class ReorderRoutineBlocksRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'blocks' => ['required', 'array'],
            'blocks.*' => ['required', 'integer', 'exists:routine_blocks,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! is_array($this->blocks)) {
                return;
            }

            $userBlockIds = $this->user()->routineBlocks()->pluck('id')->toArray();

            foreach ($this->blocks as $blockId) {
                if (! in_array($blockId, $userBlockIds)) {
                    $validator->errors()->add('blocks', 'All blocks must belong to you.');

                    return;
                }
            }
        });
    }
}
