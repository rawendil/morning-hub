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
            'google_calendar_connection_id' => ['nullable', 'required_if:type,google_calendar', 'integer', 'exists:google_calendar_connections,id'],
            'config' => ['nullable', 'array'],
            'config.icon' => ['nullable', 'string', 'max:50'],
            'config.habits' => ['required_if:type,habits', 'array', 'min:1'],
            'config.habits.*' => ['required', 'string', 'max:255'],
            'config.sources' => ['required_if:type,feed', 'array', 'min:1'],
            'config.sources.*.name' => ['required', 'string', 'max:255'],
            'config.sources.*.url' => ['required', 'url', 'max:500'],
            'config.days' => ['required_if:type,feed', 'integer', 'min:1', 'max:30'],
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

            if ($validator->errors()->isEmpty() && $this->input('google_calendar_connection_id')) {
                $ownsConnection = $this->user()
                    ->googleCalendarConnection()
                    ->where('id', $this->input('google_calendar_connection_id'))
                    ->exists();

                if (! $ownsConnection) {
                    $validator->errors()->add('google_calendar_connection_id', 'Invalid Google Calendar connection.');
                }
            }

        });
    }
}
