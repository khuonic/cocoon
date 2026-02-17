<?php

namespace App\Traits;

use App\Enums\SyncAction;
use App\Models\SyncLog;

trait Syncable
{
    public bool $isSyncing = false;

    public static function bootSyncable(): void
    {
        static::created(function ($model): void {
            $model->queueSync(SyncAction::Created);
        });

        static::updated(function ($model): void {
            $model->queueSync(SyncAction::Updated);
        });

        static::deleted(function ($model): void {
            $model->queueSync(SyncAction::Deleted);
        });
    }

    protected function queueSync(SyncAction $action): void
    {
        if ($this->isSyncing) {
            return;
        }

        SyncLog::create([
            'syncable_type' => $this->getMorphClass(),
            'syncable_uuid' => $this->uuid,
            'action' => $action,
            'payload' => $action === SyncAction::Deleted ? null : $this->toArray(),
        ]);
    }
}
