<?php

namespace App\Http\Controllers;

use App\Http\Requests\Birthday\StoreBirthdayRequest;
use App\Http\Requests\Birthday\UpdateBirthdayRequest;
use App\Models\Birthday;
use App\Services\ReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BirthdayController extends Controller
{
    public function index(): Response
    {
        $birthdays = Birthday::query()
            ->with('addedBy')
            ->orderByRaw("strftime('%m', date), strftime('%d', date)")
            ->get()
            ->map(fn (Birthday $b) => [
                ...$b->toArray(),
                'age' => $b->age,
            ]);

        return Inertia::render('Birthdays/Index', [
            'birthdays' => $birthdays,
        ]);
    }

    public function store(StoreBirthdayRequest $request, ReminderService $reminders): RedirectResponse
    {
        $birthday = Birthday::create([
            ...$request->validated(),
            'uuid' => Str::uuid(),
            'added_by' => auth()->id(),
        ]);

        $reminders->scheduleForBirthday($birthday);

        return to_route('birthdays.index');
    }

    public function update(UpdateBirthdayRequest $request, Birthday $birthday, ReminderService $reminders): RedirectResponse
    {
        $reminders->cancelForBirthday($birthday);
        $birthday->update($request->validated());
        $reminders->scheduleForBirthday($birthday->fresh());

        return to_route('birthdays.index');
    }

    public function destroy(Birthday $birthday, ReminderService $reminders): RedirectResponse
    {
        $reminders->cancelForBirthday($birthday);
        $birthday->delete();

        return to_route('birthdays.index');
    }
}
