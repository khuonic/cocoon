<?php

namespace App\Models;

use App\Enums\BookmarkCategory;
use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bookmark extends Model
{
    /** @use HasFactory<\Database\Factories\BookmarkFactory> */
    use HasFactory;

    use Syncable;

    protected $fillable = [
        'url',
        'title',
        'description',
        'category',
        'is_favorite',
        'show_on_dashboard',
        'added_by',
        'uuid',
    ];

    protected function casts(): array
    {
        return [
            'category' => BookmarkCategory::class,
            'is_favorite' => 'boolean',
            'show_on_dashboard' => 'boolean',
        ];
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
