<?php

namespace App\Http\Controllers;

use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\BalanceCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function __construct(private BalanceCalculator $balanceCalculator) {}

    public function index(): Response
    {
        $expenses = Expense::query()
            ->whereNull('settled_at')
            ->with(['category', 'payer'])
            ->latest('date')
            ->get();

        return Inertia::render('Budget/Index', [
            'expenses' => $expenses,
            'balance' => $this->balanceCalculator->calculate(),
            'users' => User::all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Budget/Create', [
            'categories' => ExpenseCategory::query()->orderBy('sort_order')->get(),
            'users' => User::all(),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        Expense::create([
            ...$request->validated(),
            'uuid' => Str::uuid(),
        ]);

        return to_route('expenses.index');
    }

    public function edit(Expense $expense): Response
    {
        return Inertia::render('Budget/Edit', [
            'expense' => $expense->load(['category', 'payer']),
            'categories' => ExpenseCategory::query()->orderBy('sort_order')->get(),
            'users' => User::all(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update($request->validated());

        return to_route('expenses.index');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return to_route('expenses.index');
    }

    public function settle(): RedirectResponse
    {
        Expense::query()
            ->whereNull('settled_at')
            ->update(['settled_at' => now()]);

        return to_route('expenses.index');
    }

    public function history(): Response
    {
        $expenses = Expense::query()
            ->with(['category', 'payer'])
            ->latest('date')
            ->paginate(20);

        $categoryTotals = Expense::query()
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->with('category')
            ->get();

        return Inertia::render('Budget/History', [
            'expenses' => $expenses,
            'categoryTotals' => $categoryTotals,
        ]);
    }
}
