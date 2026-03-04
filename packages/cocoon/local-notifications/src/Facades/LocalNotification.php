<?php

namespace Cocoon\LocalNotifications\Facades;

use Cocoon\LocalNotifications\LocalNotificationManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void schedule(string $id, string $title, string $body, \DateTimeInterface $triggerAt)
 * @method static void cancel(string $id)
 * @method static void cancelAll()
 */
class LocalNotification extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LocalNotificationManager::class;
    }
}
