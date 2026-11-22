<?php

namespace App\Http\Controllers\Party;

use App\Http\Controllers\Controller;
use App\Models\Party\Player;
use App\Party\Contracts\PartyGame;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartyGameController extends Controller
{
    /**
     * Page de jeu. Chaque jeu fournit ses props (jamais les bonnes réponses).
     */
    public function show(Request $request, PartyGame $game): Response|RedirectResponse
    {
        /** @var Player $player */
        $player = $request->attributes->get(Player::class);

        if (! $player->canPlay($game)) {
            return to_route('party.home');
        }

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
