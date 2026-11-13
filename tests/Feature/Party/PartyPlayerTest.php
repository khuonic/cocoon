<?php

use App\Models\Party\Player;
use App\Party\PartyEvent;

beforeEach(fn () => enableParty());

test('a guest creates a pseudo and receives a player cookie', function () {
    $response = asPartyGuest()->post(route('party.player.store'), ['pseudo' => '  Kev  ']);

    $player = Player::sole();
    expect($player->pseudo)->toBe('Kev');
    $response->assertRedirect(route('party.home'))
        ->assertCookie(PartyEvent::PLAYER_COOKIE, $player->uuid);
});

test('a pseudo already taken with another case is rejected', function () {
    Player::factory()->create(['pseudo' => 'Lola']);

    asPartyGuest()->post(route('party.player.store'), ['pseudo' => 'LOLA'])
        ->assertSessionHasErrors(['pseudo' => 'Ce pseudo est déjà pris.']);

    expect(Player::count())->toBe(1);
});

test('a pseudo with forbidden characters is rejected', function () {
    asPartyGuest()->post(route('party.player.store'), ['pseudo' => '<b>Kev</b>'])
        ->assertSessionHasErrors(['pseudo' => 'Lettres, chiffres, espaces, points et tirets uniquement.']);
});

test('a returning player renames themself without creating a new player', function () {
    $player = Player::factory()->create(['pseudo' => 'Kev']);

    asPartyGuest($player)->post(route('party.player.store'), ['pseudo' => 'kev'])
        ->assertRedirect(route('party.home'));

    expect(Player::count())->toBe(1)
        ->and($player->fresh()->pseudo)->toBe('kev');
});

test('the pseudo form requires party access', function () {
    $this->post(route('party.player.store'), ['pseudo' => 'Kev'])->assertRedirect(route('party.enter'));

    expect(Player::count())->toBe(0);
});
