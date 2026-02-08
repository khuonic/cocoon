<?php

use App\Http\Controllers\Auth\SetupController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\MealPlanController;
use App\Http\Controllers\MoreController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ShoppingListController;
use App\Http\Controllers\TodoController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('setup', [SetupController::class, 'create'])->name('setup');
    Route::post('setup', [SetupController::class, 'store']);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('expenses/settle', [ExpenseController::class, 'settle'])->name('expenses.settle');
    Route::get('expenses/history', [ExpenseController::class, 'history'])->name('expenses.history');
    Route::resource('expenses', ExpenseController::class)->except(['show']);
    Route::resource('shopping-lists', ShoppingListController::class)->only(['index']);
    Route::resource('todos', TodoController::class)->only(['index']);
    Route::resource('meal-plans', MealPlanController::class)->only(['index']);
    Route::resource('notes', NoteController::class)->only(['index']);
    Route::resource('bookmarks', BookmarkController::class)->only(['index']);
    Route::get('more', MoreController::class)->name('more');
});

require __DIR__.'/settings.php';
