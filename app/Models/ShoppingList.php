<?php

namespace App\Models;

use App\Traits\Syncable;
use Carbon\CarbonImmutable;
use Database\Factories\ShoppingListFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property bool $is_active
 * @property string $uuid
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, ShoppingItem> $checkedItems
 * @property-read int|null $checked_items_count
 * @property-read Collection<int, ShoppingItem> $items
 * @property-read int|null $items_count
 * @property-read Collection<int, ShoppingItem> $uncheckedItems
 * @property-read int|null $unchecked_items_count
 *
 * @method static \Database\Factories\ShoppingListFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingList query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingList whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingList whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingList whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingList whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingList whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingList whereUuid($value)
 *
 * @mixin \Eloquent
 */
class ShoppingList extends Model
{
    /** @use HasFactory<ShoppingListFactory> */
    use HasFactory;

    use Syncable;

    protected $fillable = [
        'name',
        'is_active',
        'uuid',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShoppingItem::class);
    }

    public function uncheckedItems(): HasMany
    {
        return $this->hasMany(ShoppingItem::class)->where('is_checked', false);
    }

    public function checkedItems(): HasMany
    {
        return $this->hasMany(ShoppingItem::class)->where('is_checked', true);
    }
}
