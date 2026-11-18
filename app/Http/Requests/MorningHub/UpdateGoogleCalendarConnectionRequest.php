<?php

namespace App\Http\Requests\MorningHub;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGoogleCalendarConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $connection = $this->user()->googleCalendarConnection;

        return $connection && $this->user()->can('update', $connection);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'calendar_ids' => ['present', 'array', 'min:1'],
            'calendar_ids.*' => ['required', 'string', 'max:255'],
        ];
    }
}
