<?php

use App\Enums\BookmarkCategory;
use App\Models\Bookmark;
use App\Models\User;

test('guests are redirected to login', function () {
    $this->get(route('bookmarks.index'))->assertRedirect('/login');
});

test('authenticated users can view the bookmarks index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Bookmarks/Index')
            ->has('bookmarks')
            ->has('categories')
        );
});

test('favorites appear first', function () {
    $user = User::factory()->create();
    Bookmark::factory()->create(['title' => 'Normal', 'is_favorite' => false, 'added_by' => $user->id]);
    Bookmark::factory()->favorite()->create(['title' => 'Favori', 'added_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertInertia(fn ($page) => $page
            ->has('bookmarks', 2)
            ->where('bookmarks.0.title', 'Favori')
            ->where('bookmarks.1.title', 'Normal')
        );
});

test('store creates a bookmark with uuid and added_by', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('bookmarks.store'), [
            'url' => 'https://example.com',
            'title' => 'Mon bookmark',
            'description' => null,
            'category' => null,
            'is_favorite' => false,
        ])
        ->assertRedirect(route('bookmarks.index'));

    $this->assertDatabaseHas('bookmarks', [
        'url' => 'https://example.com',
        'title' => 'Mon bookmark',
        'added_by' => $user->id,
    ]);

    $bookmark = Bookmark::query()->where('title', 'Mon bookmark')->first();
    expect($bookmark->uuid)->not->toBeNull();
});

test('store validates url is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('bookmarks.store'), [
            'url' => '',
            'title' => 'Un titre',
            'is_favorite' => false,
        ])
        ->assertSessionHasErrors(['url']);
});

test('store validates title is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('bookmarks.store'), [
            'url' => 'https://example.com',
            'title' => '',
            'is_favorite' => false,
        ])
        ->assertSessionHasErrors(['title']);
});

test('store validates url format', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('bookmarks.store'), [
            'url' => 'not-a-url',
            'title' => 'Un titre',
            'is_favorite' => false,
        ])
        ->assertSessionHasErrors(['url']);
});

test('store validates category must be a valid enum value', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('bookmarks.store'), [
            'url' => 'https://example.com',
            'title' => 'Un titre',
            'category' => 'invalid-category',
            'is_favorite' => false,
        ])
        ->assertSessionHasErrors(['category']);
});

test('store creates a bookmark with category', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('bookmarks.store'), [
            'url' => 'https://example.com',
            'title' => 'Resto favori',
            'description' => 'Super resto',
            'category' => 'resto',
            'is_favorite' => false,
        ])
        ->assertRedirect(route('bookmarks.index'));

    $bookmark = Bookmark::query()->where('title', 'Resto favori')->first();
    expect($bookmark->category)->toBe(BookmarkCategory::Resto);
    expect($bookmark->description)->toBe('Super resto');
});

test('update modifies a bookmark', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->create(['title' => 'Ancien titre', 'added_by' => $user->id]);

    $this->actingAs($user)
        ->put(route('bookmarks.update', $bookmark), [
            'url' => 'https://new-url.com',
            'title' => 'Nouveau titre',
            'description' => 'Nouvelle description',
            'category' => 'voyage',
            'is_favorite' => true,
        ])
        ->assertRedirect(route('bookmarks.index'));

    $bookmark->refresh();
    expect($bookmark->url)->toBe('https://new-url.com');
    expect($bookmark->title)->toBe('Nouveau titre');
    expect($bookmark->description)->toBe('Nouvelle description');
    expect($bookmark->category)->toBe(BookmarkCategory::Voyage);
    expect($bookmark->is_favorite)->toBeTrue();
});

test('toggle favorite inverts is_favorite', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->create(['is_favorite' => false, 'added_by' => $user->id]);

    $this->actingAs($user)
        ->patch(route('bookmarks.toggle-favorite', $bookmark))
        ->assertRedirect(route('bookmarks.index'));

    expect($bookmark->fresh()->is_favorite)->toBeTrue();
});

test('destroy deletes a bookmark', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->create(['added_by' => $user->id]);

    $this->actingAs($user)
        ->delete(route('bookmarks.destroy', $bookmark))
        ->assertRedirect(route('bookmarks.index'));

    $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark->id]);
});
