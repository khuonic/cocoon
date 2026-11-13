<?php

namespace App\Party\Contracts;

interface PartyGame
{
    public function slug(): string;

    public function title(): string;

    public function description(): string;

    /**
     * Composant Inertia de la page de jeu (ex: Party/Games/VoieDeLaSagesse).
     */
    public function component(): string;

    public function maxScore(): int;

    /**
     * Message de fin selon le score obtenu.
     */
    public function verdictFor(int $score): string;
}
