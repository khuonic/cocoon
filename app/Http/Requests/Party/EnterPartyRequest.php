<?php

namespace App\Http\Requests\Party;

use Illuminate\Foundation\Http\FormRequest;

class EnterPartyRequest extends FormRequest
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
            'password' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Le mot de passe est obligatoire.',
        ];
    }
}
