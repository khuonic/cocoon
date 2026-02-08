<?php

namespace App\Models;

use App\Enums\RecurrenceType;
use App\Enums\SplitType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    /** @use HasFactory<\Database\Factories\ExpenseFactory> */
    use HasFactory;

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
