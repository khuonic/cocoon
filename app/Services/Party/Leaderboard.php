<?php

namespace App\Services\Party;

use App\Models\Party\GameRun;
use App\Party\Contracts\PartyGame;
use Illuminate\Support\Collection;

/**
 * Classement d'un jeu (et d'une variante) : meilleure partie terminée de chaque joueur,
 * triée selon le classement propre au jeu (points ou rapidité).
 */
class Leaderboard
{
    /**
     * @return Collection<int, array{rank: int, player_id: int, pseudo: string, run_uuid: string, score: int, max_score: int, duration_ms: int, moves: int|null}>
     */
    public function forGame(PartyGame $game, ?string $variant = null): Collection
    {
        return $this->bestRuns($game, $variant)
            ->values()
            ->map(fn (GameRun $run, int $index) => [
                'rank' => $index + 1,
                'player_id' => $run->party_player_id,
                'pseudo' => $run->player->pseudo,
                'run_uuid' => $run->uuid,
                'score' => $run->score,
                'max_score' => $run->max_score,
                'duration_ms' => (int) $run->duration_ms,
                'moves' => $run->moves,
            ]);
    }

    /**
     * Rang qu'obtiendrait cette partie face aux meilleures parties des autres joueurs.
     */
    public function rankOf(GameRun $run, PartyGame $game): int
    {
        $betterPlayers = $this->bestRuns($game, $run->variant)
            ->reject(fn (GameRun $best) => $best->party_player_id === $run->party_player_id)
            ->filter(fn (GameRun $best) => $best->beats($run, $game->ranking()))
            ->count();

        return $betterPlayers + 1;
    }

    /**
     * @return Collection<int, GameRun>
     */
    private function bestRuns(PartyGame $game, ?string $variant): Collection
    {
        return GameRun::query()
            ->with('player')
            ->finished()
            ->where('game_slug', $game->slug())
            ->when(
                $variant === null,
                fn ($query) => $query->whereNull('variant'),
                fn ($query) => $query->where('variant', $variant),
            )
            ->rankedBy($game->ranking())
            ->get()
            ->unique('party_player_id');
    }
}
