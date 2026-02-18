<?php

namespace App\Http\Controllers;

use App\Http\Requests\Note\StoreNoteRequest;
use App\Http\Requests\Note\UpdateNoteRequest;
use App\Models\Note;
use App\Models\TodoList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function index(Request $request): Response
    {
        $notes = Note::query()
            ->with('creator')
            ->orderByDesc('is_pinned')
            ->latest('updated_at')
            ->get();

        $todoLists = TodoList::query()
            ->with(['todos' => fn ($q) => $q->oldest('created_at')])
            ->latest('updated_at')
            ->get();

        return Inertia::render('Notes/Index', [
            'notes' => $notes,
            'todoLists' => $todoLists,
            'tab' => $request->query('tab', 'notes'),
        ]);
    }

    public function show(Note $note): Response
    {
        return Inertia::render('Notes/Show', [
            'note' => $note,
        ]);
    }

    public function store(StoreNoteRequest $request): RedirectResponse
    {
        $note = Note::create([
            ...$request->validated(),
            'uuid' => Str::uuid(),
            'created_by' => auth()->id(),
        ]);

        return to_route('notes.show', $note);
    }

    public function update(UpdateNoteRequest $request, Note $note): RedirectResponse
    {
        $note->update($request->validated());

        return back();
    }

    public function togglePin(Note $note): RedirectResponse
    {
        $note->update([
            'is_pinned' => ! $note->is_pinned,
        ]);

        return back();
    }

    public function destroy(Note $note): RedirectResponse
    {
        $note->delete();

        return to_route('notes.index');
    }
}
