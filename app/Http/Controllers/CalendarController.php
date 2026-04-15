<?php

namespace App\Http\Controllers;

use App\Http\Requests\Calendar\StoreCalendarEventRequest;
use App\Http\Requests\Calendar\UpdateCalendarEventRequest;
use App\Models\Birthday;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\ReminderService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $month = $request->query('month')
            ? Carbon::parse($request->query('month').'-01')->startOfMonth()
            : Carbon::now()->startOfMonth();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $events = CalendarEvent::query()
            ->with('user')
            ->whereBetween('starts_at', [$startOfMonth, $endOfMonth])
            ->orWhere(fn ($q) => $q
                ->whereNotNull('ends_at')
                ->where('starts_at', '<=', $endOfMonth)
                ->where('ends_at', '>=', $startOfMonth)
            )
            ->get()
            ->map(fn (CalendarEvent $e) => [
                ...$e->toArray(),
                'category_color' => $e->category->color(),
                'category_label' => $e->category->label(),
            ]);

        $birthdays = Birthday::query()
            ->with('addedBy')
            ->get()
            ->filter(fn (Birthday $b) => (int) $b->date->format('m') === (int) $month->format('m'))
            ->map(fn (Birthday $b) => [
                ...$b->toArray(),
                'age' => $b->age,
                'day' => (int) $b->date->format('d'),
            ])
            ->values();

        $users = User::query()->select(['id', 'name'])->get();

        return Inertia::render('Calendar/Index', [
            'events' => $events,
            'birthdays' => $birthdays,
            'users' => $users,
            'currentMonth' => $month->format('Y-m'),
        ]);
    }

    public function store(StoreCalendarEventRequest $request, ReminderService $reminders): RedirectResponse
    {
        $event = CalendarEvent::create([
            ...$request->validated(),
            'uuid' => Str::uuid(),
            'user_id' => $request->boolean('is_personal') ? auth()->id() : null,
        ]);

        $reminders->scheduleForCalendarEvent($event);

        return to_route('calendar.index', ['month' => Carbon::parse($request->validated()['starts_at'])->format('Y-m')]);
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $calendarEvent, ReminderService $reminders): RedirectResponse
    {
        $reminders->cancelForCalendarEvent($calendarEvent);

        $calendarEvent->update([
            ...$request->validated(),
            'user_id' => $request->boolean('is_personal') ? auth()->id() : null,
        ]);

        $reminders->scheduleForCalendarEvent($calendarEvent->fresh());

        return to_route('calendar.index', ['month' => Carbon::parse($request->validated()['starts_at'])->format('Y-m')]);
    }

    public function destroy(CalendarEvent $calendarEvent, ReminderService $reminders): RedirectResponse
    {
        $month = $calendarEvent->starts_at->format('Y-m');
        $reminders->cancelForCalendarEvent($calendarEvent);
        $calendarEvent->delete();

        return to_route('calendar.index', ['month' => $month]);
    }
}
