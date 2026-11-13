<?php

namespace App\Services\Party;

use App\Models\Party\GameRun;
use Illuminate\Support\Collection;

/**
 * Classement d'un jeu : meilleure partie terminée de chaque joueur,
 * triée par score décroissant puis durée croissante.
 */
class Leaderboard
{
    /**
     * @return Collection<int, array{rank: int, player_id: int, pseudo: string, run_uuid: string, score: int, max_score: int, duration_ms: int}>
     */
    public function forGame(string $gameSlug): Collection
    {
        return $this->bestRuns($gameSlug)
            ->values()
            ->map(fn (GameRun $run, int $index) => [
                'rank' => $index + 1,
                'player_id' => $run->party_player_id,
                'pseudo' => $run->player->pseudo,
                'run_uuid' => $run->uuid,
                'score' => $run->score,
                'max_score' => $run->max_score,
                'duration_ms' => (int) $run->duration_ms,
            ]);
    }

    /**
     * Rang qu'obtiendrait cette partie face aux meilleures parties des autres joueurs.
     */
    public function rankOf(GameRun $run): int
    {
        $betterPlayers = $this->bestRuns($run->game_slug)
            ->reject(fn (GameRun $best) => $best->party_player_id === $run->party_player_id)
            ->filter(fn (GameRun $best) => $best->score > $run->score
                || ($best->score === $run->score && $best->duration_ms < $run->duration_ms))
            ->count();

        return $betterPlayers + 1;
    }

    /**
     * @return Collection<int, GameRun>
     */
    private function bestRuns(string $gameSlug): Collection
    {
        return GameRun::query()
            ->with('player')
            ->finished()
            ->where('game_slug', $gameSlug)
            ->orderByDesc('score')
            ->orderBy('duration_ms')
            ->orderBy('finished_at')
            ->get()
            ->unique('party_player_id');
    }
}
