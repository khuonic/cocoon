<?php

namespace App\Services\Party;

use App\Models\Party\GameRun;
use App\Models\Party\Player;
use App\Party\Contracts\QuizGame;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Déroulement d'une partie de quiz : validation côté serveur, score et chrono serveur.
 */
class QuizRunService
{
    public function start(Player $player, QuizGame $game): GameRun
    {
        return $player->runs()->create([
            'uuid' => (string) Str::uuid(),
            'game_slug' => $game->slug(),
            'answers' => [],
            'score' => 0,
            'max_score' => $game->maxScore(),
            'started_at' => now(),
        ]);
    }

    /**
     * @return array{correct: bool, answer: string, reveal: string, finished: bool, score: int}
     *
     * @throws ValidationException
     */
    public function answer(GameRun $run, QuizGame $game, string $stepKey, string $choice): array
    {
        return DB::transaction(function () use ($run, $game, $stepKey, $choice) {
            $run = GameRun::query()->lockForUpdate()->findOrFail($run->id);

            if ($run->isFinished()) {
                throw ValidationException::withMessages(['step' => 'Cette partie est déjà terminée.']);
            }

            $steps = $game->steps();
            $answers = $run->answers;
            $expectedStep = $steps[count($answers)] ?? null;

            if ($expectedStep === null || $expectedStep['key'] !== $stepKey) {
                throw ValidationException::withMessages(['step' => "Ce n'est pas l'étape en cours."]);
            }

            if (! array_key_exists($choice, $expectedStep['choices'])) {
                throw ValidationException::withMessages(['choice' => "Ce choix n'existe pas."]);
            }

            $isCorrect = $choice === $expectedStep['answer'];
            $answers[$stepKey] = ['choice' => $choice, 'correct' => $isCorrect];

            $run->answers = $answers;
            $run->score += $isCorrect ? 1 : 0;

            $isFinished = count($answers) === count($steps);

            if ($isFinished) {
                $finishedAt = now();
                $run->finished_at = $finishedAt;
                $run->duration_ms = (int) round($run->started_at->diffInMilliseconds($finishedAt, true));
            }

            $run->save();

            return [
                'correct' => $isCorrect,
                'answer' => $expectedStep['answer'],
                'reveal' => $expectedStep['reveal'],
                'finished' => $isFinished,
                'score' => $run->score,
            ];
        });
    }
}
