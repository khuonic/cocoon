<?php

namespace App\Http\Controllers;

use App\Http\Requests\Todo\StoreTodoRequest;
use App\Http\Requests\Todo\UpdateTodoRequest;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TodoController extends Controller
{
    public function index(): Response
    {
        $sharedTodos = Todo::query()
            ->where('is_personal', false)
            ->where('is_done', false)
            ->with(['creator', 'assignee'])
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->oldest('created_at')
            ->get();

        $personalTodos = Todo::query()
            ->where('is_personal', true)
            ->where('created_by', auth()->id())
            ->where('is_done', false)
            ->with(['creator', 'assignee'])
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->oldest('created_at')
            ->get();

        $doneTodos = Todo::query()
            ->where('is_done', true)
            ->with(['creator', 'assignee'])
            ->latest('completed_at')
            ->limit(20)
            ->get();

        return Inertia::render('Todos/Index', [
            'sharedTodos' => $sharedTodos,
            'personalTodos' => $personalTodos,
            'doneTodos' => $doneTodos,
            'users' => User::all(),
        ]);
    }

    public function store(StoreTodoRequest $request): RedirectResponse
    {
        Todo::create([
            ...$request->validated(),
            'uuid' => Str::uuid(),
            'created_by' => auth()->id(),
        ]);

        return to_route('todos.index');
    }

    public function update(UpdateTodoRequest $request, Todo $todo): RedirectResponse
    {
        $todo->update($request->validated());

        return to_route('todos.index');
    }

    public function toggle(Todo $todo): RedirectResponse
    {
        $todo->update([
            'is_done' => ! $todo->is_done,
            'completed_at' => ! $todo->is_done ? now() : null,
        ]);

        return to_route('todos.index');
    }

    public function destroy(Todo $todo): RedirectResponse
    {
        $todo->delete();

        return to_route('todos.index');
    }
}
