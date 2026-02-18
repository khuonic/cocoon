<?php

namespace App\Http\Controllers;

use App\Enums\MealTag;
use App\Http\Requests\Recipe\StoreRecipeRequest;
use App\Http\Requests\Recipe\UpdateRecipeRequest;
use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Recipes/Create', [
            'availableTags' => $this->availableTags(),
        ]);
    }

    public function store(StoreRecipeRequest $request): RedirectResponse
    {
        $recipe = Recipe::create([
            ...$request->safe()->except(['ingredients', 'steps']),
            'uuid' => Str::uuid(),
            'created_by' => auth()->id(),
        ]);

        $this->syncIngredients($recipe, $request->validated('ingredients', []));
        $this->syncSteps($recipe, $request->validated('steps', []));

        return to_route('recipes.show', $recipe);
    }

    public function show(Recipe $recipe): Response
    {
        return Inertia::render('Recipes/Show', [
            'recipe' => $recipe->load(['creator', 'ingredients', 'steps']),
        ]);
    }

    public function edit(Recipe $recipe): Response
    {
        return Inertia::render('Recipes/Edit', [
            'recipe' => $recipe->load(['ingredients', 'steps']),
            'availableTags' => $this->availableTags(),
        ]);
    }

    public function update(UpdateRecipeRequest $request, Recipe $recipe): RedirectResponse
    {
        $recipe->update($request->safe()->except(['ingredients', 'steps']));

        $this->syncIngredients($recipe, $request->validated('ingredients', []));
        $this->syncSteps($recipe, $request->validated('steps', []));

        return to_route('recipes.show', $recipe);
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        $recipe->delete();

        return to_route('more');
    }

    /**
     * @param  array<int, array{name: string, quantity?: string|null, unit?: string|null}>  $ingredients
     */
    private function syncIngredients(Recipe $recipe, array $ingredients): void
    {
        $recipe->ingredients()->delete();

        foreach ($ingredients as $index => $ingredient) {
            $recipe->ingredients()->create([
                ...$ingredient,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param  array<int, array{instruction: string}>  $steps
     */
    private function syncSteps(Recipe $recipe, array $steps): void
    {
        $recipe->steps()->delete();

        foreach ($steps as $index => $step) {
            $recipe->steps()->create([
                ...$step,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function availableTags(): array
    {
        return array_map(
            fn (MealTag $tag) => ['value' => $tag->value, 'label' => $tag->label()],
            MealTag::cases()
        );
    }
}
