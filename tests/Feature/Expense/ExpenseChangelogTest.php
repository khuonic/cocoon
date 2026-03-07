<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;

test('authenticated users can view the changelog', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('expenses.changelog'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Changelog')
            ->has('activities')
        );
});

test('changelog requires authentication', function () {
    $this->get(route('expenses.changelog'))
        ->assertRedirect('/login');
});

test('changelog shows expense update activities', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();
    $expense = Expense::factory()->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
        'split_type' => 'equal',
        'description' => 'Avant',
    ]);

    $this->actingAs($user)
        ->put(route('expenses.update', $expense), [
            'amount' => 99.00,
            'description' => 'Après',
            'category_id' => $category->id,
            'paid_by' => $user->id,
            'split_type' => 'equal',
            'date' => '2026-02-08',
            'is_recurring' => false,
        ]);

    // 2 activities : created + updated
    $this->actingAs($user)
        ->get(route('expenses.changelog'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Changelog')
            ->has('activities', 2)
        );

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => Expense::class,
        'subject_id' => $expense->id,
        'event' => 'updated',
    ]);
});

test('changelog is limited to expense activities', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('expenses.changelog'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('activities', 0)
        );
});
