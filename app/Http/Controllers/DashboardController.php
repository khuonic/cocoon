<?php

namespace App\Http\Controllers;

use App\Models\Birthday;
use App\Models\Joke;
use App\Models\SweetMessage;
use App\Models\Todo;
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

        $pinnedTodos = Todo::query()
            ->where('show_on_dashboard', true)
            ->where('is_done', false)
            ->with('assignee')
            ->oldest('created_at')
            ->get();

        return Inertia::render('Dashboard', [
            'sweetMessage' => $sweetMessage,
            'mySweetMessage' => $mySweetMessage,
            'todayBirthdays' => $todayBirthdays,
            'joke' => $joke,
            'pinnedTodos' => $pinnedTodos,
        ]);
    }
}
