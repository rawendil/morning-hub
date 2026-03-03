<?php

namespace App\Http\Requests\MorningHub;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClickUpConnectionRequest extends FormRequest
{
    /** Determine if the user is authorized to make this request. */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('connection'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'api_token' => ['sometimes', 'string'],
            'workspace_id' => ['nullable', 'string'],
            'default_space_id' => ['nullable', 'string'],
            'default_folder_id' => ['nullable', 'string'],
            'default_list_id' => ['nullable', 'string'],
            'default_list_ids' => ['nullable', 'array'],
            'default_list_ids.*' => ['string'],
        ];
    }
}
