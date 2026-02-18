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
            ->has('todoLists')
            ->has('tab')
        );
});

test('notes index passes the tab query parameter', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('notes.index', ['tab' => 'todos']))
        ->assertInertia(fn ($page) => $page
            ->where('tab', 'todos')
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

test('show renders the Notes/Show page', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['title' => 'Ma note', 'created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('notes.show', $note))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notes/Show')
            ->where('note.title', 'Ma note')
        );
});

test('store creates a note and redirects to show', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('notes.store'), [
            'title' => 'Ma note',
            'color' => null,
        ]);

    $note = Note::query()->where('title', 'Ma note')->first();
    $response->assertRedirect(route('notes.show', $note));

    $this->assertDatabaseHas('notes', [
        'title' => 'Ma note',
        'created_by' => $user->id,
    ]);

    expect($note->uuid)->not->toBeNull();
});

test('store validates title is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), ['title' => ''])
        ->assertSessionHasErrors(['title']);
});

test('store validates color must be a valid enum value', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), [
            'title' => 'Un titre',
            'color' => 'invalid-color',
        ])
        ->assertSessionHasErrors(['color']);
});

test('store creates a note with a color', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), [
            'title' => 'Note colorée',
            'color' => 'yellow',
        ]);

    $note = Note::query()->where('title', 'Note colorée')->first();
    expect($note->color)->toBe(NoteColor::Yellow);
});

test('update modifies a note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['title' => 'Ancien titre', 'created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('notes.update', $note), [
            'title' => 'Nouveau titre',
            'content' => 'Nouveau contenu',
        ])
        ->assertRedirect();

    $note->refresh();
    expect($note->title)->toBe('Nouveau titre');
    expect($note->content)->toBe('Nouveau contenu');
});

test('toggle pin inverts is_pinned', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['is_pinned' => false, 'created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('notes.toggle-pin', $note))
        ->assertRedirect();

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
