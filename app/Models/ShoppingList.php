<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShoppingList extends Model
{
    /** @use HasFactory<\Database\Factories\ShoppingListFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'is_template',
        'is_active',
        'uuid',
    ];

    protected function casts(): array
    {
        return [
            'is_template' => 'boolean',
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
