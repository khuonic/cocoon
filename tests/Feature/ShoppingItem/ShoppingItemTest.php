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
        ])
        ->assertRedirect(route('shopping-lists.show', $list));

    $this->assertDatabaseHas('shopping_items', [
        'shopping_list_id' => $list->id,
        'name' => 'Bananes',
        'category' => 'fruits_legumes',
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

test('update modifies name and category', function () {
    $user = User::factory()->create();
    $list = ShoppingList::factory()->create();
    $item = ShoppingItem::factory()->create([
        'shopping_list_id' => $list->id,
        'name' => 'Ancien nom',
        'category' => 'autre',
        'added_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->put(route('shopping-items.update', $item), [
            'name' => 'Nouveau nom',
            'category' => 'frais',
        ])
        ->assertRedirect(route('shopping-lists.show', $list));

    $item->refresh();
    expect($item->name)->toBe('Nouveau nom');
    expect($item->category->value)->toBe('frais');
});

test('update validates name is required', function () {
    $user = User::factory()->create();
    $item = ShoppingItem::factory()->create(['added_by' => $user->id]);

    $this->actingAs($user)
        ->put(route('shopping-items.update', $item), [
            'name' => '',
            'category' => 'frais',
        ])
        ->assertSessionHasErrors('name');
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
