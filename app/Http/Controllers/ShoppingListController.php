<?php

namespace App\Http\Controllers;

use App\Enums\ShoppingItemCategory;
use App\Http\Requests\ShoppingList\StoreShoppingListRequest;
use App\Http\Requests\ShoppingList\UpdateShoppingListRequest;
use App\Models\ShoppingList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ShoppingListController extends Controller
{
    public function index(): Response
    {
        $shoppingLists = ShoppingList::query()
            ->withCount(['items', 'uncheckedItems', 'checkedItems'])
            ->orderByDesc('is_active')
            ->latest()
            ->get();

        return Inertia::render('Shopping/Index', [
            'shoppingLists' => $shoppingLists,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Shopping/Create');
    }

    public function store(StoreShoppingListRequest $request): RedirectResponse
    {
        $isTemplate = $request->boolean('is_template');

        $shoppingList = ShoppingList::create([
            ...$request->validated(),
            'is_template' => $isTemplate,
            'is_active' => ! $isTemplate,
            'uuid' => Str::uuid(),
        ]);

        return to_route('shopping-lists.show', $shoppingList);
    }

    public function show(ShoppingList $shoppingList): Response
    {
        $shoppingList->load('items.addedBy');

        $categoryOrder = array_map(
            fn (ShoppingItemCategory $c) => $c->value,
            ShoppingItemCategory::cases()
        );

        $uncheckedItems = $shoppingList->items
            ->where('is_checked', false)
            ->sortBy(fn ($item) => array_search($item->category->value, $categoryOrder))
            ->groupBy(fn ($item) => $item->category->value);

        $checkedItems = $shoppingList->items
            ->where('is_checked', true)
            ->values();

        $categories = collect(ShoppingItemCategory::cases())->map(fn (ShoppingItemCategory $c) => [
            'value' => $c->value,
            'label' => $c->label(),
        ]);

        return Inertia::render('Shopping/Show', [
            'shoppingList' => $shoppingList,
            'uncheckedItemsByCategory' => $uncheckedItems->map->values(),
            'checkedItems' => $checkedItems,
            'categories' => $categories,
        ]);
    }

    public function update(UpdateShoppingListRequest $request, ShoppingList $shoppingList): RedirectResponse
    {
        $shoppingList->update($request->validated());

        return to_route('shopping-lists.show', $shoppingList);
    }

    public function destroy(ShoppingList $shoppingList): RedirectResponse
    {
        $shoppingList->delete();

        return to_route('shopping-lists.index');
    }

    public function duplicate(ShoppingList $shoppingList): RedirectResponse
    {
        $newList = ShoppingList::create([
            'name' => $shoppingList->name,
            'is_template' => false,
            'is_active' => true,
            'uuid' => Str::uuid(),
        ]);

        $shoppingList->items->each(function ($item) use ($newList) {
            $newList->items()->create([
                'name' => $item->name,
                'category' => $item->category,
                'is_checked' => false,
                'added_by' => $item->added_by,
                'uuid' => Str::uuid(),
            ]);
        });

        return to_route('shopping-lists.show', $newList);
    }
}
