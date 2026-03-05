<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $recipe_id
 * @property string $name
 * @property string|null $quantity
 * @property string|null $unit
 * @property int $sort_order
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Recipe $recipe
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereRecipeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class RecipeIngredient extends Model
{
    protected $fillable = [
        'recipe_id',
        'name',
        'quantity',
        'unit',
        'sort_order',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
