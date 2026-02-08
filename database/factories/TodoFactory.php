<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Todo>
 */
class TodoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'is_personal' => false,
            'assigned_to' => null,
            'created_by' => User::factory(),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+2 weeks'),
            'recurrence_type' => null,
            'recurrence_day' => null,
            'is_done' => false,
            'completed_at' => null,
            'uuid' => Str::uuid(),
        ];
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_done' => true,
            'completed_at' => now(),
        ]);
    }

    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_personal' => true,
        ]);
    }
}
