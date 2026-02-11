<?php

use App\Enums\NoteColor;
use App\Models\Note;
use App\Models\User;

test('guests are redirected to login', function () {
    $this->get(route('notes.index'))->assertRedirect('/login');
});

test('authenticated users can view the notes index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('notes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notes/Index')
            ->has('notes')
        );
});

test('pinned notes appear first', function () {
    $user = User::factory()->create();
    Note::factory()->create(['title' => 'Note normale', 'is_pinned' => false, 'created_by' => $user->id]);
    Note::factory()->pinned()->create(['title' => 'Note épinglée', 'created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('notes.index'))
        ->assertInertia(fn ($page) => $page
            ->has('notes', 2)
            ->where('notes.0.title', 'Note épinglée')
            ->where('notes.1.title', 'Note normale')
        );
});

test('store creates a note with uuid and created_by', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), [
            'title' => 'Ma note',
            'content' => 'Contenu de la note',
            'is_pinned' => false,
            'color' => null,
        ])
        ->assertRedirect(route('notes.index'));

    $this->assertDatabaseHas('notes', [
        'title' => 'Ma note',
        'content' => 'Contenu de la note',
        'created_by' => $user->id,
    ]);

    $note = Note::query()->where('title', 'Ma note')->first();
    expect($note->uuid)->not->toBeNull();
});

test('store validates title is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), [
            'title' => '',
            'content' => 'Un contenu',
            'is_pinned' => false,
        ])
        ->assertSessionHasErrors(['title']);
});

test('store validates content is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), [
            'title' => 'Un titre',
            'content' => '',
            'is_pinned' => false,
        ])
        ->assertSessionHasErrors(['content']);
});

test('store validates color must be a valid enum value', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), [
            'title' => 'Un titre',
            'content' => 'Un contenu',
            'is_pinned' => false,
            'color' => 'invalid-color',
        ])
        ->assertSessionHasErrors(['color']);
});

test('store creates a note with a color', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), [
            'title' => 'Note colorée',
            'content' => 'Un contenu',
            'is_pinned' => false,
            'color' => 'yellow',
        ])
        ->assertRedirect(route('notes.index'));

    $note = Note::query()->where('title', 'Note colorée')->first();
    expect($note->color)->toBe(NoteColor::Yellow);
});

test('store creates a note without color', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), [
            'title' => 'Note sans couleur',
            'content' => 'Un contenu',
            'is_pinned' => false,
            'color' => null,
        ])
        ->assertRedirect(route('notes.index'));

    $note = Note::query()->where('title', 'Note sans couleur')->first();
    expect($note->color)->toBeNull();
});

test('update modifies a note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['title' => 'Ancien titre', 'created_by' => $user->id]);

    $this->actingAs($user)
        ->put(route('notes.update', $note), [
            'title' => 'Nouveau titre',
            'content' => 'Nouveau contenu',
            'is_pinned' => true,
            'color' => 'blue',
        ])
        ->assertRedirect(route('notes.index'));

    $note->refresh();
    expect($note->title)->toBe('Nouveau titre');
    expect($note->content)->toBe('Nouveau contenu');
    expect($note->is_pinned)->toBeTrue();
    expect($note->color)->toBe(NoteColor::Blue);
});

test('toggle pin inverts is_pinned', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['is_pinned' => false, 'created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('notes.toggle-pin', $note))
        ->assertRedirect(route('notes.index'));

    expect($note->fresh()->is_pinned)->toBeTrue();
});

test('destroy deletes a note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->delete(route('notes.destroy', $note))
        ->assertRedirect(route('notes.index'));

    $this->assertDatabaseMissing('notes', ['id' => $note->id]);
});
