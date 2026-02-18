<?php

namespace App\Http\Controllers;

use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\BalanceCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function history(Request $request): Response
    {
        $period = $request->query('period', 'monthly');
        $currentMonth = $request->query('month', now()->format('Y-m'));

        $query = Expense::query()
            ->with(['category', 'payer'])
            ->latest('date');

        if ($period === 'monthly') {
            [$year, $month] = explode('-', $currentMonth);
            $query->whereYear('date', $year)->whereMonth('date', $month);
        } elseif ($period === 'annual') {
            $query->whereYear('date', now()->year);
        }

        $expenses = $query->get();

        $categoryTotals = $expenses
            ->groupBy('category_id')
            ->map(fn ($items) => [
                'category_id' => $items->first()->category_id,
                'total' => number_format((float) $items->sum('amount'), 2, '.', ''),
                'category' => $items->first()->category,
            ])
            ->values();

        return Inertia::render('Budget/History', [
            'expenses' => $expenses,
            'categoryTotals' => $categoryTotals,
            'period' => $period,
            'currentMonth' => $currentMonth,
            'totalAmount' => number_format((float) $expenses->sum('amount'), 2, '.', ''),
        ]);
    }
}
