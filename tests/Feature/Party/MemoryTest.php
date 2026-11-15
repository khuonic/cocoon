<?php

use App\Models\Party\GameRun;
use App\Models\Party\Player;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    enableParty();
    Storage::fake('local');
    config(['party.memory_photos_disk' => 'local']);
});

/**
 * Coups qui résolvent le paquet : d'abord des erreurs éventuelles, puis chaque paire.
 *
 * @param  list<int>  $deck
 * @return list<int>
 */
function solveDeck(array $deck, int $mistakes = 0): array
{
    $positionsByFace = collect($deck)->map(fn ($face, $position) => [$face, $position])->groupBy(0, true)->map(fn ($group) => $group->pluck(1)->values());
    $flips = [];

    $faces = $positionsByFace->keys()->values();
    for ($index = 0; $index < $mistakes; $index++) {
        $flips[] = $positionsByFace[$faces[0]][0];
        $flips[] = $positionsByFace[$faces[1]][0];
    }

    foreach ($positionsByFace as $positions) {
        $flips[] = $positions[0];
        $flips[] = $positions[1];
    }

    return $flips;
}

function startMemory(Player $player, string $variant = '16-pixel'): GameRun
{
    asPartyGuest($player)
        ->postJson(route('party.runs.store', 'memory'), ['variant' => $variant])
        ->assertCreated();

    return GameRun::query()->latest('id')->firstOrFail();
}

test('a memory run starts with a server shuffled deck of pairs', function () {
    $player = Player::factory()->create();

    $response = asPartyGuest($player)
        ->postJson(route('party.runs.store', 'memory'), ['variant' => '16-pixel'])
        ->assertCreated();

    $run = GameRun::sole();
    $deck = $response->json('deck');

    expect($deck)->toHaveCount(16)
        ->and(collect($deck)->countBy()->unique()->values()->all())->toBe([2])
        ->and($run->data['deck'])->toBe($deck)
        ->and($run->variant)->toBe('16-pixel')
        ->and($run->max_score)->toBe(8);
});

test('an unknown memory variant is rejected', function () {
    $player = Player::factory()->create();

    asPartyGuest($player)
        ->postJson(route('party.runs.store', 'memory'), ['variant' => '14-pixel'])
        ->assertJsonValidationErrors(['variant' => "Cette version du memory n'existe pas."]);

    expect(GameRun::count())->toBe(0);
});

test('photo memory needs enough photos for the chosen size', function () {
    $player = Player::factory()->create();
    foreach (range(1, 6) as $id) {
        Storage::disk('local')->put("party/memory/{$id}.jpg", 'jpeg');
    }

    asPartyGuest($player)
        ->postJson(route('party.runs.store', 'memory'), ['variant' => '16-photos'])
        ->assertJsonValidationErrors(['variant' => 'Pas encore assez de photos pour cette taille.']);

    $response = asPartyGuest($player)
        ->postJson(route('party.runs.store', 'memory'), ['variant' => '12-photos'])
        ->assertCreated();

    expect(collect($response->json('deck'))->unique()->sort()->values()->all())->toBe([1, 2, 3, 4, 5, 6]);
});

test('a solved memory is finished with server duration and move count', function () {
    $player = Player::factory()->create();
    $run = startMemory($player);
    $this->travel(40)->seconds();

    asPartyGuest($player)
        ->postJson(route('party.runs.finish', $run), ['flips' => solveDeck($run->data['deck'], mistakes: 2)])
        ->assertOk();

    $run->refresh();
    expect($run->isFinished())->toBeTrue()
        ->and($run->moves)->toBe(10)
        ->and($run->score)->toBe(8)
        ->and($run->duration_ms)->toBeGreaterThanOrEqual(40_000);
});

test('flips that do not solve the grid are rejected', function () {
    $player = Player::factory()->create();
    $run = startMemory($player);
    $this->travel(40)->seconds();

    $unfinished = array_slice(solveDeck($run->data['deck']), 0, 14);

    asPartyGuest($player)
        ->postJson(route('party.runs.finish', $run), ['flips' => $unfinished])
        ->assertJsonValidationErrors(['flips' => 'Ces coups ne résolvent pas la grille.']);

    expect($run->fresh()->isFinished())->toBeFalse();
});

test('a memory solved faster than humanly possible is rejected', function () {
    $player = Player::factory()->create();
    $run = startMemory($player);

    asPartyGuest($player)
        ->postJson(route('party.runs.finish', $run), ['flips' => solveDeck($run->data['deck'])])
        ->assertJsonValidationErrors(['flips' => 'Partie trop rapide pour être honnête.']);
});

test('a player cannot finish another player memory', function () {
    $run = startMemory(Player::factory()->create());
    $this->travel(40)->seconds();

    asPartyGuest(Player::factory()->create())
        ->postJson(route('party.runs.finish', $run), ['flips' => solveDeck($run->data['deck'])])
        ->assertForbidden();
});

test('memory leaderboards are split by variant and ranked by speed then moves', function () {
    $viewer = Player::factory()->create();
    $fast = Player::factory()->create(['pseudo' => 'Rapide']);
    $careful = Player::factory()->create(['pseudo' => 'Soigneux']);
    $sloppy = Player::factory()->create(['pseudo' => 'Brouillon']);

    $memoryRun = fn (Player $player, string $variant, int $durationMs, int $moves) => GameRun::factory()->for($player)->create([
        'game_slug' => 'memory',
        'variant' => $variant,
        'score' => 8,
        'max_score' => 8,
        'moves' => $moves,
        'finished_at' => now(),
        'duration_ms' => $durationMs,
    ]);

    $memoryRun($sloppy, '16-pixel', 50_000, 20);
    $memoryRun($careful, '16-pixel', 50_000, 9);
    $memoryRun($fast, '16-pixel', 30_000, 18);
    $memoryRun($fast, '12-pixel', 10_000, 6);

    asPartyGuest($viewer)
        ->get(route('party.leaderboard', ['game' => 'memory', 'variant' => '16-pixel']))
        ->assertInertia(fn ($page) => $page
            ->where('selectedVariant', '16-pixel')
            ->has('entries', 3)
            ->where('entries.0.pseudo', 'Rapide')
            ->where('entries.1.pseudo', 'Soigneux')
            ->where('entries.2.pseudo', 'Brouillon')
        );
});

test('the memory page shows the player records per variant and the photo count', function () {
    $player = Player::factory()->create();
    Storage::disk('local')->put('party/memory/1.jpg', 'jpeg');
    GameRun::factory()->for($player)->create([
        'game_slug' => 'memory',
        'variant' => '12-pixel',
        'moves' => 7,
        'finished_at' => now(),
        'duration_ms' => 21_000,
    ]);

    asPartyGuest($player)
        ->get(route('party.games.show', 'memory'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Party/Games/Memory')
            ->where('game.default_variant', '16-pixel')
            ->where('records.12-pixel.duration_ms', 21_000)
            ->where('records.12-pixel.moves', 7)
            ->where('photoCount', 1)
        );
});

test('the memory stays playable when the photo disk is misconfigured', function () {
    config(['party.memory_photos_disk' => 'disque-inexistant']);
    $player = Player::factory()->create();

    asPartyGuest($player)
        ->get(route('party.games.show', 'memory'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('photoCount', 0));

    asPartyGuest($player)
        ->postJson(route('party.runs.store', 'memory'), ['variant' => '12-pixel'])
        ->assertCreated();
});
