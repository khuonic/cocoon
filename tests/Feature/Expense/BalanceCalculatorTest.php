<?php

use App\Enums\SplitType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\BalanceCalculator;

beforeEach(function () {
    $this->calculator = new BalanceCalculator;
    $this->kevin = User::factory()->create(['name' => 'Kevin']);
    $this->lola = User::factory()->create(['name' => 'Lola']);
    $this->category = ExpenseCategory::factory()->create();
});

test('returns zero balance when no expenses exist', function () {
    $result = $this->calculator->calculate();

    expect($result['balance'])->toBe('0.00');
    expect($result['is_settled'])->toBeTrue();
    expect($result['creditor'])->toBeNull();
    expect($result['debtor'])->toBeNull();
    expect($result['unsettled_count'])->toBe(0);
});

test('calculates equal split correctly', function () {
    Expense::factory()->create([
        'amount' => 100,
        'paid_by' => $this->kevin->id,
        'split_type' => SplitType::Equal,
        'category_id' => $this->category->id,
    ]);

    $result = $this->calculator->calculate();

    expect($result['balance'])->toBe('50.00');
    expect($result['creditor']->id)->toBe($this->kevin->id);
    expect($result['debtor']->id)->toBe($this->lola->id);
    expect($result['is_settled'])->toBeFalse();
});

test('handles full_payer split as personal expense', function () {
    Expense::factory()->create([
        'amount' => 100,
        'paid_by' => $this->kevin->id,
        'split_type' => SplitType::FullPayer,
        'category_id' => $this->category->id,
    ]);

    $result = $this->calculator->calculate();

    expect($result['balance'])->toBe('0.00');
    expect($result['is_settled'])->toBeTrue();
});

test('handles full_other split', function () {
    Expense::factory()->create([
        'amount' => 100,
        'paid_by' => $this->kevin->id,
        'split_type' => SplitType::FullOther,
        'category_id' => $this->category->id,
    ]);

    $result = $this->calculator->calculate();

    expect($result['balance'])->toBe('100.00');
    expect($result['creditor']->id)->toBe($this->kevin->id);
    expect($result['debtor']->id)->toBe($this->lola->id);
});

test('handles custom split', function () {
    Expense::factory()->create([
        'amount' => 100,
        'paid_by' => $this->kevin->id,
        'split_type' => SplitType::Custom,
        'split_value' => 30,
        'category_id' => $this->category->id,
    ]);

    $result = $this->calculator->calculate();

    expect($result['balance'])->toBe('30.00');
    expect($result['creditor']->id)->toBe($this->kevin->id);
});

test('nets out expenses from both users', function () {
    Expense::factory()->create([
        'amount' => 100,
        'paid_by' => $this->kevin->id,
        'split_type' => SplitType::Equal,
        'category_id' => $this->category->id,
    ]);

    Expense::factory()->create([
        'amount' => 60,
        'paid_by' => $this->lola->id,
        'split_type' => SplitType::Equal,
        'category_id' => $this->category->id,
    ]);

    $result = $this->calculator->calculate();

    expect($result['balance'])->toBe('20.00');
    expect($result['creditor']->id)->toBe($this->kevin->id);
    expect($result['debtor']->id)->toBe($this->lola->id);
});

test('ignores settled expenses', function () {
    Expense::factory()->settled()->create([
        'amount' => 100,
        'paid_by' => $this->kevin->id,
        'split_type' => SplitType::Equal,
        'category_id' => $this->category->id,
    ]);

    $result = $this->calculator->calculate();

    expect($result['balance'])->toBe('0.00');
    expect($result['is_settled'])->toBeTrue();
    expect($result['unsettled_count'])->toBe(0);
});

test('returns is_settled true when balance is zero', function () {
    Expense::factory()->create([
        'amount' => 50,
        'paid_by' => $this->kevin->id,
        'split_type' => SplitType::Equal,
        'category_id' => $this->category->id,
    ]);

    Expense::factory()->create([
        'amount' => 50,
        'paid_by' => $this->lola->id,
        'split_type' => SplitType::Equal,
        'category_id' => $this->category->id,
    ]);

    $result = $this->calculator->calculate();

    expect($result['balance'])->toBe('0.00');
    expect($result['is_settled'])->toBeTrue();
});
