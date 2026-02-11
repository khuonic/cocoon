<?php

namespace App\Http\Controllers;

use App\Enums\BookmarkCategory;
use App\Http\Requests\Bookmark\StoreBookmarkRequest;
use App\Http\Requests\Bookmark\UpdateBookmarkRequest;
use App\Models\Bookmark;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BookmarkController extends Controller
{
    public function index(): Response
    {
        $bookmarks = Bookmark::query()
            ->with('addedBy')
            ->orderByDesc('is_favorite')
            ->latest()
            ->get();

        $categories = collect(BookmarkCategory::cases())->map(fn (BookmarkCategory $c) => [
            'value' => $c->value,
            'label' => $c->label(),
        ]);

        return Inertia::render('Bookmarks/Index', [
            'bookmarks' => $bookmarks,
            'categories' => $categories,
        ]);
    }

    public function store(StoreBookmarkRequest $request): RedirectResponse
    {
        Bookmark::create([
            ...$request->validated(),
            'uuid' => Str::uuid(),
            'added_by' => auth()->id(),
        ]);

        return to_route('bookmarks.index');
    }

    public function update(UpdateBookmarkRequest $request, Bookmark $bookmark): RedirectResponse
    {
        $bookmark->update($request->validated());

        return to_route('bookmarks.index');
    }

    public function toggleFavorite(Bookmark $bookmark): RedirectResponse
    {
        $bookmark->update([
            'is_favorite' => ! $bookmark->is_favorite,
        ]);

        return to_route('bookmarks.index');
    }

    public function destroy(Bookmark $bookmark): RedirectResponse
    {
        $bookmark->delete();

        return to_route('bookmarks.index');
    }
}
