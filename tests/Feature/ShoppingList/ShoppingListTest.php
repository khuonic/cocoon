<?php

use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use App\Models\User;

test('guests are redirected to login', function () {
    $this->get(route('shopping-lists.index'))->assertRedirect('/login');
});

test('authenticated users can view the shopping list index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('shopping-lists.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Shopping/Index'));
});

test('index displays lists with counts', function () {
    $user = User::factory()->create();
    $list = ShoppingList::factory()->create(['name' => 'Courses semaine']);
    ShoppingItem::factory()->count(3)->create(['shopping_list_id' => $list->id, 'added_by' => $user->id]);
    ShoppingItem::factory()->checked()->create(['shopping_list_id' => $list->id, 'added_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('shopping-lists.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Shopping/Index')
            ->has('shoppingLists', 1)
            ->where('shoppingLists.0.name', 'Courses semaine')
            ->where('shoppingLists.0.items_count', 4)
            ->where('shoppingLists.0.checked_items_count', 1)
            ->where('shoppingLists.0.unchecked_items_count', 3)
        );
});

test('create form is accessible', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('shopping-lists.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Shopping/Create'));
});

test('store creates a shopping list with uuid', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('shopping-lists.store'), [
            'name' => 'Ma liste',
            'is_template' => false,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('shopping_lists', [
        'name' => 'Ma liste',
        'is_template' => false,
        'is_active' => true,
    ]);

    $list = ShoppingList::query()->where('name', 'Ma liste')->first();
    expect($list->uuid)->not->toBeNull();
});

test('store validates required fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('shopping-lists.store'), [])
        ->assertSessionHasErrors(['name']);
});

test('store with is_template creates an inactive list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('shopping-lists.store'), [
            'name' => 'Template courses',
            'is_template' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('shopping_lists', [
        'name' => 'Template courses',
        'is_template' => true,
        'is_active' => false,
    ]);
});

test('show displays the list with items', function () {
    $user = User::factory()->create();
    $list = ShoppingList::factory()->create();
    ShoppingItem::factory()->create([
        'shopping_list_id' => $list->id,
        'added_by' => $user->id,
        'name' => 'Tomates',
        'category' => 'fruits_legumes',
    ]);

    $this->actingAs($user)
        ->get(route('shopping-lists.show', $list))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Shopping/Show')
            ->has('shoppingList')
            ->has('uncheckedItemsByCategory')
            ->has('checkedItems')
            ->has('categories')
        );
});

test('update modifies the list name', function () {
    $user = User::factory()->create();
    $list = ShoppingList::factory()->create(['name' => 'Ancien nom']);

    $this->actingAs($user)
        ->put(route('shopping-lists.update', $list), [
            'name' => 'Nouveau nom',
        ])
        ->assertRedirect(route('shopping-lists.show', $list));

    expect($list->fresh()->name)->toBe('Nouveau nom');
});

test('destroy deletes the list and its items', function () {
    $user = User::factory()->create();
    $list = ShoppingList::factory()->create();
    ShoppingItem::factory()->count(3)->create([
        'shopping_list_id' => $list->id,
        'added_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->delete(route('shopping-lists.destroy', $list))
        ->assertRedirect(route('shopping-lists.index'));

    $this->assertDatabaseMissing('shopping_lists', ['id' => $list->id]);
    $this->assertDatabaseCount('shopping_items', 0);
});

test('duplicate copies the list and its items', function () {
    $user = User::factory()->create();
    $list = ShoppingList::factory()->template()->create(['name' => 'Mon modèle']);
    ShoppingItem::factory()->count(2)->create([
        'shopping_list_id' => $list->id,
        'added_by' => $user->id,
    ]);
    ShoppingItem::factory()->checked()->create([
        'shopping_list_id' => $list->id,
        'added_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('shopping-lists.duplicate', $list))
        ->assertRedirect();

    $newList = ShoppingList::query()->where('id', '!=', $list->id)->first();
    expect($newList->name)->toBe('Mon modèle');
    expect($newList->is_template)->toBeFalse();
    expect($newList->is_active)->toBeTrue();
    expect($newList->items)->toHaveCount(3);
});

test('duplicate creates new uuids for items', function () {
    $user = User::factory()->create();
    $list = ShoppingList::factory()->template()->create();
    $item = ShoppingItem::factory()->create([
        'shopping_list_id' => $list->id,
        'added_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('shopping-lists.duplicate', $list));

    $newList = ShoppingList::query()->where('id', '!=', $list->id)->first();
    $newItem = $newList->items->first();

    expect($newList->uuid)->not->toBe($list->uuid);
    expect($newItem->uuid)->not->toBe($item->uuid);
});

test('duplicate sets all items as unchecked', function () {
    $user = User::factory()->create();
    $list = ShoppingList::factory()->template()->create();
    ShoppingItem::factory()->checked()->count(3)->create([
        'shopping_list_id' => $list->id,
        'added_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('shopping-lists.duplicate', $list));

    $newList = ShoppingList::query()->where('id', '!=', $list->id)->first();
    expect($newList->items->every(fn ($item) => ! $item->is_checked))->toBeTrue();
});
