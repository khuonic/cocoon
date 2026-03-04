<?php

namespace App\Models;

use App\Enums\EventCategory;
use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    /** @use HasFactory<\Database\Factories\CalendarEventFactory> */
    use HasFactory;

    use Syncable;

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'location',
        'category',
        'starts_at',
        'ends_at',
        'all_day',
        'is_personal',
        'user_id',
        'reminder_before',
    ];

    protected function casts(): array
    {
        return [
            'category' => EventCategory::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'boolean',
            'is_personal' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
