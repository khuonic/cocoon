<?php

namespace App\Models;

use App\Enums\SyncAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
