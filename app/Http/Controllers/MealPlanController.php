<?php

namespace App\Http\Controllers;

use App\Enums\MealTag;
use App\Models\MealIdea;
use App\Models\Recipe;
use Inertia\Inertia;
use Inertia\Response;

class MealPlanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Meals/Index', [
            'ideas' => MealIdea::query()->with('creator')->latest()->get(),
            'recipes' => Recipe::query()->with('creator')->latest()->get(),
            'availableTags' => array_map(
                fn (MealTag $tag) => ['value' => $tag->value, 'label' => $tag->label()],
                MealTag::cases()
            ),
        ]);
    }
}
