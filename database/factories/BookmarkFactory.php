<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bookmark>
 */
class BookmarkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url' => fake()->url(),
            'title' => fake()->sentence(4),
            'price' => fake()->optional(0.8)->randomFloat(2, 5, 2000),
            'image_url' => fake()->optional()->imageUrl(),
            'notes' => fake()->optional()->sentence(),
            'added_by' => User::factory(),
            'uuid' => Str::uuid(),
        ];
    }
}
