<?php

namespace App\Http\Requests\Party;

use App\Http\Middleware\EnsurePartyPlayer;
use App\Models\Party\Player;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StorePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pseudo' => is_string($this->input('pseudo'))
                ? preg_replace('/\s+/u', ' ', trim($this->input('pseudo')))
                : $this->input('pseudo'),
        ]);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $currentPlayer = EnsurePartyPlayer::resolve($this);

        return [
            'pseudo' => [
                'required',
                'string',
                'min:2',
                'max:20',
                'regex:/^[\pL\pN _.\-]+$/u',
                function (string $attribute, mixed $value, Closure $fail) use ($currentPlayer) {
                    if (Player::pseudoIsTaken((string) $value, $currentPlayer?->id)) {
                        $fail('Ce pseudo est déjà pris.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pseudo.required' => 'Choisis un pseudo.',
            'pseudo.min' => 'Le pseudo doit faire au moins 2 caractères.',
            'pseudo.max' => 'Le pseudo ne peut pas dépasser 20 caractères.',
            'pseudo.regex' => 'Lettres, chiffres, espaces, points et tirets uniquement.',
        ];
    }
}
