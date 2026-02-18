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
            ->has('period')
            ->has('currentMonth')
            ->has('totalAmount')
        );
});

test('history defaults to monthly period for current month', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
        'date' => now()->format('Y-m-d'),
    ]);
    Expense::factory()->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
        'date' => now()->subYear()->format('Y-m-d'),
    ]);

    $this->actingAs($user)
        ->get(route('expenses.history'))
        ->assertInertia(fn ($page) => $page
            ->has('expenses', 1)
            ->where('period', 'monthly')
        );
});

test('history with period=annual returns current year expenses', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
        'date' => now()->format('Y-m-d'),
    ]);
    Expense::factory()->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
        'date' => now()->subYear()->format('Y-m-d'),
    ]);

    $this->actingAs($user)
        ->get(route('expenses.history', ['period' => 'annual']))
        ->assertInertia(fn ($page) => $page
            ->has('expenses', 1)
            ->where('period', 'annual')
        );
});

test('history with period=total returns all expenses', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->count(3)->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
        'date' => now()->subYear()->format('Y-m-d'),
    ]);

    $this->actingAs($user)
        ->get(route('expenses.history', ['period' => 'total']))
        ->assertInertia(fn ($page) => $page
            ->has('expenses', 3)
            ->where('period', 'total')
        );
});

test('history with month param filters by specific month', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
        'date' => '2025-06-15',
    ]);
    Expense::factory()->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
        'date' => '2025-07-10',
    ]);

    $this->actingAs($user)
        ->get(route('expenses.history', ['period' => 'monthly', 'month' => '2025-06']))
        ->assertInertia(fn ($page) => $page
            ->has('expenses', 1)
            ->where('currentMonth', '2025-06')
        );
});

test('history includes category totals for the period', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->create([
        'amount' => 50,
        'paid_by' => $user->id,
        'category_id' => $category->id,
        'date' => now()->format('Y-m-d'),
    ]);
    Expense::factory()->create([
        'amount' => 30,
        'paid_by' => $user->id,
        'category_id' => $category->id,
        'date' => now()->format('Y-m-d'),
    ]);

    $this->actingAs($user)
        ->get(route('expenses.history'))
        ->assertInertia(fn ($page) => $page
            ->has('categoryTotals', 1)
            ->where('totalAmount', '80.00')
        );
});

test('history requires authentication', function () {
    $this->get(route('expenses.history'))
        ->assertRedirect('/login');
});
