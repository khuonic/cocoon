<?php

namespace App\Models;

use App\Enums\EventCategory;
use App\Traits\Syncable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property string $title
 * @property string|null $description
 * @property string|null $location
 * @property EventCategory $category
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property bool $all_day
 * @property bool $is_personal
 * @property int|null $user_id
 * @property int|null $reminder_before
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $user
 *
 * @method static \Database\Factories\CalendarEventFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarEvent whereAllDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarEvent whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarEvent whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarEvent whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarEvent whereIsPersonal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarEvent whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarEvent whereReminderBefore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarEvent whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarEvent whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarEvent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarEvent whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CalendarEvent whereUuid($value)
 *
 * @mixin \Eloquent
 */
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
