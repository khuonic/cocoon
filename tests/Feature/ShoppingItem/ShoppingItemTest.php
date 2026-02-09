<?php

use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use App\Models\User;

test('store adds an item to the list', function () {
    $user = User::factory()->create();
    $list = ShoppingList::factory()->create();

    $this->actingAs($user)
        ->post(route('shopping-items.store', $list), [
            'name' => 'Bananes',
            'category' => 'fruits_legumes',
            'quantity' => '1 kg',
        ])
        ->assertRedirect(route('shopping-lists.show', $list));

    $this->assertDatabaseHas('shopping_items', [
        'shopping_list_id' => $list->id,
        'name' => 'Bananes',
        'category' => 'fruits_legumes',
        'quantity' => '1 kg',
    ]);
});

test('store validates name and category', function () {
    $user = User::factory()->create();
    $list = ShoppingList::factory()->create();

    $this->actingAs($user)
        ->post(route('shopping-items.store', $list), [])
        ->assertSessionHasErrors(['name', 'category']);
});

test('store validates the category enum', function () {
    $user = User::factory()->create();
    $list = ShoppingList::factory()->create();

    $this->actingAs($user)
        ->post(route('shopping-items.store', $list), [
            'name' => 'Test',
            'category' => 'invalid_category',
        ])
        ->assertSessionHasErrors('category');
});

test('store assigns added_by to the current user', function () {
    $user = User::factory()->create();
    $list = ShoppingList::factory()->create();

    $this->actingAs($user)
        ->post(route('shopping-items.store', $list), [
            'name' => 'Lait',
            'category' => 'frais',
        ]);

    $item = ShoppingItem::query()->where('name', 'Lait')->first();
    expect($item->added_by)->toBe($user->id);
    expect($item->uuid)->not->toBeNull();
});

test('toggleCheck inverts is_checked', function () {
    $user = User::factory()->create();
    $item = ShoppingItem::factory()->create([
        'is_checked' => false,
        'added_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->patch(route('shopping-items.toggle-check', $item))
        ->assertRedirect();

    expect($item->fresh()->is_checked)->toBeTrue();

    $this->actingAs($user)
        ->patch(route('shopping-items.toggle-check', $item))
        ->assertRedirect();

    expect($item->fresh()->is_checked)->toBeFalse();
});

test('toggleFavorite inverts is_favorite', function () {
    $user = User::factory()->create();
    $item = ShoppingItem::factory()->create([
        'is_favorite' => false,
        'added_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->patch(route('shopping-items.toggle-favorite', $item))
        ->assertRedirect();

    expect($item->fresh()->is_favorite)->toBeTrue();

    $this->actingAs($user)
        ->patch(route('shopping-items.toggle-favorite', $item))
        ->assertRedirect();

    expect($item->fresh()->is_favorite)->toBeFalse();
});

test('destroy deletes the item', function () {
    $user = User::factory()->create();
    $list = ShoppingList::factory()->create();
    $item = ShoppingItem::factory()->create([
        'shopping_list_id' => $list->id,
        'added_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->delete(route('shopping-items.destroy', $item))
        ->assertRedirect(route('shopping-lists.show', $list));

    $this->assertDatabaseMissing('shopping_items', ['id' => $item->id]);
});
