<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $todo_list_id
 * @property string $title
 * @property bool $is_done
 * @property \Carbon\CarbonImmutable|null $completed_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\TodoList $todoList
 *
 * @method static Builder<static>|Todo done()
 * @method static \Database\Factories\TodoFactory factory($count = null, $state = [])
 * @method static Builder<static>|Todo newModelQuery()
 * @method static Builder<static>|Todo newQuery()
 * @method static Builder<static>|Todo pending()
 * @method static Builder<static>|Todo query()
 * @method static Builder<static>|Todo whereCompletedAt($value)
 * @method static Builder<static>|Todo whereCreatedAt($value)
 * @method static Builder<static>|Todo whereId($value)
 * @method static Builder<static>|Todo whereIsDone($value)
 * @method static Builder<static>|Todo whereTitle($value)
 * @method static Builder<static>|Todo whereTodoListId($value)
 * @method static Builder<static>|Todo whereUpdatedAt($value)
 * @method static Builder<static>|Todo whereUuid($value)
 *
 * @mixin \Eloquent
 */
class Todo extends Model
{
    /** @use HasFactory<\Database\Factories\TodoFactory> */
    use HasFactory;

    use Syncable;

    protected $fillable = [
        'uuid',
        'todo_list_id',
        'title',
        'position',
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
