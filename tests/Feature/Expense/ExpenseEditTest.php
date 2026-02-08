<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;

test('authenticated users can view the edit form', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();
    $expense = Expense::factory()->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($user)
        ->get(route('expenses.edit', $expense))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Edit')
            ->has('expense')
            ->has('categories')
            ->has('users')
        );
});

test('users can update an expense', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();
    $expense = Expense::factory()->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
        'split_type' => 'equal',
    ]);

    $this->actingAs($user)
        ->put(route('expenses.update', $expense), [
            'amount' => 99.99,
            'description' => 'Mise à jour',
            'category_id' => $category->id,
            'paid_by' => $user->id,
            'split_type' => 'equal',
            'date' => '2026-02-08',
            'is_recurring' => false,
        ])
        ->assertRedirect(route('expenses.index'));

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'description' => 'Mise à jour',
        'amount' => 99.99,
    ]);
});

test('update validates required fields', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();
    $expense = Expense::factory()->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($user)
        ->put(route('expenses.update', $expense), [])
        ->assertSessionHasErrors(['amount', 'description', 'category_id', 'paid_by', 'split_type', 'date']);
});

test('users can delete an expense', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();
    $expense = Expense::factory()->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($user)
        ->delete(route('expenses.destroy', $expense))
        ->assertRedirect(route('expenses.index'));

    $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
});
