<?php

namespace App\Models;

use App\Enums\NoteColor;
use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    /** @use HasFactory<\Database\Factories\NoteFactory> */
    use HasFactory;

    use Syncable;

    protected $fillable = [
        'title',
        'content',
        'is_pinned',
        'color',
        'created_by',
        'uuid',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'color' => NoteColor::class,
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
