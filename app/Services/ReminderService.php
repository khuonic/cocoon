<?php

namespace App\Services;

use App\Models\Birthday;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use Cocoon\LocalNotifications\Facades\LocalNotification;

class ReminderService
{
    public function scheduleForCalendarEvent(CalendarEvent $event): void
    {
        if ($event->reminder_before === null) {
            return;
        }

        $triggerAt = Carbon::parse($event->starts_at)->subMinutes((int) $event->reminder_before);

        if ($triggerAt->isPast()) {
            return;
        }

        LocalNotification::schedule(
            id: 'calendar_'.$event->uuid,
            title: $event->title,
            body: $event->location ? '📍 '.$event->location : $event->category->label(),
            triggerAt: $triggerAt,
        );
    }

    public function cancelForCalendarEvent(CalendarEvent $event): void
    {
        LocalNotification::cancel('calendar_'.$event->uuid);
    }

    public function scheduleForBirthday(Birthday $birthday): void
    {
        if ($birthday->reminder_days_before === null) {
            return;
        }

        $triggerAt = $this->nextBirthdayTrigger($birthday);

        if ($triggerAt === null || $triggerAt->isPast()) {
            return;
        }

        $body = $birthday->reminder_days_before === 0
            ? "Aujourd'hui, c'est l'anniversaire de {$birthday->name} !"
            : "Demain, c'est l'anniversaire de {$birthday->name} !";

        LocalNotification::schedule(
            id: 'birthday_'.$birthday->uuid,
            title: '🎂 Anniversaire de '.$birthday->name,
            body: $body,
            triggerAt: $triggerAt,
        );
    }

    public function cancelForBirthday(Birthday $birthday): void
    {
        LocalNotification::cancel('birthday_'.$birthday->uuid);
    }

    private function nextBirthdayTrigger(Birthday $birthday): ?Carbon
    {
        if ($birthday->reminder_days_before === null) {
            return null;
        }

        $now = Carbon::now();
        $thisYear = Carbon::createFromDate($now->year, $birthday->date->month, $birthday->date->day)
            ->subDays($birthday->reminder_days_before)
            ->startOfDay()
            ->addHours(9);

        return $thisYear->isPast() ? $thisYear->addYear() : $thisYear;
    }
}
