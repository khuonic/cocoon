<?php

namespace App\Http\Requests\SweetMessage;

use Illuminate\Foundation\Http\FormRequest;

class StoreSweetMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Le message est obligatoire.',
            'content.max' => 'Le message ne peut pas dépasser 500 caractères.',
        ];
    }
}
