<?php

namespace App\Http\Requests\MorningHub;

use Illuminate\Foundation\Http\FormRequest;

class SetArticleReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'link' => ['required', 'string', 'url', 'max:2048'],
            'read' => ['required', 'boolean'],
        ];
    }
}
