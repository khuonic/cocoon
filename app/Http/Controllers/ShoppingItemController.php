<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShoppingItem\StoreShoppingItemRequest;
use App\Http\Requests\ShoppingItem\UpdateShoppingItemRequest;
use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ShoppingItemController extends Controller
{
    public function store(StoreShoppingItemRequest $request, ShoppingList $shoppingList): RedirectResponse
    {
        $shoppingList->items()->create([
            ...$request->validated(),
            'added_by' => $request->user()->id,
            'uuid' => Str::uuid(),
        ]);

        return to_route('shopping-lists.show', $shoppingList);
    }

    public function update(UpdateShoppingItemRequest $request, ShoppingItem $shoppingItem): RedirectResponse
    {
        $shoppingItem->update($request->validated());

        return to_route('shopping-lists.show', $shoppingItem->shopping_list_id);
    }

    public function toggleCheck(ShoppingItem $shoppingItem): RedirectResponse
    {
        $shoppingItem->update(['is_checked' => ! $shoppingItem->is_checked]);

        return to_route('shopping-lists.show', $shoppingItem->shopping_list_id);
    }

    public function destroy(ShoppingItem $shoppingItem): RedirectResponse
    {
        $listId = $shoppingItem->shopping_list_id;
        $shoppingItem->delete();

        return to_route('shopping-lists.show', $listId);
    }
}
