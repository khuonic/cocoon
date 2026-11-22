<?php

use App\Services\Party\SecretVoice;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    enableParty();
    Storage::fake('local');
    config(['party.memory_photos_disk' => 'local']);
});

test('any guest with party access can listen to the voice without a pseudo', function () {
    Storage::disk('local')->put(SecretVoice::PATH, 'm4a-bytes');

    asPartyGuest()
        ->get(route('party.secret.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Party/Secret')->where('available', true));

    asPartyGuest()
        ->get(route('party.secret.voice'))
        ->assertOk()
        ->assertHeader('Content-Type', 'audio/mp4');
});

test('a QR code with the key opens the secret page directly', function () {
    $this->get(route('party.secret.show', ['k' => 'cle-du-qr']))
        ->assertRedirect(route('party.secret.show'));
});

test('the page says the voice is coming while it has not been uploaded', function () {
    asPartyGuest()
        ->get(route('party.secret.show'))
        ->assertInertia(fn ($page) => $page->where('available', false));

    asPartyGuest()->get(route('party.secret.voice'))->assertNotFound();
});

test('the voice is not reachable without party access', function () {
    Storage::disk('local')->put(SecretVoice::PATH, 'm4a-bytes');

    $this->get(route('party.secret.show'))->assertRedirect(route('party.enter'));
    $this->get(route('party.secret.voice'))->assertRedirect(route('party.enter'));
});

test('the upload command stores an m4a or mp4 voice and rejects other formats', function (string $file) {
    $directory = storage_path('framework/testing/secret-voice');
    File::ensureDirectoryExists($directory);
    File::put("{$directory}/{$file}", 'mp4-bytes');
    File::put("{$directory}/vocal.mp3", 'mp3-bytes');

    $this->artisan('party:secret-voice', ['file' => "{$directory}/vocal.mp3"])->assertFailed();
    Storage::disk('local')->assertMissing(SecretVoice::PATH);

    $this->artisan('party:secret-voice', ['file' => "{$directory}/{$file}"])->assertSuccessful();
    expect(Storage::disk('local')->get(SecretVoice::PATH))->toBe('mp4-bytes');

    File::deleteDirectory($directory);
})->with(['vocal.m4a', 'vocal_festif.mp4']);
