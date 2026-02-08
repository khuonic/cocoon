<?php

namespace App\Models;

use App\Enums\RecurrenceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Todo extends Model
{
    /** @use HasFactory<\Database\Factories\TodoFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'is_personal',
        'assigned_to',
        'created_by',
        'due_date',
        'recurrence_type',
        'recurrence_day',
        'is_done',
        'completed_at',
        'uuid',
    ];

    protected function casts(): array
    {
        return [
            'is_personal' => 'boolean',
            'is_done' => 'boolean',
            'due_date' => 'date',
            'recurrence_type' => RecurrenceType::class,
            'completed_at' => 'datetime',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
