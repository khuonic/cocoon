<?php

namespace App\Http\Requests\Recipe;

use App\Enums\MealTag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecipeRequest extends FormRequest
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
            'description' => ['nullable', 'string', 'max:5000'],
            'url' => ['nullable', 'url', 'max:2048'],
            'prep_time' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'cook_time' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'servings' => ['nullable', 'integer', 'min:1', 'max:50'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', Rule::in(array_column(MealTag::cases(), 'value'))],
            'image' => ['nullable', 'image', 'max:10240'],
            'ingredients' => ['nullable', 'array'],
            'ingredients.*.name' => ['required', 'string', 'max:255'],
            'ingredients.*.quantity' => ['nullable', 'string', 'max:50'],
            'ingredients.*.unit' => ['nullable', 'string', 'max:50'],
            'steps' => ['nullable', 'array'],
            'steps.*.instruction' => ['required', 'string', 'max:2000'],
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
            'description.max' => 'La description ne peut pas dépasser 5000 caractères.',
            'url.url' => 'L\'URL n\'est pas valide.',
            'prep_time.min' => 'Le temps de préparation doit être positif.',
            'prep_time.max' => 'Le temps de préparation ne peut pas dépasser 1440 minutes.',
            'cook_time.min' => 'Le temps de cuisson doit être positif.',
            'cook_time.max' => 'Le temps de cuisson ne peut pas dépasser 1440 minutes.',
            'servings.min' => 'Le nombre de portions doit être au moins 1.',
            'servings.max' => 'Le nombre de portions ne peut pas dépasser 50.',
            'tags.*.in' => 'Un des tags sélectionnés n\'est pas valide.',
            'ingredients.*.name.required' => 'Le nom de l\'ingrédient est obligatoire.',
            'ingredients.*.name.max' => 'Le nom de l\'ingrédient ne peut pas dépasser 255 caractères.',
            'steps.*.instruction.required' => 'L\'instruction de l\'étape est obligatoire.',
            'steps.*.instruction.max' => 'L\'instruction ne peut pas dépasser 2000 caractères.',
        ];
    }
}
