<?php

namespace App\Services\Party;

use App\Models\Party\GameRun;
use App\Models\Party\Player;
use App\Party\Games\Memory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Parties de memory : le serveur mélange le paquet, puis rejoue les coups reçus
 * pour vérifier que la grille est bien résolue avant d'enregistrer le temps.
 */
class MemoryRunService
{
    /** Temps minimum crédible pour retourner deux cartes. */
    public const MIN_MS_PER_MOVE = 300;

    public function __construct(
        private GameRunService $runs,
        private MemoryPhotos $photos,
    ) {}

    /**
     * @throws ValidationException
     */
    public function start(Player $player, Memory $game, ?string $variant): GameRun
    {
        if (! $game->isValidVariant($variant)) {
            throw ValidationException::withMessages(['variant' => "Cette version du memory n'existe pas."]);
        }

        $pairs = $game->pairsFor($variant);
        $style = $game->styleOf($variant);

        $faces = $style === 'photos'
            ? array_slice($this->photos->ids(), 0, $pairs)
            : range(0, $pairs - 1);

        if (count($faces) < $pairs) {
            throw ValidationException::withMessages(['variant' => 'Pas encore assez de photos pour cette taille.']);
        }

        return $this->runs->start($player, $game, $variant, [
            'deck' => Arr::shuffle([...$faces, ...$faces]),
        ]);
    }

    /**
     * @param  list<int>  $flips  positions des cartes retournées, dans l'ordre (deux par coup)
     *
     * @throws ValidationException
     */
    public function finish(GameRun $run, array $flips): GameRun
    {
        return DB::transaction(function () use ($run, $flips) {
            $run = GameRun::query()->lockForUpdate()->findOrFail($run->id);
            $this->runs->ensureNotFinished($run);

            $deck = $run->data['deck'] ?? [];
            $moves = $this->replay($deck, $flips);

            if ($this->runs->elapsedMs($run) < $moves * self::MIN_MS_PER_MOVE) {
                throw ValidationException::withMessages(['flips' => 'Partie trop rapide pour être honnête.']);
            }

            return $this->runs->finish($run, score: intdiv(count($deck), 2), moves: $moves);
        });
    }

    /**
     * Rejoue les coups et renvoie leur nombre si toutes les paires sont trouvées.
     *
     * @param  list<int>  $deck
     * @param  list<int>  $flips
     *
     * @throws ValidationException
     */
    private function replay(array $deck, array $flips): int
    {
        $invalid = fn () => ValidationException::withMessages(['flips' => 'Ces coups ne résolvent pas la grille.']);

        if ($deck === [] || count($flips) % 2 !== 0) {
            throw $invalid();
        }

        $matched = [];

        foreach (array_chunk($flips, 2) as [$first, $second]) {
            $isPlayable = fn (int $position) => array_key_exists($position, $deck) && ! isset($matched[$position]);

            if ($first === $second || ! $isPlayable($first) || ! $isPlayable($second)) {
                throw $invalid();
            }

            if ($deck[$first] === $deck[$second]) {
                $matched[$first] = true;
                $matched[$second] = true;
            }
        }

        if (count($matched) !== count($deck)) {
            throw $invalid();
        }

        return intdiv(count($flips), 2);
    }
}
