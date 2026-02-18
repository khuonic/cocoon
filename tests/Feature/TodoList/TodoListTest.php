<?php

use App\Models\TodoList;
use App\Models\User;

test('guests are redirected to login when accessing a todo list', function () {
    $list = TodoList::factory()->create();

    $this->get(route('todo-lists.show', $list))->assertRedirect('/login');
});

test('show renders the TodoLists/Show page with todos', function () {
    $user = User::factory()->create();
    $list = TodoList::factory()->create(['title' => 'Ma liste']);

    $this->actingAs($user)
        ->get(route('todo-lists.show', $list))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TodoLists/Show')
            ->has('todoList')
            ->where('todoList.title', 'Ma liste')
        );
});

test('store creates a todo list and redirects to its show page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('todo-lists.store'), [
            'title' => 'Liste de courses',
            'is_personal' => false,
        ]);

    $list = TodoList::query()->first();
    $response->assertRedirect(route('todo-lists.show', $list));

    $this->assertDatabaseHas('todo_lists', [
        'title' => 'Liste de courses',
        'is_personal' => false,
        'user_id' => null,
    ]);

    expect($list->uuid)->not->toBeNull();
});

test('store creates a personal todo list with user_id', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('todo-lists.store'), [
            'title' => 'Ma liste perso',
            'is_personal' => true,
        ]);

    $this->assertDatabaseHas('todo_lists', [
        'title' => 'Ma liste perso',
        'is_personal' => true,
        'user_id' => $user->id,
    ]);
});

test('store validates title is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('todo-lists.store'), ['title' => ''])
        ->assertSessionHasErrors(['title']);
});

test('update modifies the list title', function () {
    $user = User::factory()->create();
    $list = TodoList::factory()->create(['title' => 'Ancien titre']);

    $this->actingAs($user)
        ->patch(route('todo-lists.update', $list), ['title' => 'Nouveau titre'])
        ->assertRedirect(route('todo-lists.show', $list));

    expect($list->fresh()->title)->toBe('Nouveau titre');
});

test('destroy deletes the list and redirects to notes index', function () {
    $user = User::factory()->create();
    $list = TodoList::factory()->create();

    $this->actingAs($user)
        ->delete(route('todo-lists.destroy', $list))
        ->assertRedirect(route('notes.index', ['tab' => 'todos']));

    $this->assertDatabaseMissing('todo_lists', ['id' => $list->id]);
});
