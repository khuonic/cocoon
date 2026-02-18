<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Todo extends Model
{
    /** @use HasFactory<\Database\Factories\TodoFactory> */
    use HasFactory;

    use Syncable;

    protected $fillable = [
        'uuid',
        'todo_list_id',
        'title',
        'is_done',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function todoList(): BelongsTo
    {
        return $this->belongsTo(TodoList::class);
    }

    /** @param Builder<Todo> $query */
    public function scopePending(Builder $query): void
    {
        $query->where('is_done', false);
    }

    /** @param Builder<Todo> $query */
    public function scopeDone(Builder $query): void
    {
        $query->where('is_done', true);
    }
}
