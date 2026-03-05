<?php

namespace App\Models;

use App\Enums\NoteColor;
use App\Traits\Syncable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $title
 * @property string|null $content
 * @property bool $is_pinned
 * @property int $created_by
 * @property string $uuid
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property NoteColor|null $color
 * @property-read User $creator
 *
 * @method static \Database\Factories\NoteFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereIsPinned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Note whereUuid($value)
 *
 * @mixin \Eloquent
 */
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
