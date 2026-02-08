<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;

test('guests are redirected to login', function () {
    $this->get(route('expenses.index'))->assertRedirect('/login');
});

test('authenticated users can view the expense index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Budget/Index'));
});

test('index displays unsettled expenses', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->create([
        'description' => 'Courses Leclerc',
        'paid_by' => $user->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Index')
            ->has('expenses', 1)
            ->where('expenses.0.description', 'Courses Leclerc')
        );
});

test('index does not display settled expenses', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();

    Expense::factory()->settled()->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Index')
            ->has('expenses', 0)
        );
});

test('index includes balance data', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('expenses.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Index')
            ->has('balance')
            ->where('balance.is_settled', true)
        );
});
