<?php

use App\Models\ExpenseCategory;
use App\Models\User;

test('authenticated users can view the create form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('expenses.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Budget/Create'));
});

test('create form includes categories', function () {
    $user = User::factory()->create();
    ExpenseCategory::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('expenses.create'))
        ->assertInertia(fn ($page) => $page
            ->has('categories', 3)
            ->has('users')
        );
});

test('users can store a new expense', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'amount' => 42.50,
            'description' => 'Courses Leclerc',
            'category_id' => $category->id,
            'paid_by' => $user->id,
            'split_type' => 'equal',
            'date' => '2026-02-08',
            'is_recurring' => false,
        ])
        ->assertRedirect(route('expenses.index'));

    $this->assertDatabaseHas('expenses', [
        'description' => 'Courses Leclerc',
        'amount' => 42.50,
        'paid_by' => $user->id,
    ]);
});

test('store validates required fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('expenses.store'), [])
        ->assertSessionHasErrors(['amount', 'description', 'category_id', 'paid_by', 'split_type', 'date']);
});

test('store validates amount is positive', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'amount' => -10,
            'description' => 'Test',
            'category_id' => $category->id,
            'paid_by' => $user->id,
            'split_type' => 'equal',
            'date' => '2026-02-08',
        ])
        ->assertSessionHasErrors('amount');
});

test('store validates category exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'amount' => 10,
            'description' => 'Test',
            'category_id' => 999,
            'paid_by' => $user->id,
            'split_type' => 'equal',
            'date' => '2026-02-08',
        ])
        ->assertSessionHasErrors('category_id');
});

test('store requires split_value when split_type is custom', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'amount' => 100,
            'description' => 'Test',
            'category_id' => $category->id,
            'paid_by' => $user->id,
            'split_type' => 'custom',
            'split_value' => null,
            'date' => '2026-02-08',
        ])
        ->assertSessionHasErrors('split_value');
});

test('store requires recurrence_type when is_recurring is true', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'amount' => 50,
            'description' => 'Abonnement',
            'category_id' => $category->id,
            'paid_by' => $user->id,
            'split_type' => 'equal',
            'date' => '2026-02-08',
            'is_recurring' => true,
            'recurrence_type' => null,
        ])
        ->assertSessionHasErrors('recurrence_type');
});
