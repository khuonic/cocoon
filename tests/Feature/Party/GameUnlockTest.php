<?php

use App\Models\Party\GameRun;
use App\Models\Party\Player;

beforeEach(fn () => enableParty());

test('memory and breakout are locked until the wisdom path is finished', function (string $slug) {
    $player = Player::factory()->create();

    asPartyGuest($player)
        ->get(route('party.games.show', $slug))
        ->assertRedirect(route('party.home'));

    asPartyGuest($player)
        ->postJson(route('party.runs.store', $slug), ['variant' => '16-pixel'])
        ->assertForbidden()
        ->assertJson(['message' => 'Termine d’abord La voie de la sagesse.']);

    expect(GameRun::count())->toBe(0);
})->with(['memory', 'casse-brique']);

test('an unfinished wisdom path does not unlock the other games', function () {
    $player = Player::factory()->create();
    GameRun::factory()->for($player)->create();

    asPartyGuest($player)
        ->get(route('party.games.show', 'casse-brique'))
        ->assertRedirect(route('party.home'));
});

test('finishing the wisdom path once unlocks the other games', function (string $slug) {
    $player = Player::factory()->sage()->create();

    asPartyGuest($player)->get(route('party.games.show', $slug))->assertOk();
    asPartyGuest($player)
        ->postJson(route('party.runs.store', $slug), ['variant' => '16-pixel'])
        ->assertCreated();
})->with(['memory', 'casse-brique']);

test('the home page tells which games are locked', function () {
    asPartyGuest(Player::factory()->create())
        ->get(route('party.home'))
        ->assertInertia(fn ($page) => $page
            ->where('games.0.slug', 'voie-de-la-sagesse')
            ->where('games.0.locked', false)
            ->where('games.1.locked', true)
            ->where('games.1.required_title', 'La voie de la sagesse')
            ->where('games.2.locked', true)
        );
});
