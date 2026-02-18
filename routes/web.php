<?php

use App\Http\Controllers\Auth\BiometricController;
use App\Http\Controllers\Auth\SetupController;
use App\Http\Controllers\BirthdayController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\MoreController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ShoppingItemController;
use App\Http\Controllers\ShoppingListController;
use App\Http\Controllers\SweetMessageController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\TodoListController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('setup', [SetupController::class, 'create'])->name('setup');
    Route::post('setup', [SetupController::class, 'store']);
    Route::get('biometric-login', [BiometricController::class, 'show'])->name('biometric.login');
    Route::post('biometric-login', [BiometricController::class, 'verify'])->name('biometric.verify');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    // Budget
    Route::post('expenses/settle', [ExpenseController::class, 'settle'])->name('expenses.settle');
    Route::get('expenses/history', [ExpenseController::class, 'history'])->name('expenses.history');
    Route::resource('expenses', ExpenseController::class)->except(['show']);

    // Courses
    Route::post('shopping-lists/{shopping_list}/duplicate', [ShoppingListController::class, 'duplicate'])
        ->name('shopping-lists.duplicate');
    Route::resource('shopping-lists', ShoppingListController::class)->except(['edit']);
    Route::post('shopping-lists/{shopping_list}/items', [ShoppingItemController::class, 'store'])
        ->name('shopping-items.store');
    Route::put('shopping-items/{shopping_item}', [ShoppingItemController::class, 'update'])
        ->name('shopping-items.update');
    Route::patch('shopping-items/{shopping_item}/toggle-check', [ShoppingItemController::class, 'toggleCheck'])
        ->name('shopping-items.toggle-check');
    Route::delete('shopping-items/{shopping_item}', [ShoppingItemController::class, 'destroy'])
        ->name('shopping-items.destroy');

    // Recettes
    Route::resource('recipes', RecipeController::class);

    // Notes + Todos
    Route::get('notes', [NoteController::class, 'index'])->name('notes.index');
    Route::post('notes', [NoteController::class, 'store'])->name('notes.store');
    Route::get('notes/{note}', [NoteController::class, 'show'])->name('notes.show');
    Route::patch('notes/{note}', [NoteController::class, 'update'])->name('notes.update');
    Route::patch('notes/{note}/toggle-pin', [NoteController::class, 'togglePin'])->name('notes.toggle-pin');
    Route::delete('notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

    Route::post('todo-lists', [TodoListController::class, 'store'])->name('todo-lists.store');
    Route::get('todo-lists/{todo_list}', [TodoListController::class, 'show'])->name('todo-lists.show');
    Route::patch('todo-lists/{todo_list}', [TodoListController::class, 'update'])->name('todo-lists.update');
    Route::delete('todo-lists/{todo_list}', [TodoListController::class, 'destroy'])->name('todo-lists.destroy');

    Route::post('todo-lists/{todo_list}/todos', [TodoController::class, 'store'])->name('todos.store');
    Route::patch('todos/{todo}/toggle', [TodoController::class, 'toggle'])->name('todos.toggle');
    Route::patch('todos/{todo}', [TodoController::class, 'update'])->name('todos.update');
    Route::delete('todos/{todo}', [TodoController::class, 'destroy'])->name('todos.destroy');

    // Divers
    Route::post('sweet-messages', [SweetMessageController::class, 'store'])->name('sweet-messages.store');
    Route::resource('birthdays', BirthdayController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('more', MoreController::class)->name('more');
});

require __DIR__.'/settings.php';
