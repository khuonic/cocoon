<?php

namespace App\Models;

use App\Enums\ShoppingItemCategory;
use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
