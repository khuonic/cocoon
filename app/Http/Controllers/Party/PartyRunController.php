<?php

namespace App\Http\Controllers\Party;

use App\Http\Controllers\Controller;
use App\Http\Requests\Party\AnswerStepRequest;
use App\Models\Party\GameRun;
use App\Models\Party\Player;
use App\Party\Contracts\PartyGame;
use App\Party\Contracts\QuizGame;
use App\Party\GameRegistry;
use App\Party\Games\CasseBrique;
use App\Party\Games\Memory;
use App\Party\PartyEvent;
use App\Services\Party\CasseBriqueRunService;
use App\Services\Party\GameRunService;
use App\Services\Party\Leaderboard;
use App\Services\Party\MemoryRunService;
use App\Services\Party\QuizRunService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartyRunController extends Controller
{
    public function store(Request $request, PartyGame $game, GameRunService $runs, MemoryRunService $memory): JsonResponse
    {
        $this->ensureOpen();
        $player = $this->player($request);
        abort_unless($player->canPlay($game), 403, 'Termine d’abord La voie de la sagesse.');

        if ($game instanceof Memory) {
            $run = $memory->start($player, $game, $request->input('variant'));

            return response()->json(['run' => $run->uuid, 'deck' => $run->data['deck']], 201);
        }

        return response()->json(['run' => $runs->start($player, $game)->uuid], 201);
    }

    public function answer(AnswerStepRequest $request, GameRun $run, GameRegistry $registry, QuizRunService $runs): JsonResponse
    {
        $this->authorizeRun($request, $run);
        $this->ensureOpen();

        $game = $registry->findOrFail($run->game_slug);
        abort_unless($game instanceof QuizGame, 404);

        return response()->json(
            $runs->answer($run, $game, $request->validated('step'), $request->validated('choice'))
        );
    }

    /**
     * Fin de partie des jeux joués côté navigateur (memory, casse-brique).
     */
    public function finish(
        Request $request,
        GameRun $run,
        GameRegistry $registry,
        MemoryRunService $memory,
        CasseBriqueRunService $casseBrique,
    ): JsonResponse {
        $this->authorizeRun($request, $run);
        $this->ensureOpen();

        $game = $registry->findOrFail($run->game_slug);

        match (true) {
            $game instanceof Memory => $memory->finish($run, $request->validate([
                'flips' => ['required', 'array', 'max:2000'],
                'flips.*' => ['integer', 'min:0'],
            ])['flips']),
            $game instanceof CasseBrique => $casseBrique->finish($run, $game, ...$this->validateCasseBrique($request)),
            default => abort(404),
        };

        return response()->json(['finished' => true]);
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
            'game' => [
                'slug' => $game->slug(),
                'title' => $game->title(),
                'ranking' => $game->ranking()->value,
            ],
            'run' => [
                'uuid' => $run->uuid,
                'score' => $run->score,
                'max_score' => $run->max_score,
                'duration_ms' => $run->duration_ms,
                'moves' => $run->moves,
                'variant' => $run->variant,
                'variant_label' => $game->variantLabel($run->variant),
            ],
            'rank' => $leaderboard->rankOf($run, $game),
            'totalPlayers' => $leaderboard->forGame($game, $run->variant)->count(),
            'verdict' => $game->verdictFor($run),
            'recap' => $recap,
        ]);
    }

    /**
     * @return array{score: int, level: int, won: bool}
     */
    private function validateCasseBrique(Request $request): array
    {
        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:0'],
            'level' => ['required', 'integer', 'min:1', 'max:'.CasseBrique::LEVELS],
            'won' => ['required', 'boolean'],
        ]);

        return [
            'score' => (int) $validated['score'],
            'level' => (int) $validated['level'],
            'won' => (bool) $validated['won'],
        ];
    }

    private function ensureOpen(): void
    {
        abort_if(PartyEvent::isClosed(), 403, 'La fête est finie, les jeux sont fermés.');
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
