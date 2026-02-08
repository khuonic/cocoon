<?php

namespace App\Services;

use App\Enums\SplitType;
use App\Models\Expense;
use App\Models\User;

class BalanceCalculator
{
    /**
     * Calculate the net balance between the two household users.
     *
     * A positive balance means user A is owed money, negative means user A owes money.
     * The "reference user" (user A) is the first user by ID.
     *
     * @return array{
     *     balance: string,
     *     creditor: User|null,
     *     debtor: User|null,
     *     is_settled: bool,
     *     unsettled_count: int
     * }
     */
    public function calculate(): array
    {
        $users = User::query()->orderBy('id')->get();

        if ($users->count() < 2) {
            return [
                'balance' => '0.00',
                'creditor' => null,
                'debtor' => null,
                'is_settled' => true,
                'unsettled_count' => 0,
            ];
        }

        $userA = $users->first();
        $userB = $users->last();

        $expenses = Expense::query()
            ->whereNull('settled_at')
            ->get();

        $netBalance = 0;

        foreach ($expenses as $expense) {
            $amountOwedToPayerByOther = $this->calculateOwed($expense);

            if ($expense->paid_by === $userA->id) {
                $netBalance += $amountOwedToPayerByOther;
            } else {
                $netBalance -= $amountOwedToPayerByOther;
            }
        }

        $isSettled = abs($netBalance) < 0.01;

        return [
            'balance' => number_format(abs($netBalance), 2, '.', ''),
            'creditor' => $netBalance > 0.01 ? $userA : ($netBalance < -0.01 ? $userB : null),
            'debtor' => $netBalance > 0.01 ? $userB : ($netBalance < -0.01 ? $userA : null),
            'is_settled' => $isSettled,
            'unsettled_count' => $expenses->count(),
        ];
    }

    /**
     * Calculate how much the non-payer owes for a given expense.
     */
    private function calculateOwed(Expense $expense): float
    {
        return match ($expense->split_type) {
            SplitType::Equal => (float) $expense->amount / 2,
            SplitType::FullPayer => 0,
            SplitType::FullOther => (float) $expense->amount,
            SplitType::Custom => (float) ($expense->split_value ?? 0),
        };
    }
}
