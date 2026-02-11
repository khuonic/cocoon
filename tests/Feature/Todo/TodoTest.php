<?php

use App\Models\Todo;
use App\Models\User;

test('guests are redirected to login', function () {
    $this->get(route('todos.index'))->assertRedirect('/login');
});

test('authenticated users can view the todo index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('todos.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Todos/Index')
            ->has('sharedTodos')
            ->has('personalTodos')
            ->has('doneTodos')
            ->has('users')
        );
});

test('index returns shared todos separately from personal', function () {
    $user = User::factory()->create();
    Todo::factory()->create(['title' => 'Tâche partagée', 'is_personal' => false, 'created_by' => $user->id]);
    Todo::factory()->personal()->create(['title' => 'Tâche perso', 'created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('todos.index'))
        ->assertInertia(fn ($page) => $page
            ->has('sharedTodos', 1)
            ->where('sharedTodos.0.title', 'Tâche partagée')
            ->has('personalTodos', 1)
            ->where('personalTodos.0.title', 'Tâche perso')
        );
});

test('index personal todos only shows current user todos', function () {
    $kevin = User::factory()->create();
    $lola = User::factory()->create();
    Todo::factory()->personal()->create(['title' => 'Perso Kevin', 'created_by' => $kevin->id]);
    Todo::factory()->personal()->create(['title' => 'Perso Lola', 'created_by' => $lola->id]);

    $this->actingAs($kevin)
        ->get(route('todos.index'))
        ->assertInertia(fn ($page) => $page
            ->has('personalTodos', 1)
            ->where('personalTodos.0.title', 'Perso Kevin')
        );
});

test('index done todos are sorted by completed_at desc', function () {
    $user = User::factory()->create();
    Todo::factory()->done()->create([
        'title' => 'Ancienne',
        'created_by' => $user->id,
        'completed_at' => now()->subDay(),
    ]);
    Todo::factory()->done()->create([
        'title' => 'Récente',
        'created_by' => $user->id,
        'completed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('todos.index'))
        ->assertInertia(fn ($page) => $page
            ->where('doneTodos.0.title', 'Récente')
            ->where('doneTodos.1.title', 'Ancienne')
        );
});

test('store creates a shared todo', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('todos.store'), [
            'title' => 'Faire les courses',
            'description' => null,
            'is_personal' => false,
            'assigned_to' => null,
            'due_date' => null,
            'show_on_dashboard' => false,
        ])
        ->assertRedirect(route('todos.index'));

    $this->assertDatabaseHas('todos', [
        'title' => 'Faire les courses',
        'is_personal' => false,
        'created_by' => $user->id,
    ]);

    $todo = Todo::query()->where('title', 'Faire les courses')->first();
    expect($todo->uuid)->not->toBeNull();
});

test('store creates a personal todo', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('todos.store'), [
            'title' => 'Ma tâche perso',
            'description' => null,
            'is_personal' => true,
            'assigned_to' => null,
            'due_date' => null,
            'show_on_dashboard' => false,
        ])
        ->assertRedirect(route('todos.index'));

    $this->assertDatabaseHas('todos', [
        'title' => 'Ma tâche perso',
        'is_personal' => true,
        'created_by' => $user->id,
    ]);
});

test('store validates title is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('todos.store'), [
            'title' => '',
            'is_personal' => false,
            'show_on_dashboard' => false,
        ])
        ->assertSessionHasErrors(['title']);
});

test('store validates assigned_to exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('todos.store'), [
            'title' => 'Test',
            'is_personal' => false,
            'assigned_to' => 999,
            'show_on_dashboard' => false,
        ])
        ->assertSessionHasErrors(['assigned_to']);
});

test('store assigns created_by automatically', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('todos.store'), [
            'title' => 'Auto assigned',
            'is_personal' => false,
            'assigned_to' => null,
            'due_date' => null,
            'show_on_dashboard' => false,
        ]);

    expect(Todo::query()->first()->created_by)->toBe($user->id);
});

test('update modifies a todo', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->create(['title' => 'Ancien titre', 'created_by' => $user->id]);

    $this->actingAs($user)
        ->put(route('todos.update', $todo), [
            'title' => 'Nouveau titre',
            'description' => 'Une description',
            'is_personal' => false,
            'assigned_to' => null,
            'due_date' => null,
            'show_on_dashboard' => false,
        ])
        ->assertRedirect(route('todos.index'));

    expect($todo->fresh()->title)->toBe('Nouveau titre');
    expect($todo->fresh()->description)->toBe('Une description');
});

test('toggle sets is_done to true and completed_at', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->create(['created_by' => $user->id, 'is_done' => false]);

    $this->actingAs($user)
        ->patch(route('todos.toggle', $todo))
        ->assertRedirect(route('todos.index'));

    $todo->refresh();
    expect($todo->is_done)->toBeTrue();
    expect($todo->completed_at)->not->toBeNull();
});

test('toggle sets is_done to false and clears completed_at', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->done()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('todos.toggle', $todo))
        ->assertRedirect(route('todos.index'));

    $todo->refresh();
    expect($todo->is_done)->toBeFalse();
    expect($todo->completed_at)->toBeNull();
});

test('destroy deletes a todo', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->delete(route('todos.destroy', $todo))
        ->assertRedirect(route('todos.index'));

    $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
});

test('shared todos do not include personal ones', function () {
    $user = User::factory()->create();
    Todo::factory()->personal()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('todos.index'))
        ->assertInertia(fn ($page) => $page
            ->has('sharedTodos', 0)
            ->has('personalTodos', 1)
        );
});
