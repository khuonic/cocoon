<?php

use App\Models\Party\Player;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    enableParty();
    Storage::fake('local');
    config(['party.memory_photos_disk' => 'local']);
});

test('a guest with party access can load a memory photo', function () {
    Storage::disk('local')->put('party/memory/1.jpg', 'jpeg-bytes');
    $player = Player::factory()->create();

    asPartyGuest($player)
        ->get(route('party.memory.photo', 1))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');
});

test('memory photos are not reachable without party access', function () {
    Storage::disk('local')->put('party/memory/1.jpg', 'jpeg-bytes');

    $this->get(route('party.memory.photo', 1))->assertRedirect(route('party.enter'));
});

test('an unknown memory photo returns not found', function () {
    $player = Player::factory()->create();

    asPartyGuest($player)->get(route('party.memory.photo', 3))->assertNotFound();
});

test('the import command squares, resizes and numbers the photos', function () {
    $directory = storage_path('framework/testing/memory-import');
    File::ensureDirectoryExists($directory);
    $portrait = UploadedFile::fake()->image('b-portrait.jpg', 600, 900);
    $landscape = UploadedFile::fake()->image('a-paysage.png', 1200, 800);
    File::copy($portrait->getPathname(), "{$directory}/b-portrait.jpg");
    File::copy($landscape->getPathname(), "{$directory}/a-paysage.png");

    $this->artisan('party:memory-photos', ['directory' => $directory, '--size' => 200])->assertSuccessful();

    $first = getimagesizefromstring(Storage::disk('local')->get('party/memory/1.jpg'));
    expect(Storage::disk('local')->files('party/memory'))->toBe(['party/memory/1.jpg', 'party/memory/2.jpg'])
        ->and([$first[0], $first[1], $first['mime']])->toBe([200, 200, 'image/jpeg']);

    File::deleteDirectory($directory);
});
