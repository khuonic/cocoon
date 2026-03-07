<?php

namespace App\Models;

use App\Enums\RecurrenceType;
use App\Enums\SplitType;
use App\Traits\Syncable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property numeric $amount
 * @property string $description
 * @property int $category_id
 * @property int $paid_by
 * @property SplitType $split_type
 * @property numeric|null $split_value
 * @property CarbonImmutable $date
 * @property bool $is_recurring
 * @property RecurrenceType|null $recurrence_type
 * @property CarbonImmutable|null $settled_at
 * @property string $uuid
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ExpenseCategory $category
 * @property-read User $payer
 *
 * @method static \Database\Factories\ExpenseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereIsRecurring($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense wherePaidBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereRecurrenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereSettledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereSplitType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereSplitValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Expense whereUuid($value)
 *
 * @mixin \Eloquent
 */
class Expense extends Model
{
    /** @use HasFactory<\Database\Factories\ExpenseFactory> */
    use HasFactory;

    use LogsActivity;
    use Syncable;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['description', 'amount', 'date', 'category_id', 'split_type', 'paid_by', 'is_recurring'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => $this->description);
    }

    protected $fillable = [
        'amount',
        'description',
        'category_id',
        'paid_by',
        'split_type',
        'split_value',
        'date',
        'is_recurring',
        'recurrence_type',
        'settled_at',
        'uuid',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'split_value' => 'decimal:2',
            'split_type' => SplitType::class,
            'recurrence_type' => RecurrenceType::class,
            'date' => 'date',
            'is_recurring' => 'boolean',
            'settled_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function isSettled(): bool
    {
        return $this->settled_at !== null;
    }
}
