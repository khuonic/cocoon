<?php

namespace App\Http\Requests\Bookmark;

use App\Enums\BookmarkCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookmarkRequest extends FormRequest
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
            'url' => ['required', 'url', 'max:2048'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['nullable', 'string', Rule::in(array_column(BookmarkCategory::cases(), 'value'))],
            'is_favorite' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required' => 'L\'URL est obligatoire.',
            'url.url' => 'L\'URL n\'est pas valide.',
            'url.max' => 'L\'URL ne peut pas dépasser 2048 caractères.',
            'title.required' => 'Le titre est obligatoire.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',
            'description.max' => 'La description ne peut pas dépasser 1000 caractères.',
            'category.in' => 'La catégorie sélectionnée n\'est pas valide.',
        ];
    }
}
