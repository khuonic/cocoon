<?php

namespace App\Party\Games;

use App\Models\Party\GameRun;
use App\Models\Party\Player;
use App\Party\Ranking;

/**
 * Casse-brique aux objets d'Antoine. Le jeu tourne dans le navigateur :
 * le serveur chronomètre et rejette les scores impossibles.
 */
class CasseBrique extends BaseGame
{
    /** Nombre de niveaux (resources/js/components/party/breakout/levels.ts). */
    public const LEVELS = 6;

    /**
     * Au-delà, le score est jugé impossible pour la durée jouée.
     * Barème front : brique = 10 + 5 × (niveau - 1), ×2 en turbo, +100 × niveau par niveau terminé,
     * bonus attrapé +20 × niveau, caca −20 × niveau
     * (≈ 11 500 points de briques + ≈ 4 500 de bonus (59 cachés) au maximum, impossible en moins de 90 s environ).
     */
    public const MAX_POINTS_PER_SECOND = 150;

    public const MAX_SCORE = 20_000;

    public function slug(): string
    {
        return 'casse-brique';
    }

    public function title(): string
    {
        return 'Le Grand Débouchage';
    }

    public function description(): string
    {
        return 'Ventouse, falafels et pintes : casse tout, garde tes vies.';
    }

    public function component(): string
    {
        return 'Party/Games/CasseBrique';
    }

    public function ranking(): Ranking
    {
        return Ranking::Points;
    }

    public function requiredGameSlug(): string
    {
        return VoieDeLaSagesse::SLUG;
    }

    public function maxScore(?string $variant = null): int
    {
        return 0;
    }

    public function isPlausible(int $score, int $durationMs): bool
    {
        return $score <= self::MAX_SCORE
            && $score <= (int) ceil(max(1, $durationMs) / 1000 * self::MAX_POINTS_PER_SECOND);
    }

    public function pageProps(Player $player): array
    {
        $best = $player->runs()
            ->finished()
            ->where('game_slug', $this->slug())
            ->rankedBy($this->ranking())
            ->first();

        return [
            'best' => $best ? ['score' => $best->score, 'duration_ms' => $best->duration_ms] : null,
        ];
    }

    public function verdictFor(GameRun $run): string
    {
        $level = (int) ($run->data['level'] ?? 1);
        $won = (bool) ($run->data['won'] ?? false);

        return match (true) {
            $won => 'Tous les niveaux pulvérisés. Antoine t’offre une pinte.',
            $level >= 4 => "Niveau {$level} atteint, le Cap Caval est fier de toi.",
            $level >= 2 => "Niveau {$level}. La ventouse n'a pas suffi.",
            default => 'Premier niveau… la barre était peut-être trop petite ?',
        };
    }
}
