<?php

namespace App\Http\Controllers\Party;

use App\Http\Controllers\Controller;
use App\Models\Party\Player;
use App\Party\Contracts\PartyGame;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartyGameController extends Controller
{
    /**
     * Page de jeu. Chaque jeu fournit ses props (jamais les bonnes réponses).
     */
    public function show(Request $request, PartyGame $game): Response
    {
        /** @var Player $player */
        $player = $request->attributes->get(Player::class);

        return Inertia::render($game->component(), [
            'game' => [
                'slug' => $game->slug(),
                'title' => $game->title(),
                'description' => $game->description(),
                'ranking' => $game->ranking()->value,
                'max_score' => $game->maxScore(),
                'variant_axes' => $game->variantAxes(),
                'default_variant' => $game->defaultVariant(),
            ],
            ...$game->pageProps($player),
        ]);
    }
}
