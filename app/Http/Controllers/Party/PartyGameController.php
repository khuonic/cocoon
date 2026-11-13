<?php

namespace App\Http\Controllers\Party;

use App\Http\Controllers\Controller;
use App\Party\Contracts\PartyGame;
use App\Party\Contracts\QuizGame;
use Inertia\Inertia;
use Inertia\Response;

class PartyGameController extends Controller
{
    /**
     * Page de jeu. Les bonnes réponses et révélations ne sont jamais envoyées ici.
     */
    public function show(PartyGame $game): Response
    {
        $steps = $game instanceof QuizGame
            ? collect($game->steps())
                ->map(fn (array $step) => collect($step)->except(['answer', 'reveal'])->all())
                ->values()
            : [];

        return Inertia::render($game->component(), [
            'game' => [
                'slug' => $game->slug(),
                'title' => $game->title(),
                'description' => $game->description(),
                'max_score' => $game->maxScore(),
            ],
            'steps' => $steps,
        ]);
    }
}
