<?php

namespace App\Http\Controllers;

use App\Models\Birthday;
use App\Models\CalendarEvent;
use App\Models\Joke;
use App\Models\SweetMessage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $userId = auth()->id();

        $sweetMessage = SweetMessage::query()
            ->where('user_id', '!=', $userId)
            ->first();

        $mySweetMessage = SweetMessage::query()
            ->where('user_id', $userId)
            ->first();

        $events = CalendarEvent::query()
            ->whereDate('starts_at', today())
            ->orderBy('all_day')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (CalendarEvent $e) => [
                'type' => 'event',
                'title' => $e->title,
                'time' => $e->all_day ? null : $e->starts_at->format('H:i'),
                'color' => $e->category->color(),
            ]);

        $birthdays = Birthday::query()
            ->whereMonth('date', now()->month)
            ->whereDay('date', now()->day)
            ->get()
            ->map(fn (Birthday $b) => [
                'type' => 'birthday',
                'title' => 'Anniversaire de '.$b->name,
                'time' => null,
                'color' => '#EC4899',
                'age' => $b->age,
            ]);

        $allItems = $events->concat($birthdays);

        $jokeCount = Joke::query()->count();
        $joke = $jokeCount > 0
            ? Joke::query()->skip(now()->dayOfYear % $jokeCount)->first()
            : null;

        return Inertia::render('Dashboard', [
            'sweetMessage' => $sweetMessage,
            'mySweetMessage' => $mySweetMessage,
            'todayItems' => $allItems->take(5)->values(),
            'todayItemsCount' => $allItems->count(),
            'joke' => $joke,
        ]);
    }
}
