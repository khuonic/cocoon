<?php

namespace Database\Factories;

use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'url' => fake()->optional()->url(),
            'prep_time' => fake()->optional()->numberBetween(5, 60),
            'cook_time' => fake()->optional()->numberBetween(10, 120),
            'servings' => fake()->optional()->numberBetween(1, 8),
            'tags' => fake()->randomElements(['rapide', 'vege', 'comfort', 'leger', 'gourmand'], 2),
            'created_by' => User::factory(),
            'uuid' => Str::uuid(),
        ];
    }

    public function withDetails(): static
    {
        return $this->afterCreating(function ($recipe) {
            foreach (range(0, 2) as $i) {
                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'name' => fake()->word(),
                    'quantity' => fake()->numberBetween(1, 500).'',
                    'unit' => fake()->randomElement(['g', 'ml', 'pièce', 'cs', 'cc']),
                    'sort_order' => $i,
                ]);
            }

            foreach (range(0, 2) as $i) {
                RecipeStep::create([
                    'recipe_id' => $recipe->id,
                    'instruction' => fake()->sentence(),
                    'sort_order' => $i,
                ]);
            }
        });
    }
}
