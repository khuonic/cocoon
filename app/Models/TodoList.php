<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $uuid
 * @property string $title
 * @property bool $is_personal
 * @property int|null $user_id
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Todo> $todos
 * @property-read int|null $todos_count
 * @property-read \App\Models\User|null $user
 *
 * @method static \Database\Factories\TodoListFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TodoList newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TodoList newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TodoList query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TodoList whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TodoList whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TodoList whereIsPersonal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TodoList whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TodoList whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TodoList whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TodoList whereUuid($value)
 *
 * @mixin \Eloquent
 */
class TodoList extends Model
{
    /** @use HasFactory<\Database\Factories\TodoListFactory> */
    use HasFactory;

    use Syncable;

    protected $fillable = [
        'uuid',
        'title',
        'is_personal',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_personal' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }
}
