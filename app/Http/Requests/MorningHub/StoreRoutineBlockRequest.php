<?php

namespace App\Http\Requests\MorningHub;

use App\Enums\BlockType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoutineBlockRequest extends FormRequest
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
            'type' => ['required', Rule::in(array_column(BlockType::cases(), 'value'))],
            'name' => ['required', 'string', 'max:255'],
            'timer_minutes' => ['nullable', 'integer', 'min:1', 'max:120'],
            'clickup_connection_id' => ['nullable', 'integer', 'exists:clickup_connections,id'],
            'config' => ['nullable', 'array'],
            'config.habits' => ['required_if:type,habits', 'array', 'min:1'],
            'config.habits.*' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->clickup_connection_id) {
                $belongs = $this->user()->clickUpConnections()
                    ->where('id', $this->clickup_connection_id)->exists();
                if (! $belongs) {
                    $validator->errors()->add('clickup_connection_id', 'This connection does not belong to you.');
                }
            }
        });
    }
}
