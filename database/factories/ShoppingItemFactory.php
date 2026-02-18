<?php

namespace Database\Factories;

use App\Enums\ShoppingItemCategory;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShoppingItem>
 */
class ShoppingItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shopping_list_id' => ShoppingList::factory(),
            'name' => fake()->word(),
            'category' => fake()->randomElement(ShoppingItemCategory::cases()),
            'is_checked' => false,
            'added_by' => User::factory(),
            'uuid' => Str::uuid(),
        ];
    }

    public function checked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_checked' => true,
        ]);
    }
}
