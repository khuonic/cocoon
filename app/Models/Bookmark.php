<?php

namespace App\Models;

use App\Enums\BookmarkCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bookmark extends Model
{
    /** @use HasFactory<\Database\Factories\BookmarkFactory> */
    use HasFactory;

    protected $fillable = [
        'url',
        'title',
        'description',
        'category',
        'is_favorite',
        'added_by',
        'uuid',
    ];

    protected function casts(): array
    {
        return [
            'category' => BookmarkCategory::class,
            'is_favorite' => 'boolean',
        ];
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
