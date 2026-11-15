<?php

namespace App\Services\Party;

use App\Models\Party\GameRun;
use App\Party\Games\CasseBrique;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fin de partie du casse-brique : score envoyé par le navigateur, durée et plausibilité vérifiées ici.
 */
class CasseBriqueRunService
{
    public function __construct(private GameRunService $runs) {}

    /**
     * @throws ValidationException
     */
    public function finish(GameRun $run, CasseBrique $game, int $score, int $level, bool $won): GameRun
    {
        return DB::transaction(function () use ($run, $game, $score, $level, $won) {
            $run = GameRun::query()->lockForUpdate()->findOrFail($run->id);
            $this->runs->ensureNotFinished($run);

            if (! $game->isPlausible($score, $this->runs->elapsedMs($run))) {
                throw ValidationException::withMessages(['score' => 'Ce score est trop beau pour être vrai.']);
            }

            return $this->runs->finish($run, score: $score, data: ['level' => $level, 'won' => $won]);
        });
    }
}
