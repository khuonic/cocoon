<?php

namespace App\Http\Requests\Note;

use App\Enums\NoteColor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNoteRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
            'is_pinned' => ['required', 'boolean'],
            'color' => ['nullable', 'string', Rule::in(array_column(NoteColor::cases(), 'value'))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est obligatoire.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',
            'content.required' => 'Le contenu est obligatoire.',
            'content.max' => 'Le contenu ne peut pas dépasser 10 000 caractères.',
            'color.in' => 'La couleur sélectionnée n\'est pas valide.',
        ];
    }
}
