<?php

namespace App\Http\Controllers\Party;

use App\Http\Controllers\Controller;
use App\Models\Party\GameRun;
use App\Models\Party\Player;
use App\Party\Contracts\PartyGame;
use App\Party\GameRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartyHomeController extends Controller
{
    public function __invoke(Request $request, GameRegistry $registry): Response
    {
        /** @var Player $player */
        $player = $request->attributes->get(Player::class);

        $games = collect($registry->all())
            ->map(function (PartyGame $game) use ($player, $registry) {
                /** @var GameRun|null $best */
                $best = $game->variantAxes() === []
                    ? $player->runs()->finished()->where('game_slug', $game->slug())->rankedBy($game->ranking())->first()
                    : null;

                return [
                    'slug' => $game->slug(),
                    'title' => $game->title(),
                    'description' => $game->description(),
                    'ranking' => $game->ranking()->value,
                    'max_score' => $game->maxScore(),
                    'played' => $player->hasFinished($game->slug()),
                    'locked' => ! $player->canPlay($game),
                    'required_title' => $game->requiredGameSlug() !== null
                        ? $registry->find($game->requiredGameSlug())?->title()
                        : null,
                    'best' => $best ? [
                        'score' => $best->score,
                        'duration_ms' => $best->duration_ms,
                    ] : null,
                ];
            })
            ->values();

        return Inertia::render('Party/Home', [
            'games' => $games,
        ]);
    }
}
