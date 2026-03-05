<?php

namespace App\Models;

use App\Enums\ShoppingItemCategory;
use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $shopping_list_id
 * @property string $name
 * @property ShoppingItemCategory|null $category
 * @property bool $is_checked
 * @property int $added_by
 * @property string $uuid
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User $addedBy
 * @property-read \App\Models\ShoppingList $shoppingList
 *
 * @method static \Database\Factories\ShoppingItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingItem whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingItem whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingItem whereIsChecked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingItem whereShoppingListId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingItem whereUuid($value)
 *
 * @mixin \Eloquent
 */
class ShoppingItem extends Model
{
    /** @use HasFactory<\Database\Factories\ShoppingItemFactory> */
    use HasFactory;

    use Syncable;

    protected $fillable = [
        'shopping_list_id',
        'name',
        'category',
        'is_checked',
        'added_by',
        'uuid',
    ];

    protected function casts(): array
    {
        return [
            'category' => ShoppingItemCategory::class,
            'is_checked' => 'boolean',
        ];
    }

    public function shoppingList(): BelongsTo
    {
        return $this->belongsTo(ShoppingList::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
