<?php

namespace App\Http\Requests\MorningHub;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreClickUpConnectionRequest extends FormRequest
{
    /** Determine if the user is authorized to make this request. */
    public function authorize(): bool
    {
        return true;
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
            'api_token' => ['required', 'string', 'starts_with:pk_', 'min:10'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (app()->isProduction() && ! $this->isSecure()) {
                $validator->errors()->add('api_token', 'API tokens must be submitted over HTTPS.');
            }
        });
    }
}
