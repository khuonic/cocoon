<?php

namespace Database\Factories;

use App\Enums\BookmarkCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bookmark>
 */
class BookmarkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url' => fake()->url(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'category' => fake()->optional(0.5)->randomElement(BookmarkCategory::cases()),
            'is_favorite' => false,
            'added_by' => User::factory(),
            'uuid' => Str::uuid(),
        ];
    }

    public function favorite(): static
    {
        return $this->state(fn () => [
            'is_favorite' => true,
        ]);
    }
}
