<?php

namespace App\Party\Games;

use App\Models\Party\GameRun;
use App\Models\Party\Player;
use App\Party\Ranking;
use App\Services\Party\MemoryPhotos;

/**
 * Memory : retrouver toutes les paires le plus vite possible.
 * Variantes : taille (12/16/20 cartes) × style (pixel art / photos), un classement par variante.
 */
class Memory extends BaseGame
{
    /** Nombre d'images pixel art disponibles côté front (resources/js/components/party/memory-faces.ts). */
    public const PIXEL_FACES = 10;

    public function __construct(private MemoryPhotos $photos) {}

    public function slug(): string
    {
        return 'memory';
    }

    public function title(): string
    {
        return "Le memory d'Antoine";
    }

    public function description(): string
    {
        return 'Retrouve toutes les paires le plus vite possible.';
    }

    public function component(): string
    {
        return 'Party/Games/Memory';
    }

    public function ranking(): Ranking
    {
        return Ranking::Speed;
    }

    public function requiredGameSlug(): string
    {
        return VoieDeLaSagesse::SLUG;
    }

    public function variantAxes(): array
    {
        return [
            ['key' => 'size', 'label' => 'Cartes', 'options' => ['12' => '12 cartes', '16' => '16 cartes', '20' => '20 cartes']],
            ['key' => 'style', 'label' => 'Style', 'options' => ['pixel' => 'Pixel art', 'photos' => 'Photos']],
        ];
    }

    public function defaultVariant(): string
    {
        return '16-pixel';
    }

    public function pairsFor(string $variant): int
    {
        return intdiv((int) explode('-', $variant)[0], 2);
    }

    public function styleOf(string $variant): string
    {
        return explode('-', $variant)[1];
    }

    public function maxScore(?string $variant = null): int
    {
        return $this->pairsFor($variant ?? (string) $this->defaultVariant());
    }

    public function pageProps(Player $player): array
    {
        $records = $player->runs()
            ->finished()
            ->where('game_slug', $this->slug())
            ->rankedBy($this->ranking())
            ->get()
            ->unique('variant')
            ->mapWithKeys(fn (GameRun $run) => [$run->variant => [
                'duration_ms' => $run->duration_ms,
                'moves' => $run->moves,
            ]]);

        return [
            'records' => (object) $records->all(),
            'photoCount' => $this->photos->count(),
        ];
    }

    public function verdictFor(GameRun $run): string
    {
        $pairs = max(1, $run->max_score);
        $extraMoves = (int) $run->moves - $pairs;

        return match (true) {
            $extraMoves <= 0 => 'Sans une seule erreur. Mémoire d’éléphant, ou tricheur ?',
            $extraMoves <= intdiv($pairs, 2) => 'Très solide ! Tu ne dois pas boire tant que ça.',
            $extraMoves <= $pairs => 'Pas mal, quelques cartes ont joué à cache-cache.',
            default => 'Tu as retourné toutes les cartes… plusieurs fois. Une autre pinte ?',
        };
    }
}
