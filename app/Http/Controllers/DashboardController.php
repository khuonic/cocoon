<?php

namespace App\Http\Controllers;

use App\Models\Birthday;
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

        $todayBirthdays = Birthday::query()
            ->whereMonth('date', now()->month)
            ->whereDay('date', now()->day)
            ->get()
            ->map(fn (Birthday $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'age' => $b->age,
            ]);

        $jokeCount = Joke::query()->count();
        $joke = $jokeCount > 0
            ? Joke::query()->find((now()->dayOfYear % $jokeCount) + 1)
            : null;

        return Inertia::render('Dashboard', [
            'sweetMessage' => $sweetMessage,
            'mySweetMessage' => $mySweetMessage,
            'todayBirthdays' => $todayBirthdays,
            'joke' => $joke,
        ]);
    }
}
