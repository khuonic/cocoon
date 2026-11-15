<?php

namespace App\Services\Party;

use App\Models\Party\GameRun;
use App\Models\Party\Player;
use App\Party\Contracts\PartyGame;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Démarrage et clôture génériques d'une partie (chrono serveur).
 */
class GameRunService
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    public function start(Player $player, PartyGame $game, ?string $variant = null, ?array $data = null): GameRun
    {
        return $player->runs()->create([
            'uuid' => (string) Str::uuid(),
            'game_slug' => $game->slug(),
            'variant' => $variant,
            'answers' => [],
            'data' => $data,
            'score' => 0,
            'max_score' => $game->maxScore($variant),
            'started_at' => now(),
        ]);
    }

    /**
     * Durée écoulée depuis le début de la partie, selon l'horloge du serveur.
     */
    public function elapsedMs(GameRun $run): int
    {
        return (int) round($run->started_at->diffInMilliseconds(now(), true));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function finish(GameRun $run, int $score, ?int $moves = null, array $data = []): GameRun
    {
        $run->forceFill([
            'score' => $score,
            'moves' => $moves,
            'data' => [...($run->data ?? []), ...$data],
            'finished_at' => now(),
            'duration_ms' => $this->elapsedMs($run),
        ])->save();

        return $run;
    }

    /**
     * @throws ValidationException
     */
    public function ensureNotFinished(GameRun $run): void
    {
        if ($run->isFinished()) {
            throw ValidationException::withMessages(['run' => 'Cette partie est déjà terminée.']);
        }
    }
}
