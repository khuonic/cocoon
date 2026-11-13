<?php

use App\Models\Party\Player;
use App\Party\PartyEvent;

beforeEach(fn () => enableParty());

test('the party site does not exist when disabled', function () {
    config(['party.enabled' => false]);

    $this->get(route('party.enter'))->assertNotFound();
});

test('party pages are never indexed', function () {
    $this->get(route('party.enter'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('a valid QR key grants access and strips the key from the url', function () {
    $this->get(route('party.home', ['k' => 'cle-du-qr']))
        ->assertRedirect(route('party.home'))
        ->assertCookie(PartyEvent::ACCESS_COOKIE, PartyEvent::accessToken());
});

test('an invalid QR key sends the guest to the password screen', function () {
    $this->get(route('party.home', ['k' => 'mauvaise-cle']))
        ->assertRedirect(route('party.enter'))
        ->assertCookieMissing(PartyEvent::ACCESS_COOKIE);
});

test('a visitor without access is sent to the password screen', function () {
    $this->get(route('party.home'))->assertRedirect(route('party.enter'));
});

test('an access cookie from a previous key no longer grants access', function () {
    $oldToken = PartyEvent::accessToken();
    config(['party.access_key' => 'nouvelle-cle']);

    $this->withCookie(PartyEvent::ACCESS_COOKIE, $oldToken)
        ->get(route('party.home'))
        ->assertRedirect(route('party.enter'));
});

test('the right password grants access', function () {
    $this->post(route('party.enter.store'), ['password' => 'gateau'])
        ->assertRedirect(route('party.home'))
        ->assertCookie(PartyEvent::ACCESS_COOKIE, PartyEvent::accessToken());
});

test('a wrong password is rejected', function () {
    $this->post(route('party.enter.store'), ['password' => 'pas-ca'])
        ->assertSessionHasErrors(['password' => 'Mauvais mot de passe.'])
        ->assertCookieMissing(PartyEvent::ACCESS_COOKIE);
});

test('password attempts are throttled', function () {
    foreach (range(1, 10) as $attempt) {
        $this->post(route('party.enter.store'), ['password' => 'pas-ca']);
    }

    $this->post(route('party.enter.store'), ['password' => 'gateau'])->assertTooManyRequests();
});

test('a guest with access but no pseudo is asked to choose one', function () {
    asPartyGuest()->get(route('party.home'))->assertRedirect(route('party.player.edit'));
});

test('party access does not open the Cocoon app', function () {
    $player = Player::factory()->create();

    asPartyGuest($player)->get('/')->assertRedirect('/login');
});

test('party pages do not expose Cocoon shared data', function () {
    $player = Player::factory()->create(['pseudo' => 'Kev']);

    asPartyGuest($player)
        ->get(route('party.home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Party/Home')
            ->where('party.player.pseudo', 'Kev')
            ->missing('syncApiUrl')
            ->missing('auth')
        );
});
