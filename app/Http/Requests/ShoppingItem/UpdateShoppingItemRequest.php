<?php

namespace App\Http\Requests\ShoppingItem;

use App\Enums\ShoppingItemCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShoppingItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('category') === '') {
            $this->merge(['category' => null]);
        }
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', Rule::enum(ShoppingItemCategory::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de l\'article est obligatoire.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'category.Illuminate\Validation\Rules\Enum' => 'La catégorie sélectionnée est invalide.',
        ];
    }
}
