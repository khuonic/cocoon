<?php

namespace App\Http\Controllers\Party;

use App\Http\Controllers\Controller;
use App\Http\Requests\Party\AnswerStepRequest;
use App\Models\Party\GameRun;
use App\Models\Party\Player;
use App\Party\Contracts\PartyGame;
use App\Party\Contracts\QuizGame;
use App\Party\GameRegistry;
use App\Party\PartyEvent;
use App\Services\Party\Leaderboard;
use App\Services\Party\QuizRunService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartyRunController extends Controller
{
    public function store(Request $request, PartyGame $game, QuizRunService $runs): JsonResponse
    {
        abort_unless($game instanceof QuizGame, 404);
        abort_if(PartyEvent::isClosed(), 403, 'La fête est finie, les jeux sont fermés.');

        $run = $runs->start($this->player($request), $game);

        return response()->json(['run' => $run->uuid], 201);
    }

    public function answer(AnswerStepRequest $request, GameRun $run, GameRegistry $registry, QuizRunService $runs): JsonResponse
    {
        $this->authorizeRun($request, $run);
        abort_if(PartyEvent::isClosed(), 403, 'La fête est finie, les jeux sont fermés.');

        $game = $registry->findOrFail($run->game_slug);
        abort_unless($game instanceof QuizGame, 404);

        return response()->json(
            $runs->answer($run, $game, $request->validated('step'), $request->validated('choice'))
        );
    }

    public function show(Request $request, GameRun $run, GameRegistry $registry, Leaderboard $leaderboard): Response
    {
        $this->authorizeRun($request, $run);
        abort_unless($run->isFinished(), 404);

        $game = $registry->findOrFail($run->game_slug);

        $recap = $game instanceof QuizGame
            ? collect($game->steps())->map(fn (array $step) => [
                'key' => $step['key'],
                'context' => $step['context'],
                'answer' => $step['choices'][$step['answer']],
                'correct' => (bool) ($run->answers[$step['key']]['correct'] ?? false),
            ])->values()
            : [];

        return Inertia::render('Party/Result', [
            'game' => ['slug' => $game->slug(), 'title' => $game->title()],
            'run' => [
                'uuid' => $run->uuid,
                'score' => $run->score,
                'max_score' => $run->max_score,
                'duration_ms' => $run->duration_ms,
            ],
            'rank' => $leaderboard->rankOf($run),
            'totalPlayers' => $leaderboard->forGame($game->slug())->count(),
            'verdict' => $game->verdictFor($run->score),
            'recap' => $recap,
        ]);
    }

    private function player(Request $request): Player
    {
        return $request->attributes->get(Player::class);
    }

    private function authorizeRun(Request $request, GameRun $run): void
    {
        abort_unless($run->party_player_id === $this->player($request)->id, 403);
    }
}
