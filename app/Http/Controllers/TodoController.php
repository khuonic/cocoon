<?php

namespace App\Http\Controllers;

use App\Http\Requests\Todo\StoreTodoRequest;
use App\Models\Todo;
use App\Models\TodoList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TodoController extends Controller
{
    public function store(StoreTodoRequest $request, TodoList $todoList): RedirectResponse
    {
        $todoList->todos()->create([
            ...$request->validated(),
            'uuid' => Str::uuid(),
        ]);

        return to_route('todo-lists.show', $todoList);
    }

    public function update(Request $request, Todo $todo): RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $todo->update(['title' => $request->input('title')]);

        return to_route('todo-lists.show', $todo->todo_list_id);
    }

    public function toggle(Todo $todo): RedirectResponse
    {
        $todo->update([
            'is_done' => ! $todo->is_done,
            'completed_at' => ! $todo->is_done ? now() : null,
        ]);

        return to_route('todo-lists.show', $todo->todo_list_id);
    }

    public function destroy(Todo $todo): RedirectResponse
    {
        $listId = $todo->todo_list_id;
        $todo->delete();

        return to_route('todo-lists.show', $listId);
    }
}
