<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;

test('settle marks all unsettled expenses as settled', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();

    $expense1 = Expense::factory()->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
    ]);
    $expense2 = Expense::factory()->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($user)
        ->post(route('expenses.settle'))
        ->assertRedirect(route('expenses.index'));

    expect($expense1->fresh()->settled_at)->not->toBeNull();
    expect($expense2->fresh()->settled_at)->not->toBeNull();
});

test('settle does not affect already settled expenses', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create();

    $settled = Expense::factory()->settled()->create([
        'paid_by' => $user->id,
        'category_id' => $category->id,
    ]);
    $originalSettledAt = $settled->settled_at;

    $this->actingAs($user)
        ->post(route('expenses.settle'));

    expect($settled->fresh()->settled_at->toDateTimeString())
        ->toBe($originalSettledAt->toDateTimeString());
});

test('settle requires authentication', function () {
    $this->post(route('expenses.settle'))
        ->assertRedirect('/login');
});
