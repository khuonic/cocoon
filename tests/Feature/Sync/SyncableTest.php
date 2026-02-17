<?php

use App\Enums\SyncAction;
use App\Models\Note;
use App\Models\SyncLog;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['email' => 'kevininc155@gmail.com']);
});

it('creates a sync log when a model is created', function () {
    $note = Note::factory()->create(['created_by' => $this->user->id]);

    $log = SyncLog::where('syncable_uuid', $note->uuid)->first();

    expect($log)->not->toBeNull();
    expect($log->action)->toBe(SyncAction::Created);
    expect($log->syncable_type)->toBe(Note::class);
    expect($log->payload)->toBeArray();
    expect($log->payload['title'])->toBe($note->title);
});

it('creates a sync log when a model is updated', function () {
    $note = Note::factory()->create(['created_by' => $this->user->id]);

    // Clear the "created" log
    SyncLog::truncate();

    $note->update(['title' => 'Updated']);

    $log = SyncLog::where('syncable_uuid', $note->uuid)->first();

    expect($log)->not->toBeNull();
    expect($log->action)->toBe(SyncAction::Updated);
});

it('creates a sync log when a model is deleted', function () {
    $note = Note::factory()->create(['created_by' => $this->user->id]);

    SyncLog::truncate();

    $note->delete();

    $log = SyncLog::where('syncable_uuid', $note->uuid)->first();

    expect($log)->not->toBeNull();
    expect($log->action)->toBe(SyncAction::Deleted);
    expect($log->payload)->toBeNull();
});

it('does not create a sync log when isSyncing is true', function () {
    $note = Note::factory()->create(['created_by' => $this->user->id]);

    SyncLog::truncate();

    $note->isSyncing = true;
    $note->update(['title' => 'Synced Update']);

    expect(SyncLog::count())->toBe(0);
});

it('uses pending scope correctly', function () {
    Note::factory()->create(['created_by' => $this->user->id]);

    expect(SyncLog::pending()->count())->toBe(1);
    expect(SyncLog::synced()->count())->toBe(0);

    SyncLog::first()->update(['synced_at' => now()]);

    expect(SyncLog::pending()->count())->toBe(0);
    expect(SyncLog::synced()->count())->toBe(1);
});
