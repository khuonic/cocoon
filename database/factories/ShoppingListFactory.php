<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShoppingList>
 */
class ShoppingListFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'is_template' => false,
            'is_active' => true,
            'uuid' => Str::uuid(),
        ];
    }

    public function template(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_template' => true,
            'is_active' => false,
        ]);
    }
}
