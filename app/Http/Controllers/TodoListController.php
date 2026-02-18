<?php

namespace App\Http\Controllers;

use App\Http\Requests\TodoList\StoreTodoListRequest;
use App\Http\Requests\TodoList\UpdateTodoListRequest;
use App\Models\TodoList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TodoListController extends Controller
{
    public function show(TodoList $todoList): Response
    {
        return Inertia::render('TodoLists/Show', [
            'todoList' => $todoList->load(['todos' => fn ($q) => $q->oldest('created_at')]),
        ]);
    }

    public function store(StoreTodoListRequest $request): RedirectResponse
    {
        $todoList = TodoList::create([
            ...$request->validated(),
            'uuid' => Str::uuid(),
            'user_id' => $request->boolean('is_personal') ? auth()->id() : null,
        ]);

        return to_route('todo-lists.show', $todoList);
    }

    public function update(UpdateTodoListRequest $request, TodoList $todoList): RedirectResponse
    {
        $todoList->update($request->validated());

        return to_route('todo-lists.show', $todoList);
    }

    public function destroy(TodoList $todoList): RedirectResponse
    {
        $todoList->delete();

        return to_route('notes.index', ['tab' => 'todos']);
    }
}
