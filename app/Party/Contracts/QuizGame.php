<?php

namespace App\Party\Contracts;

/**
 * Jeu à étapes : une question par étape, une seule bonne réponse.
 */
interface QuizGame extends PartyGame
{
    /**
     * @return list<array{key: string, chapter: string, context: string, scene: string, question: string, choices: array<string, string>, answer: string, reveal: string}>
     */
    public function steps(): array;
}
