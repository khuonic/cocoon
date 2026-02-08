<?php

namespace App\Http\Requests\Expense;

use App\Enums\RecurrenceType;
use App\Enums\SplitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'description' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'paid_by' => ['required', 'integer', 'exists:users,id'],
            'split_type' => ['required', Rule::enum(SplitType::class)],
            'split_value' => ['nullable', 'numeric', 'min:0', 'required_if:split_type,custom'],
            'date' => ['required', 'date'],
            'is_recurring' => ['boolean'],
            'recurrence_type' => ['nullable', 'required_if:is_recurring,true', Rule::enum(RecurrenceType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Le montant est obligatoire.',
            'amount.min' => 'Le montant doit être supérieur à 0.',
            'description.required' => 'La description est obligatoire.',
            'category_id.required' => 'La catégorie est obligatoire.',
            'category_id.exists' => 'Cette catégorie n\'existe pas.',
            'paid_by.required' => 'Le payeur est obligatoire.',
            'split_type.required' => 'Le type de répartition est obligatoire.',
            'split_value.required_if' => 'Le montant personnalisé est obligatoire pour une répartition custom.',
            'date.required' => 'La date est obligatoire.',
            'recurrence_type.required_if' => 'Le type de récurrence est obligatoire pour une dépense récurrente.',
        ];
    }
}
