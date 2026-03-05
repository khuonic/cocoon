<?php

namespace App\Models;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $content
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string $uuid
 * @property-read \App\Models\User $user
 *
 * @method static \Database\Factories\SweetMessageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SweetMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SweetMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SweetMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SweetMessage whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SweetMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SweetMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SweetMessage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SweetMessage whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SweetMessage whereUuid($value)
 *
 * @mixin \Eloquent
 */
class SweetMessage extends Model
{
    /** @use HasFactory<\Database\Factories\SweetMessageFactory> */
    use HasFactory;

    use Syncable;

    protected $fillable = [
        'user_id',
        'content',
        'uuid',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
