<?php

namespace App\Http\Controllers;

use App\Http\Requests\Birthday\StoreBirthdayRequest;
use App\Http\Requests\Birthday\UpdateBirthdayRequest;
use App\Models\Birthday;
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

    public function store(StoreBirthdayRequest $request): RedirectResponse
    {
        Birthday::create([
            ...$request->validated(),
            'uuid' => Str::uuid(),
            'added_by' => auth()->id(),
        ]);

        return to_route('birthdays.index');
    }

    public function update(UpdateBirthdayRequest $request, Birthday $birthday): RedirectResponse
    {
        $birthday->update($request->validated());

        return to_route('birthdays.index');
    }

    public function destroy(Birthday $birthday): RedirectResponse
    {
        $birthday->delete();

        return to_route('birthdays.index');
    }
}
