<?php

use App\Models\MealIdea;
use App\Models\User;

test('guests are redirected to login', function () {
    $this->get(route('meal-plans.index'))->assertRedirect('/login');
});

test('authenticated users can view the meal index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('meal-plans.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Meals/Index')
            ->has('ideas')
            ->has('recipes')
            ->has('availableTags')
        );
});

test('index returns the correct number of ideas', function () {
    $user = User::factory()->create();
    MealIdea::factory()->count(3)->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('meal-plans.index'))
        ->assertInertia(fn ($page) => $page->has('ideas', 3));
});

test('store creates an idea with uuid', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('meal-ideas.store'), [
            'name' => 'Pâtes carbo',
            'description' => 'La vraie recette italienne',
            'url' => null,
            'tags' => ['rapide', 'gourmand'],
        ])
        ->assertRedirect(route('meal-plans.index'));

    $this->assertDatabaseHas('meal_ideas', [
        'name' => 'Pâtes carbo',
        'description' => 'La vraie recette italienne',
        'created_by' => $user->id,
    ]);

    $idea = MealIdea::query()->where('name', 'Pâtes carbo')->first();
    expect($idea->uuid)->not->toBeNull();
    expect($idea->tags)->toBe(['rapide', 'gourmand']);
});

test('store validates name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('meal-ideas.store'), [
            'name' => '',
            'tags' => [],
        ])
        ->assertSessionHasErrors(['name']);
});

test('store validates tags contain valid values', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('meal-ideas.store'), [
            'name' => 'Test',
            'tags' => ['invalid_tag'],
        ])
        ->assertSessionHasErrors(['tags.0']);
});

test('store validates url format', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('meal-ideas.store'), [
            'name' => 'Test',
            'url' => 'not-a-url',
        ])
        ->assertSessionHasErrors(['url']);
});

test('store assigns created_by automatically', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('meal-ideas.store'), [
            'name' => 'Auto assigned',
            'tags' => [],
        ]);

    expect(MealIdea::query()->first()->created_by)->toBe($user->id);
});

test('update modifies an idea', function () {
    $user = User::factory()->create();
    $idea = MealIdea::factory()->create(['name' => 'Ancien nom', 'created_by' => $user->id]);

    $this->actingAs($user)
        ->put(route('meal-ideas.update', $idea), [
            'name' => 'Nouveau nom',
            'description' => 'Nouvelle description',
            'url' => 'https://example.com',
            'tags' => ['vege'],
        ])
        ->assertRedirect(route('meal-plans.index'));

    $idea->refresh();
    expect($idea->name)->toBe('Nouveau nom');
    expect($idea->description)->toBe('Nouvelle description');
    expect($idea->url)->toBe('https://example.com');
    expect($idea->tags)->toBe(['vege']);
});

test('destroy deletes an idea', function () {
    $user = User::factory()->create();
    $idea = MealIdea::factory()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->delete(route('meal-ideas.destroy', $idea))
        ->assertRedirect(route('meal-plans.index'));

    $this->assertDatabaseMissing('meal_ideas', ['id' => $idea->id]);
});
