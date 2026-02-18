<?php

use App\Models\Todo;
use App\Models\TodoList;
use App\Models\User;

test('store creates a todo in the given list', function () {
    $user = User::factory()->create();
    $list = TodoList::factory()->create();

    $this->actingAs($user)
        ->post(route('todos.store', $list), ['title' => 'Faire la vaisselle'])
        ->assertRedirect(route('todo-lists.show', $list));

    $this->assertDatabaseHas('todos', [
        'title' => 'Faire la vaisselle',
        'todo_list_id' => $list->id,
        'is_done' => false,
    ]);

    expect(Todo::query()->first()->uuid)->not->toBeNull();
});

test('store validates title is required', function () {
    $user = User::factory()->create();
    $list = TodoList::factory()->create();

    $this->actingAs($user)
        ->post(route('todos.store', $list), ['title' => ''])
        ->assertSessionHasErrors(['title']);
});

test('store requires authentication', function () {
    $list = TodoList::factory()->create();

    $this->post(route('todos.store', $list), ['title' => 'Test'])
        ->assertRedirect('/login');
});

test('toggle sets is_done to true and sets completed_at', function () {
    $user = User::factory()->create();
    $list = TodoList::factory()->create();
    $todo = Todo::factory()->create(['todo_list_id' => $list->id, 'is_done' => false]);

    $this->actingAs($user)
        ->patch(route('todos.toggle', $todo))
        ->assertRedirect(route('todo-lists.show', $list));

    $todo->refresh();
    expect($todo->is_done)->toBeTrue();
    expect($todo->completed_at)->not->toBeNull();
});

test('toggle sets is_done to false and clears completed_at', function () {
    $user = User::factory()->create();
    $list = TodoList::factory()->create();
    $todo = Todo::factory()->done()->create(['todo_list_id' => $list->id]);

    $this->actingAs($user)
        ->patch(route('todos.toggle', $todo))
        ->assertRedirect(route('todo-lists.show', $list));

    $todo->refresh();
    expect($todo->is_done)->toBeFalse();
    expect($todo->completed_at)->toBeNull();
});

test('destroy deletes a todo', function () {
    $user = User::factory()->create();
    $list = TodoList::factory()->create();
    $todo = Todo::factory()->create(['todo_list_id' => $list->id]);

    $this->actingAs($user)
        ->delete(route('todos.destroy', $todo))
        ->assertRedirect(route('todo-lists.show', $list));

    $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
});
