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

        $bestRuns = $player->runs()
            ->finished()
            ->orderByDesc('score')
            ->orderBy('duration_ms')
            ->get()
            ->unique('game_slug')
            ->keyBy('game_slug');

        $games = collect($registry->all())
            ->map(function (PartyGame $game) use ($bestRuns) {
                /** @var GameRun|null $best */
                $best = $bestRuns->get($game->slug());

                return [
                    'slug' => $game->slug(),
                    'title' => $game->title(),
                    'description' => $game->description(),
                    'max_score' => $game->maxScore(),
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
