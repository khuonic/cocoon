<?php

namespace Database\Factories;

use App\Enums\EventCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'title' => fake()->sentence(3),
            'description' => null,
            'location' => null,
            'category' => fake()->randomElement(EventCategory::cases())->value,
            'starts_at' => fake()->dateTimeBetween('now', '+1 month'),
            'ends_at' => null,
            'all_day' => false,
            'is_personal' => false,
            'user_id' => User::factory(),
            'reminder_before' => null,
        ];
    }
}
