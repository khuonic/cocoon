<?php

namespace App\Http\Requests\Calendar;

use App\Enums\EventCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCalendarEventRequest extends FormRequest
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
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'category' => ['required', new Enum(EventCategory::class)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'all_day' => ['boolean'],
            'is_personal' => ['boolean'],
            'reminder_before' => ['nullable', 'integer', 'min:0'],
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
            'category.required' => 'La catégorie est obligatoire.',
            'starts_at.required' => 'La date de début est obligatoire.',
            'starts_at.date' => 'La date de début n\'est pas valide.',
            'ends_at.after_or_equal' => 'La date de fin doit être après la date de début.',
        ];
    }
}
