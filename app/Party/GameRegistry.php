<?php

namespace App\Party;

use App\Party\Contracts\PartyGame;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Liste des mini-jeux déclarés dans config('party.games').
 */
class GameRegistry
{
    /** @var array<string, PartyGame> */
    private array $games = [];

    /**
     * @param  list<class-string<PartyGame>>  $gameClasses
     */
    public function __construct(array $gameClasses)
    {
        foreach ($gameClasses as $gameClass) {
            $game = app($gameClass);
            $this->games[$game->slug()] = $game;
        }
    }

    /**
     * @return array<string, PartyGame>
     */
    public function all(): array
    {
        return $this->games;
    }

    public function find(string $slug): ?PartyGame
    {
        return $this->games[$slug] ?? null;
    }

    public function findOrFail(string $slug): PartyGame
    {
        return $this->find($slug) ?? throw new NotFoundHttpException;
    }
}
