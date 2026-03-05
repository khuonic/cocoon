<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $recipe_id
 * @property string $instruction
 * @property int $sort_order
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Recipe $recipe
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep whereInstruction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep whereRecipeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class RecipeStep extends Model
{
    protected $fillable = [
        'recipe_id',
        'instruction',
        'sort_order',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
