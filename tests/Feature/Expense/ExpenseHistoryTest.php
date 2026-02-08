<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;

test('authenticated users can view history', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('expenses.history'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Budget/History')
            ->has('expenses')
            ->has('categoryTotals')
        );
});

test('history includes all expenses (settled and unsettled)', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
    ]);
    Expense::factory()->settled()->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($user)
        ->get(route('expenses.history'))
        ->assertInertia(fn ($page) => $page
            ->has('expenses.data', 2)
        );
});

test('history includes category totals', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->create([
        'amount' => 50,
        'paid_by' => $user->id,
        'category_id' => $category->id,
    ]);
    Expense::factory()->create([
        'amount' => 30,
        'paid_by' => $user->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($user)
        ->get(route('expenses.history'))
        ->assertInertia(fn ($page) => $page
            ->has('categoryTotals', 1)
        );
});

test('history requires authentication', function () {
    $this->get(route('expenses.history'))
        ->assertRedirect('/login');
});
