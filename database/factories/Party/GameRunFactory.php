<?php

namespace Database\Factories\Party;

use App\Models\Party\GameRun;
use App\Models\Party\Player;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GameRun>
 */
class GameRunFactory extends Factory
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
            'party_player_id' => Player::factory(),
            'game_slug' => 'voie-de-la-sagesse',
            'answers' => [],
            'score' => 0,
            'max_score' => 6,
            'started_at' => now(),
            'finished_at' => null,
            'duration_ms' => null,
        ];
    }

    public function finished(int $score = 6, int $durationMs = 60_000): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $score,
            'finished_at' => now(),
            'duration_ms' => $durationMs,
        ]);
    }
}
