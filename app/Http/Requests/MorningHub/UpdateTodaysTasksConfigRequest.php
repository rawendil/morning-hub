<?php

namespace App\Http\Requests\MorningHub;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTodaysTasksConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'connection_ids' => ['nullable', 'array'],
            'connection_ids.*' => ['required', 'integer', 'exists:clickup_connections,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $connectionIds = $this->input('connection_ids', []);
            if (empty($connectionIds)) {
                return;
            }

            $userIds = $this->user()->clickUpConnections()->pluck('id')->all();
            foreach ($connectionIds as $id) {
                if (! in_array((int) $id, $userIds)) {
                    $validator->errors()->add('connection_ids', __('Niektóre połączenia nie należą do Ciebie.'));
                    break;
                }
            }
        });
    }
}
