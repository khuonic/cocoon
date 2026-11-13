<?php

namespace App\Http\Requests\Party;

use Illuminate\Foundation\Http\FormRequest;

class AnswerStepRequest extends FormRequest
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
            'step' => ['required', 'string', 'max:50'],
            'choice' => ['required', 'string', 'in:a,b,c'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'step.required' => "L'étape est obligatoire.",
            'choice.required' => 'Choisis une réponse.',
            'choice.in' => "Ce choix n'existe pas.",
        ];
    }
}
