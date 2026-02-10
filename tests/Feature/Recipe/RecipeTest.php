<?php

use App\Models\Recipe;
use App\Models\User;

test('guests cannot access recipes create', function () {
    $this->get(route('recipes.create'))->assertRedirect('/login');
});

test('create form is accessible', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('recipes.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Recipes/Create')
            ->has('availableTags')
        );
});

test('store creates a recipe with uuid, ingredients and steps', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('recipes.store'), [
            'title' => 'Risotto champignons',
            'description' => 'Un classique italien',
            'url' => 'https://example.com/risotto',
            'prep_time' => 15,
            'cook_time' => 30,
            'servings' => 2,
            'tags' => ['comfort', 'gourmand'],
            'ingredients' => [
                ['name' => 'Riz arborio', 'quantity' => '200', 'unit' => 'g'],
                ['name' => 'Champignons', 'quantity' => '150', 'unit' => 'g'],
            ],
            'steps' => [
                ['instruction' => 'Faire revenir les champignons'],
                ['instruction' => 'Ajouter le riz et le bouillon'],
            ],
        ])
        ->assertRedirect();

    $recipe = Recipe::query()->where('title', 'Risotto champignons')->first();
    expect($recipe)->not->toBeNull();
    expect($recipe->uuid)->not->toBeNull();
    expect($recipe->created_by)->toBe($user->id);
    expect($recipe->ingredients)->toHaveCount(2);
    expect($recipe->steps)->toHaveCount(2);
});

test('store validates title is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('recipes.store'), [
            'title' => '',
        ])
        ->assertSessionHasErrors(['title']);
});

test('store validates ingredient name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('recipes.store'), [
            'title' => 'Test',
            'ingredients' => [
                ['name' => '', 'quantity' => '100', 'unit' => 'g'],
            ],
        ])
        ->assertSessionHasErrors(['ingredients.0.name']);
});

test('store validates step instruction is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('recipes.store'), [
            'title' => 'Test',
            'steps' => [
                ['instruction' => ''],
            ],
        ])
        ->assertSessionHasErrors(['steps.0.instruction']);
});

test('show displays recipe with details', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->withDetails()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('recipes.show', $recipe))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Recipes/Show')
            ->has('recipe')
            ->where('recipe.id', $recipe->id)
            ->has('recipe.ingredients', 3)
            ->has('recipe.steps', 3)
        );
});

test('edit displays the form', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('recipes.edit', $recipe))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Recipes/Edit')
            ->has('recipe')
            ->has('availableTags')
        );
});

test('update modifies and resyncs ingredients', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->withDetails()->create(['title' => 'Ancien titre', 'created_by' => $user->id]);

    $this->actingAs($user)
        ->put(route('recipes.update', $recipe), [
            'title' => 'Nouveau titre',
            'description' => null,
            'url' => null,
            'prep_time' => 10,
            'cook_time' => 20,
            'servings' => 4,
            'tags' => ['rapide'],
            'ingredients' => [
                ['name' => 'Nouvel ingrédient', 'quantity' => '1', 'unit' => 'pièce'],
            ],
            'steps' => [
                ['instruction' => 'Nouvelle étape'],
            ],
        ])
        ->assertRedirect();

    $recipe->refresh();
    expect($recipe->title)->toBe('Nouveau titre');
    expect($recipe->ingredients)->toHaveCount(1);
    expect($recipe->ingredients->first()->name)->toBe('Nouvel ingrédient');
    expect($recipe->steps)->toHaveCount(1);
});

test('destroy deletes recipe with cascade', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->withDetails()->create(['created_by' => $user->id]);
    $recipeId = $recipe->id;

    $this->actingAs($user)
        ->delete(route('recipes.destroy', $recipe))
        ->assertRedirect(route('meal-plans.index'));

    $this->assertDatabaseMissing('recipes', ['id' => $recipeId]);
    $this->assertDatabaseMissing('recipe_ingredients', ['recipe_id' => $recipeId]);
    $this->assertDatabaseMissing('recipe_steps', ['recipe_id' => $recipeId]);
});

test('store works without ingredients or steps', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('recipes.store'), [
            'title' => 'Recette simple',
            'tags' => [],
        ])
        ->assertRedirect();

    $recipe = Recipe::query()->where('title', 'Recette simple')->first();
    expect($recipe)->not->toBeNull();
    expect($recipe->ingredients)->toHaveCount(0);
    expect($recipe->steps)->toHaveCount(0);
});

test('ingredients respect array order via sort_order', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('recipes.store'), [
            'title' => 'Test order',
            'ingredients' => [
                ['name' => 'Premier', 'quantity' => null, 'unit' => null],
                ['name' => 'Deuxième', 'quantity' => null, 'unit' => null],
                ['name' => 'Troisième', 'quantity' => null, 'unit' => null],
            ],
            'steps' => [],
        ]);

    $recipe = Recipe::query()->where('title', 'Test order')->first();
    $ingredients = $recipe->ingredients;

    expect($ingredients[0]->name)->toBe('Premier');
    expect($ingredients[0]->sort_order)->toBe(0);
    expect($ingredients[1]->name)->toBe('Deuxième');
    expect($ingredients[1]->sort_order)->toBe(1);
    expect($ingredients[2]->name)->toBe('Troisième');
    expect($ingredients[2]->sort_order)->toBe(2);
});
