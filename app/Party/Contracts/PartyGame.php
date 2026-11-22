<?php

namespace App\Party\Contracts;

use App\Models\Party\GameRun;
use App\Models\Party\Player;
use App\Party\Ranking;

interface PartyGame
{
    public function slug(): string;

    public function title(): string;

    public function description(): string;

    /**
     * Composant Inertia de la page de jeu (ex: Party/Games/VoieDeLaSagesse).
     */
    public function component(): string;

    public function ranking(): Ranking;

    /**
     * Jeu à terminer au moins une fois avant de pouvoir jouer à celui-ci (null si aucun).
     */
    public function requiredGameSlug(): ?string;

    /**
     * Score maximum d'une partie (0 si le score n'est pas borné).
     */
    public function maxScore(?string $variant = null): int;

    /**
     * Axes de variantes, chacun avec ses options. La variante d'une partie est la
     * combinaison des valeurs dans l'ordre des axes, séparées par un tiret (ex: "16-pixel").
     *
     * @return list<array{key: string, label: string, options: array<string, string>}>
     */
    public function variantAxes(): array;

    public function defaultVariant(): ?string;

    public function isValidVariant(?string $variant): bool;

    public function variantLabel(?string $variant): ?string;

    /**
     * Props spécifiques à la page de jeu.
     *
     * @return array<string, mixed>
     */
    public function pageProps(Player $player): array;

    /**
     * Message de fin selon la partie jouée.
     */
    public function verdictFor(GameRun $run): string;
}
