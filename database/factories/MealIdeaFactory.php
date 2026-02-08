<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MealIdea>
 */
class MealIdeaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'tags' => fake()->randomElements(['rapide', 'vege', 'comfort', 'leger', 'gourmand'], 2),
            'created_by' => User::factory(),
            'uuid' => Str::uuid(),
        ];
    }
}
