<?php

namespace App\Http\Controllers;

use App\Http\Requests\Note\StoreNoteRequest;
use App\Http\Requests\Note\UpdateNoteRequest;
use App\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function index(): Response
    {
        $notes = Note::query()
            ->with('creator')
            ->orderByDesc('is_pinned')
            ->latest('updated_at')
            ->get();

        return Inertia::render('Notes/Index', [
            'notes' => $notes,
        ]);
    }

    public function store(StoreNoteRequest $request): RedirectResponse
    {
        Note::create([
            ...$request->validated(),
            'uuid' => Str::uuid(),
            'created_by' => auth()->id(),
        ]);

        return to_route('notes.index');
    }

    public function update(UpdateNoteRequest $request, Note $note): RedirectResponse
    {
        $note->update($request->validated());

        return to_route('notes.index');
    }

    public function togglePin(Note $note): RedirectResponse
    {
        $note->update([
            'is_pinned' => ! $note->is_pinned,
        ]);

        return to_route('notes.index');
    }

    public function destroy(Note $note): RedirectResponse
    {
        $note->delete();

        return to_route('notes.index');
    }
}
