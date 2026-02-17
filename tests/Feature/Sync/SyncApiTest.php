<?php

use App\Models\Note;
use App\Models\SyncLog;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create(['email' => 'kevininc155@gmail.com']);
});

it('rejects unauthenticated requests to push', function () {
    $this->postJson('/api/sync/push', ['changes' => []])->assertUnauthorized();
});

it('rejects unauthenticated requests to pull', function () {
    $this->getJson('/api/sync/pull?since=2025-01-01T00:00:00Z')->assertUnauthorized();
});

it('rejects unauthenticated requests to full', function () {
    $this->postJson('/api/sync/full', ['changes' => []])->assertUnauthorized();
});

it('pushes a created note', function () {
    Sanctum::actingAs($this->user);

    $uuid = (string) Str::uuid();

    $response = $this->postJson('/api/sync/push', [
        'changes' => [
            [
                'type' => 'notes',
                'uuid' => $uuid,
                'action' => 'created',
                'data' => [
                    'title' => 'Test Note',
                    'content' => 'Some content',
                    'is_pinned' => false,
                    'created_by' => $this->user->id,
                    'uuid' => $uuid,
                ],
                'updated_at' => now()->toIso8601String(),
            ],
        ],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('applied', 1)
        ->assertJsonPath('rejected', 0)
        ->assertJsonStructure(['applied', 'rejected', 'server_time']);

    $this->assertDatabaseHas('notes', ['uuid' => $uuid, 'title' => 'Test Note']);
});

it('pushes an updated note', function () {
    Sanctum::actingAs($this->user);

    $note = Note::factory()->create(['created_by' => $this->user->id]);

    $response = $this->postJson('/api/sync/push', [
        'changes' => [
            [
                'type' => 'notes',
                'uuid' => $note->uuid,
                'action' => 'updated',
                'data' => [
                    'title' => 'Updated Title',
                    'content' => $note->content,
                    'is_pinned' => false,
                    'created_by' => $this->user->id,
                    'uuid' => $note->uuid,
                ],
                'updated_at' => now()->addSecond()->toIso8601String(),
            ],
        ],
    ]);

    $response->assertSuccessful()->assertJsonPath('applied', 1);

    $this->assertDatabaseHas('notes', ['uuid' => $note->uuid, 'title' => 'Updated Title']);
});

it('pushes a deleted note', function () {
    Sanctum::actingAs($this->user);

    $note = Note::factory()->create(['created_by' => $this->user->id]);

    $response = $this->postJson('/api/sync/push', [
        'changes' => [
            [
                'type' => 'notes',
                'uuid' => $note->uuid,
                'action' => 'deleted',
                'data' => null,
                'updated_at' => now()->toIso8601String(),
            ],
        ],
    ]);

    $response->assertSuccessful()->assertJsonPath('applied', 1);

    $this->assertDatabaseMissing('notes', ['uuid' => $note->uuid]);
});

it('rejects older update via last-write-wins', function () {
    Sanctum::actingAs($this->user);

    $note = Note::factory()->create([
        'created_by' => $this->user->id,
        'title' => 'Current Title',
    ]);

    // Touch the note so updated_at is now
    $note->touch();

    $response = $this->postJson('/api/sync/push', [
        'changes' => [
            [
                'type' => 'notes',
                'uuid' => $note->uuid,
                'action' => 'updated',
                'data' => [
                    'title' => 'Old Title',
                    'content' => $note->content,
                    'is_pinned' => false,
                    'created_by' => $this->user->id,
                    'uuid' => $note->uuid,
                ],
                'updated_at' => now()->subHour()->toIso8601String(),
            ],
        ],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('applied', 0)
        ->assertJsonPath('rejected', 1);

    $this->assertDatabaseHas('notes', ['uuid' => $note->uuid, 'title' => 'Current Title']);
});

it('rejects push with unknown type', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/sync/push', [
        'changes' => [
            [
                'type' => 'unknown_type',
                'uuid' => (string) Str::uuid(),
                'action' => 'created',
                'data' => ['name' => 'test'],
                'updated_at' => now()->toIso8601String(),
            ],
        ],
    ]);

    $response->assertSuccessful()->assertJsonPath('rejected', 1);
});

it('validates push payload', function () {
    Sanctum::actingAs($this->user);

    $this->postJson('/api/sync/push', ['changes' => [
        ['type' => '', 'uuid' => '', 'action' => 'invalid'],
    ]])->assertUnprocessable();
});

it('pulls changes since a timestamp', function () {
    Sanctum::actingAs($this->user);

    $since = now()->subMinute();

    $note = Note::factory()->create(['created_by' => $this->user->id]);

    $response = $this->getJson('/api/sync/pull?since=' . urlencode($since->toIso8601String()));

    $response->assertSuccessful()
        ->assertJsonStructure(['changes', 'server_time']);

    $changes = $response->json('changes');

    expect($changes)->toHaveCount(1);
    expect($changes[0]['type'])->toBe('notes');
    expect($changes[0]['uuid'])->toBe((string) $note->uuid);
});

it('pulls deleted records from sync log', function () {
    Sanctum::actingAs($this->user);

    $since = now()->subMinute();

    SyncLog::create([
        'syncable_type' => Note::class,
        'syncable_uuid' => (string) Str::uuid(),
        'action' => 'deleted',
        'payload' => null,
    ]);

    $response = $this->getJson('/api/sync/pull?since=' . urlencode($since->toIso8601String()));

    $deletedChanges = collect($response->json('changes'))->where('action', 'deleted');

    expect($deletedChanges)->toHaveCount(1);
});

it('performs full sync', function () {
    Sanctum::actingAs($this->user);

    Note::factory()->count(2)->create(['created_by' => $this->user->id]);
    Todo::factory()->create(['created_by' => $this->user->id]);

    $newUuid = (string) Str::uuid();

    $response = $this->postJson('/api/sync/full', [
        'changes' => [
            [
                'type' => 'notes',
                'uuid' => $newUuid,
                'action' => 'created',
                'data' => [
                    'title' => 'From client',
                    'content' => 'Content',
                    'is_pinned' => false,
                    'created_by' => $this->user->id,
                    'uuid' => $newUuid,
                ],
                'updated_at' => now()->toIso8601String(),
            ],
        ],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('applied', 1)
        ->assertJsonStructure(['applied', 'rejected', 'changes', 'server_time']);

    // 3 notes (2 existing + 1 pushed) + 1 todo = 4 changes in response
    $changes = $response->json('changes');
    expect($changes)->toHaveCount(4);

    $this->assertDatabaseHas('notes', ['uuid' => $newUuid]);
});

it('handles full sync with no client changes', function () {
    Sanctum::actingAs($this->user);

    Note::factory()->create(['created_by' => $this->user->id]);

    $response = $this->postJson('/api/sync/full', ['changes' => []]);

    $response->assertSuccessful()
        ->assertJsonPath('applied', 0)
        ->assertJsonPath('rejected', 0);

    expect($response->json('changes'))->toHaveCount(1);
});

it('pushes multiple changes in one request', function () {
    Sanctum::actingAs($this->user);

    $uuid1 = (string) Str::uuid();
    $uuid2 = (string) Str::uuid();

    $response = $this->postJson('/api/sync/push', [
        'changes' => [
            [
                'type' => 'notes',
                'uuid' => $uuid1,
                'action' => 'created',
                'data' => [
                    'title' => 'Note 1',
                    'content' => 'Content 1',
                    'is_pinned' => false,
                    'created_by' => $this->user->id,
                    'uuid' => $uuid1,
                ],
                'updated_at' => now()->toIso8601String(),
            ],
            [
                'type' => 'todos',
                'uuid' => $uuid2,
                'action' => 'created',
                'data' => [
                    'title' => 'Todo 1',
                    'is_personal' => false,
                    'is_done' => false,
                    'show_on_dashboard' => false,
                    'created_by' => $this->user->id,
                    'uuid' => $uuid2,
                ],
                'updated_at' => now()->toIso8601String(),
            ],
        ],
    ]);

    $response->assertSuccessful()->assertJsonPath('applied', 2);

    $this->assertDatabaseHas('notes', ['uuid' => $uuid1]);
    $this->assertDatabaseHas('todos', ['uuid' => $uuid2]);
});
