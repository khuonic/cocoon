<?php

use App\Models\Party\GameRun;
use App\Models\Party\Player;

beforeEach(fn () => enableParty());

test('the leaderboard keeps the best finished run of each player, ties broken by time', function () {
    $viewer = Player::factory()->create();
    $kev = Player::factory()->create(['pseudo' => 'Kev']);
    $lola = Player::factory()->create(['pseudo' => 'Lola']);
    $max = Player::factory()->create(['pseudo' => 'Max']);

    GameRun::factory()->for($kev)->finished(score: 4, durationMs: 50_000)->create();
    GameRun::factory()->for($kev)->finished(score: 6, durationMs: 90_000)->create();
    GameRun::factory()->for($lola)->finished(score: 6, durationMs: 70_000)->create();
    GameRun::factory()->for($max)->finished(score: 5, durationMs: 30_000)->create();
    GameRun::factory()->for($max)->create(['score' => 6]);

    asPartyGuest($viewer)
        ->get(route('party.leaderboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Party/Leaderboard')
            ->where('selectedGame', 'voie-de-la-sagesse')
            ->has('entries', 3)
            ->where('entries.0.pseudo', 'Lola')
            ->where('entries.1.pseudo', 'Kev')
            ->where('entries.1.score', 6)
            ->where('entries.2.pseudo', 'Max')
            ->where('entries.2.rank', 3)
        );
});

test('the home page shows the player best score per game', function () {
    $player = Player::factory()->create();
    GameRun::factory()->for($player)->finished(score: 3, durationMs: 40_000)->create();
    GameRun::factory()->for($player)->finished(score: 5, durationMs: 70_000)->create();

    asPartyGuest($player)
        ->get(route('party.home'))
        ->assertInertia(fn ($page) => $page
            ->where('games.0.slug', 'voie-de-la-sagesse')
            ->where('games.0.best.score', 5)
            ->where('games.0.best.duration_ms', 70_000)
        );
});
