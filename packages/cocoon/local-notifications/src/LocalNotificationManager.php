<?php

namespace Cocoon\LocalNotifications;

use DateTimeInterface;

class LocalNotificationManager
{
    public function schedule(string $id, string $title, string $body, DateTimeInterface $triggerAt): void
    {
        if (! function_exists('nativephp_call')) {
            return;
        }

        nativephp_call('LocalNotification.Schedule', json_encode([
            'id' => $id,
            'title' => $title,
            'body' => $body,
            'trigger_at' => $triggerAt->format(DateTimeInterface::ATOM),
        ]));
    }

    public function cancel(string $id): void
    {
        if (! function_exists('nativephp_call')) {
            return;
        }

        nativephp_call('LocalNotification.Cancel', json_encode(['id' => $id]));
    }

    public function cancelAll(): void
    {
        if (! function_exists('nativephp_call')) {
            return;
        }

        nativephp_call('LocalNotification.CancelAll', json_encode([]));
    }
}
