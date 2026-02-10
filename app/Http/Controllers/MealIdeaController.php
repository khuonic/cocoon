<?php

namespace App\Http\Controllers;

use App\Http\Requests\MealIdea\StoreMealIdeaRequest;
use App\Http\Requests\MealIdea\UpdateMealIdeaRequest;
use App\Models\MealIdea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class MealIdeaController extends Controller
{
    public function store(StoreMealIdeaRequest $request): RedirectResponse
    {
        MealIdea::create([
            ...$request->validated(),
            'uuid' => Str::uuid(),
            'created_by' => auth()->id(),
        ]);

        return to_route('meal-plans.index');
    }

    public function update(UpdateMealIdeaRequest $request, MealIdea $mealIdea): RedirectResponse
    {
        $mealIdea->update($request->validated());

        return to_route('meal-plans.index');
    }

    public function destroy(MealIdea $mealIdea): RedirectResponse
    {
        $mealIdea->delete();

        return to_route('meal-plans.index');
    }
}
