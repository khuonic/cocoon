<?php

namespace Database\Factories;

use App\Enums\MealType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MealPlan>
 */
class MealPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('now', '+1 week'),
            'meal_type' => fake()->randomElement(MealType::cases()),
            'description' => fake()->sentence(3),
            'cooked_by' => User::factory(),
            'uuid' => Str::uuid(),
        ];
    }
}
