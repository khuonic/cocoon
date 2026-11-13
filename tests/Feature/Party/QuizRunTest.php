<?php

use App\Models\Party\GameRun;
use App\Models\Party\Player;
use App\Party\Games\VoieDeLaSagesse;

beforeEach(fn () => enableParty());

/**
 * Répond à toutes les étapes, en se trompant sur les clés listées.
 *
 * @param  list<string>  $wrongSteps
 */
function playWholeGame(Player $player, GameRun $run, array $wrongSteps = []): void
{
    foreach ((new VoieDeLaSagesse)->steps() as $step) {
        $choice = in_array($step['key'], $wrongSteps, true)
            ? collect($step['choices'])->keys()->first(fn ($key) => $key !== $step['answer'])
            : $step['answer'];

        asPartyGuest($player)
            ->postJson(route('party.runs.answer', $run), ['step' => $step['key'], 'choice' => $choice])
            ->assertOk();
    }
}

test('the game page never exposes answers or reveals', function () {
    $player = Player::factory()->create();

    asPartyGuest($player)
        ->get(route('party.games.show', 'voie-de-la-sagesse'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Party/Games/VoieDeLaSagesse')
            ->has('steps', 6)
            ->missing('steps.0.answer')
            ->missing('steps.0.reveal')
        );
});

test('an unknown game returns not found', function () {
    $player = Player::factory()->create();

    asPartyGuest($player)->get(route('party.games.show', 'jeu-inconnu'))->assertNotFound();
});

test('a player starts a run', function () {
    $player = Player::factory()->create();

    $response = asPartyGuest($player)->postJson(route('party.runs.store', 'voie-de-la-sagesse'))->assertCreated();

    $run = GameRun::sole();
    expect($response->json('run'))->toBe($run->uuid)
        ->and($run->party_player_id)->toBe($player->id)
        ->and($run->max_score)->toBe(6)
        ->and($run->finished_at)->toBeNull();
});

test('a correct answer scores a point and returns the reveal', function () {
    $player = Player::factory()->create();
    $run = GameRun::factory()->for($player)->create();

    asPartyGuest($player)
        ->postJson(route('party.runs.answer', $run), ['step' => 'chambre', 'choice' => 'a'])
        ->assertOk()
        ->assertJson([
            'correct' => true,
            'answer' => 'a',
            'reveal' => 'Derrière le lit, évidemment. La cachette idéale.',
            'finished' => false,
            'score' => 1,
        ]);
});

test('a wrong answer scores nothing but still reveals the right answer', function () {
    $player = Player::factory()->create();
    $run = GameRun::factory()->for($player)->create();

    asPartyGuest($player)
        ->postJson(route('party.runs.answer', $run), ['step' => 'chambre', 'choice' => 'c'])
        ->assertOk()
        ->assertJson(['correct' => false, 'answer' => 'a', 'score' => 0]);

    expect($run->fresh()->answers)->toBe(['chambre' => ['choice' => 'c', 'correct' => false]]);
});

test('a step cannot be answered twice', function () {
    $player = Player::factory()->create();
    $run = GameRun::factory()->for($player)->create();

    asPartyGuest($player)->postJson(route('party.runs.answer', $run), ['step' => 'chambre', 'choice' => 'a']);

    asPartyGuest($player)
        ->postJson(route('party.runs.answer', $run), ['step' => 'chambre', 'choice' => 'b'])
        ->assertJsonValidationErrors(['step' => "Ce n'est pas l'étape en cours."]);

    expect($run->fresh()->score)->toBe(1);
});

test('steps cannot be skipped', function () {
    $player = Player::factory()->create();
    $run = GameRun::factory()->for($player)->create();

    asPartyGuest($player)
        ->postJson(route('party.runs.answer', $run), ['step' => 'bsr', 'choice' => 'c'])
        ->assertJsonValidationErrors(['step' => "Ce n'est pas l'étape en cours."]);
});

test('an invalid choice is rejected', function () {
    $player = Player::factory()->create();
    $run = GameRun::factory()->for($player)->create();

    asPartyGuest($player)
        ->postJson(route('party.runs.answer', $run), ['step' => 'chambre', 'choice' => 'd'])
        ->assertJsonValidationErrors(['choice' => "Ce choix n'existe pas."]);
});

test('a player cannot answer in another player run', function () {
    $owner = Player::factory()->create();
    $intruder = Player::factory()->create();
    $run = GameRun::factory()->for($owner)->create();

    asPartyGuest($intruder)
        ->postJson(route('party.runs.answer', $run), ['step' => 'chambre', 'choice' => 'a'])
        ->assertForbidden();

    expect($run->fresh()->answers)->toBe([]);
});

test('the last answer finishes the run with score and server duration', function () {
    $player = Player::factory()->create();
    $run = GameRun::factory()->for($player)->create(['started_at' => now()->subSeconds(90)]);

    playWholeGame($player, $run, wrongSteps: ['bsr']);

    $run->refresh();
    expect($run->isFinished())->toBeTrue()
        ->and($run->score)->toBe(5)
        ->and($run->duration_ms)->toBeGreaterThanOrEqual(90_000);
});

test('a finished run shows the result with rank and verdict', function () {
    $player = Player::factory()->create();
    $run = GameRun::factory()->for($player)->finished(score: 6, durationMs: 80_000)->create();
    GameRun::factory()->finished(score: 6, durationMs: 60_000)->create();

    asPartyGuest($player)
        ->get(route('party.runs.show', $run))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Party/Result')
            ->where('run.score', 6)
            ->where('rank', 2)
            ->where('totalPlayers', 2)
            ->where('verdict', 'Sans faute. Tu connais Antoine mieux que sa propre mère.')
            ->has('recap', 6)
        );
});

test('another player result is forbidden', function () {
    $player = Player::factory()->create();
    $run = GameRun::factory()->finished()->create();

    asPartyGuest($player)->get(route('party.runs.show', $run))->assertForbidden();
});

test('games are read only once the party is closed', function () {
    enableParty(['party.closes_at' => now()->subMinute()->toDateTimeString()]);
    $player = Player::factory()->create();
    $run = GameRun::factory()->for($player)->create();

    asPartyGuest($player)->postJson(route('party.runs.store', 'voie-de-la-sagesse'))->assertForbidden();
    asPartyGuest($player)
        ->postJson(route('party.runs.answer', $run), ['step' => 'chambre', 'choice' => 'a'])
        ->assertForbidden();

    expect(GameRun::count())->toBe(1);
});
