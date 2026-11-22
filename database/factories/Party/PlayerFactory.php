<?php

namespace Database\Factories\Party;

use App\Models\Party\GameRun;
use App\Models\Party\Player;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'pseudo' => fake()->unique()->lexify('Invite ????'),
        ];
    }

    /**
     * Joueur ayant déjà terminé La voie de la sagesse (débloque les autres jeux).
     */
    public function sage(): static
    {
        return $this->has(GameRun::factory()->finished(), 'runs');
    }
}
