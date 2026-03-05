<?php

namespace App\Http\Requests\MorningHub;

use App\Enums\BlockType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoutineBlockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('block'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::in(array_column(BlockType::cases(), 'value'))],
            'name' => ['required', 'string', 'max:255'],
            'timer_minutes' => ['nullable', 'integer', 'min:1', 'max:120'],
            'clickup_connection_id' => ['nullable', 'integer', 'exists:clickup_connections,id'],
            'config' => ['nullable', 'array'],
            'config.icon' => ['nullable', 'string', 'max:50'],
            'config.habits' => ['required_if:type,habits', 'array', 'min:1'],
            'config.habits.*' => ['required', 'string', 'max:255'],
            'config.sources' => ['required_if:type,feed', 'array', 'min:1'],
            'config.sources.*.name' => ['required', 'string', 'max:255'],
            'config.sources.*.url' => ['required', 'url', 'max:500'],
            'config.days' => ['required_if:type,feed', 'integer', 'min:1', 'max:30'],
            'config.connection_ids' => ['required_if:type,todays_tasks', 'array', 'min:1'],
            'config.connection_ids.*' => ['required', 'integer', 'exists:clickup_connections,id'],
            'config.placeholder_text' => ['nullable', 'string', 'max:500'],
            'config.placeholder_url' => ['nullable', 'url', 'max:500'],
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

            if ($this->input('type') === 'todays_tasks') {
                $connectionIds = $this->input('config.connection_ids', []);
                $userIds = $this->user()->clickUpConnections()->pluck('id')->all();
                foreach ($connectionIds as $id) {
                    if (! in_array((int) $id, $userIds)) {
                        $validator->errors()->add('config.connection_ids', 'Niektóre połączenia nie należą do Ciebie.');
                        break;
                    }
                }
            }
        });
    }
}
