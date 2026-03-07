<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property \Carbon\CarbonImmutable $date
 * @property int $added_by
 * @property string $uuid
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property int|null $reminder_days_before
 * @property-read \App\Models\User $addedBy
 * @property-read mixed $age
 *
 * @method static \Database\Factories\BirthdayFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Birthday newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Birthday newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Birthday query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Birthday whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Birthday whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Birthday whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Birthday whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Birthday whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Birthday whereReminderDaysBefore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Birthday whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Birthday whereUuid($value)
 *
 * @mixin \Eloquent
 */
class Birthday extends Model
{
    /** @use HasFactory<\Database\Factories\BirthdayFactory> */
    use HasFactory;

    use Syncable;

    protected $fillable = [
        'name',
        'date',
        'reminder_days_before',
        'added_by',
        'uuid',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'reminder_days_before' => 'integer',
        ];
    }

    protected function age(): Attribute
    {
        return Attribute::get(fn () => (int) now()->year - (int) $this->date->year);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
