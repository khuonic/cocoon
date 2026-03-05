<?php

namespace App\Models;

use App\Enums\SyncAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $syncable_type
 * @property string $syncable_uuid
 * @property SyncAction $action
 * @property array<array-key, mixed>|null $payload
 * @property \Carbon\CarbonImmutable|null $synced_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 *
 * @method static Builder<static>|SyncLog newModelQuery()
 * @method static Builder<static>|SyncLog newQuery()
 * @method static Builder<static>|SyncLog pending()
 * @method static Builder<static>|SyncLog query()
 * @method static Builder<static>|SyncLog synced()
 * @method static Builder<static>|SyncLog whereAction($value)
 * @method static Builder<static>|SyncLog whereCreatedAt($value)
 * @method static Builder<static>|SyncLog whereId($value)
 * @method static Builder<static>|SyncLog wherePayload($value)
 * @method static Builder<static>|SyncLog whereSyncableType($value)
 * @method static Builder<static>|SyncLog whereSyncableUuid($value)
 * @method static Builder<static>|SyncLog whereSyncedAt($value)
 * @method static Builder<static>|SyncLog whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class SyncLog extends Model
{
    protected $fillable = [
        'syncable_type',
        'syncable_uuid',
        'action',
        'payload',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => SyncAction::class,
            'payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<SyncLog>  $query
     * @return Builder<SyncLog>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('synced_at');
    }

    /**
     * @param  Builder<SyncLog>  $query
     * @return Builder<SyncLog>
     */
    public function scopeSynced(Builder $query): Builder
    {
        return $query->whereNotNull('synced_at');
    }
}
