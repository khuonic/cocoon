<?php

namespace Database\Factories;

use App\Enums\SplitType;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 1, 500),
            'description' => fake()->sentence(3),
            'category_id' => ExpenseCategory::factory(),
            'paid_by' => User::factory(),
            'split_type' => fake()->randomElement(SplitType::cases()),
            'split_value' => null,
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
            'is_recurring' => false,
            'recurrence_type' => null,
            'settled_at' => null,
            'uuid' => Str::uuid(),
        ];
    }

    public function settled(): static
    {
        return $this->state(fn (array $attributes) => [
            'settled_at' => now(),
        ]);
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
            'recurrence_type' => fake()->randomElement(['weekly', 'monthly', 'yearly']),
        ]);
    }
}
