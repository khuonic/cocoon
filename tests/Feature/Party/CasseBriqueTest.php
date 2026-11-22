<?php

use App\Models\Party\GameRun;
use App\Models\Party\Player;

beforeEach(fn () => enableParty());

function startCasseBrique(Player $player): GameRun
{
    asPartyGuest($player)->postJson(route('party.runs.store', 'casse-brique'))->assertCreated();

    return GameRun::query()->latest('id')->firstOrFail();
}

test('a breakout score is saved with the level reached', function () {
    $player = Player::factory()->sage()->create();
    $run = startCasseBrique($player);
    $this->travel(60)->seconds();

    asPartyGuest($player)
        ->postJson(route('party.runs.finish', $run), ['score' => 1250, 'level' => 3, 'won' => false])
        ->assertOk();

    $run->refresh();
    expect($run->isFinished())->toBeTrue()
        ->and($run->score)->toBe(1250)
        ->and($run->variant)->toBeNull()
        ->and($run->data)->toBe(['level' => 3, 'won' => false])
        ->and($run->duration_ms)->toBeGreaterThanOrEqual(60_000);
});

test('an impossible breakout score for the time played is rejected', function () {
    $player = Player::factory()->sage()->create();
    $run = startCasseBrique($player);
    $this->travel(10)->seconds();

    asPartyGuest($player)
        ->postJson(route('party.runs.finish', $run), ['score' => 5000, 'level' => 2, 'won' => false])
        ->assertJsonValidationErrors(['score' => 'Ce score est trop beau pour être vrai.']);

    expect($run->fresh()->isFinished())->toBeFalse();
});

test('a breakout run cannot be finished twice', function () {
    $player = Player::factory()->sage()->create();
    $run = startCasseBrique($player);
    $this->travel(60)->seconds();

    asPartyGuest($player)->postJson(route('party.runs.finish', $run), ['score' => 100, 'level' => 1, 'won' => false]);

    asPartyGuest($player)
        ->postJson(route('party.runs.finish', $run), ['score' => 900, 'level' => 2, 'won' => false])
        ->assertJsonValidationErrors(['run' => 'Cette partie est déjà terminée.']);

    expect($run->fresh()->score)->toBe(100);
});

test('the breakout leaderboard ranks by points', function () {
    $viewer = Player::factory()->sage()->create();
    $low = Player::factory()->create(['pseudo' => 'Petit']);
    $high = Player::factory()->create(['pseudo' => 'Gros']);

    GameRun::factory()->for($low)->finished(score: 400, durationMs: 30_000)->create(['game_slug' => 'casse-brique', 'max_score' => 0]);
    GameRun::factory()->for($high)->finished(score: 2000, durationMs: 90_000)->create(['game_slug' => 'casse-brique', 'max_score' => 0]);

    asPartyGuest($viewer)
        ->get(route('party.leaderboard', ['game' => 'casse-brique']))
        ->assertInertia(fn ($page) => $page
            ->where('selectedGame.ranking', 'points')
            ->where('entries.0.pseudo', 'Gros')
            ->where('entries.1.pseudo', 'Petit')
        );
});

test('the breakout page shows the player best score', function () {
    $player = Player::factory()->sage()->create();
    GameRun::factory()->for($player)->finished(score: 800, durationMs: 70_000)->create(['game_slug' => 'casse-brique', 'max_score' => 0]);

    asPartyGuest($player)
        ->get(route('party.games.show', 'casse-brique'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Party/Games/CasseBrique')
            ->where('best.score', 800)
        );
});

test('a breakout score boosted by bonuses is accepted up to the ceiling', function () {
    $player = Player::factory()->sage()->create();
    $run = startCasseBrique($player);
    $this->travel(5)->minutes();

    asPartyGuest($player)
        ->postJson(route('party.runs.finish', $run), ['score' => 20_001, 'level' => 6, 'won' => true])
        ->assertJsonValidationErrors(['score' => 'Ce score est trop beau pour être vrai.']);

    asPartyGuest($player)
        ->postJson(route('party.runs.finish', $run), ['score' => 20_000, 'level' => 6, 'won' => true])
        ->assertOk();
});
