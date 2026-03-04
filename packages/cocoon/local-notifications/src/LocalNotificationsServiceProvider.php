<?php

namespace Cocoon\LocalNotifications;

use Illuminate\Support\ServiceProvider;

class LocalNotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LocalNotificationManager::class, function () {
            return new LocalNotificationManager;
        });
    }
}
