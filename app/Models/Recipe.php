<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string|null $url
 * @property int|null $prep_time
 * @property int|null $cook_time
 * @property int|null $servings
 * @property array<array-key, mixed>|null $tags
 * @property int $created_by
 * @property string $uuid
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $image_path
 * @property-read \App\Models\User $creator
 * @property-read Collection<int, RecipeIngredient> $ingredients
 * @property-read int|null $ingredients_count
 * @property-read Collection<int, RecipeStep> $steps
 * @property-read int|null $steps_count
 *
 * @method static \Database\Factories\RecipeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereCookTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe wherePrepTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereServings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereUuid($value)
 *
 * @mixin \Eloquent
 */
class Recipe extends Model
{
    /** @use HasFactory<\Database\Factories\RecipeFactory> */
    use HasFactory;

    use Syncable;

    protected $fillable = [
        'title',
        'description',
        'url',
        'image_path',
        'prep_time',
        'cook_time',
        'servings',
        'tags',
        'created_by',
        'uuid',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'prep_time' => 'integer',
            'cook_time' => 'integer',
            'servings' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('sort_order');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RecipeStep::class)->orderBy('sort_order');
    }
}
