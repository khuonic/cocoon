<?php

namespace App\Http\Controllers\Party;

use App\Http\Controllers\Controller;
use App\Models\Party\Player;
use App\Party\Contracts\PartyGame;
use App\Party\GameRegistry;
use App\Services\Party\Leaderboard;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartyLeaderboardController extends Controller
{
    public function __invoke(Request $request, GameRegistry $registry, Leaderboard $leaderboard): Response
    {
        /** @var Player $player */
        $player = $request->attributes->get(Player::class);

        $games = $registry->all();
        $selected = $registry->find((string) $request->query('game')) ?? reset($games);

        $variant = $request->query('variant');
        if ($selected && ! $selected->isValidVariant(is_string($variant) ? $variant : null)) {
            $variant = $selected->defaultVariant();
        }

        return Inertia::render('Party/Leaderboard', [
            'games' => collect($games)
                ->map(fn (PartyGame $game) => ['slug' => $game->slug(), 'title' => $game->title()])
                ->values(),
            'selectedGame' => $selected ? [
                'slug' => $selected->slug(),
                'ranking' => $selected->ranking()->value,
                'variant_axes' => $selected->variantAxes(),
            ] : null,
            'selectedVariant' => $variant,
            'entries' => $selected ? $leaderboard->forGame($selected, $variant) : [],
            'currentPlayerId' => $player->id,
        ]);
    }
}
