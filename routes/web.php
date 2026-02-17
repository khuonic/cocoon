<?php

use App\Http\Controllers\Auth\BiometricController;
use App\Http\Controllers\Auth\SetupController;
use App\Http\Controllers\BirthdayController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\MealIdeaController;
use App\Http\Controllers\MealPlanController;
use App\Http\Controllers\MoreController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ShoppingItemController;
use App\Http\Controllers\ShoppingListController;
use App\Http\Controllers\SweetMessageController;
use App\Http\Controllers\TodoController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('setup', [SetupController::class, 'create'])->name('setup');
    Route::post('setup', [SetupController::class, 'store']);
    Route::get('biometric-login', [BiometricController::class, 'show'])->name('biometric.login');
    Route::post('biometric-login', [BiometricController::class, 'verify'])->name('biometric.verify');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('expenses/settle', [ExpenseController::class, 'settle'])->name('expenses.settle');
    Route::get('expenses/history', [ExpenseController::class, 'history'])->name('expenses.history');
    Route::resource('expenses', ExpenseController::class)->except(['show']);
    Route::post('shopping-lists/{shopping_list}/duplicate', [ShoppingListController::class, 'duplicate'])
        ->name('shopping-lists.duplicate');
    Route::resource('shopping-lists', ShoppingListController::class)->except(['edit']);

    Route::post('shopping-lists/{shopping_list}/items', [ShoppingItemController::class, 'store'])
        ->name('shopping-items.store');
    Route::patch('shopping-items/{shopping_item}/toggle-check', [ShoppingItemController::class, 'toggleCheck'])
        ->name('shopping-items.toggle-check');
    Route::patch('shopping-items/{shopping_item}/toggle-favorite', [ShoppingItemController::class, 'toggleFavorite'])
        ->name('shopping-items.toggle-favorite');
    Route::delete('shopping-items/{shopping_item}', [ShoppingItemController::class, 'destroy'])
        ->name('shopping-items.destroy');
    Route::patch('todos/{todo}/toggle', [TodoController::class, 'toggle'])->name('todos.toggle');
    Route::resource('todos', TodoController::class)->except(['create', 'show', 'edit']);
    Route::resource('meal-plans', MealPlanController::class)->only(['index']);
    Route::resource('meal-ideas', MealIdeaController::class)->only(['store', 'update', 'destroy']);
    Route::resource('recipes', RecipeController::class)->except(['index']);
    Route::patch('notes/{note}/toggle-pin', [NoteController::class, 'togglePin'])->name('notes.toggle-pin');
    Route::resource('notes', NoteController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('bookmarks/{bookmark}/toggle-favorite', [BookmarkController::class, 'toggleFavorite'])->name('bookmarks.toggle-favorite');
    Route::resource('bookmarks', BookmarkController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('sweet-messages', [SweetMessageController::class, 'store'])->name('sweet-messages.store');
    Route::resource('birthdays', BirthdayController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('more', MoreController::class)->name('more');
});

require __DIR__.'/settings.php';
