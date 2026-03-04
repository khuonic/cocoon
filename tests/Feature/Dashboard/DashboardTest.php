<?php

use App\Enums\EventCategory;
use App\Models\Birthday;
use App\Models\CalendarEvent;
use App\Models\Joke;
use App\Models\SweetMessage;
use App\Models\User;

test('guests are redirected to login', function () {
    $this->get('/')->assertRedirect('/login');
});

test('authenticated users can view the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('sweetMessage')
            ->has('mySweetMessage')
            ->has('todayItems')
            ->has('todayItemsCount')
            ->has('joke')
        );
});

test('dashboard shows partner sweet message', function () {
    $kevin = User::factory()->create();
    $lola = User::factory()->create();
    SweetMessage::factory()->create(['user_id' => $lola->id, 'content' => 'Je t\'aime']);

    $this->actingAs($kevin)
        ->get('/')
        ->assertInertia(fn ($page) => $page
            ->where('sweetMessage.content', 'Je t\'aime')
        );
});

test('dashboard does not show own sweet message as partner message', function () {
    $kevin = User::factory()->create();
    SweetMessage::factory()->create(['user_id' => $kevin->id, 'content' => 'Mon message']);

    $this->actingAs($kevin)
        ->get('/')
        ->assertInertia(fn ($page) => $page
            ->where('sweetMessage', null)
            ->where('mySweetMessage.content', 'Mon message')
        );
});

test('dashboard shows joke of the day', function () {
    $user = User::factory()->create();
    Joke::create(['content' => 'Blague unique']);

    $this->actingAs($user)
        ->get('/')
        ->assertInertia(fn ($page) => $page
            ->where('joke.content', 'Blague unique')
        );
});

test("today's calendar event appears in todayItems", function () {
    $user = User::factory()->create();
    CalendarEvent::factory()->create([
        'title' => 'Réunion',
        'category' => EventCategory::Pro->value,
        'starts_at' => today()->setTime(10, 0),
        'all_day' => false,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get('/')
        ->assertInertia(fn ($page) => $page
            ->has('todayItems', 1)
            ->where('todayItems.0.type', 'event')
            ->where('todayItems.0.title', 'Réunion')
            ->where('todayItems.0.time', '10:00')
        );
});

test("today's birthday appears in todayItems", function () {
    $user = User::factory()->create();
    Birthday::factory()->create([
        'name' => 'Maman',
        'date' => now()->subYears(50)->format('Y-m-d'),
        'added_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get('/')
        ->assertInertia(fn ($page) => $page
            ->has('todayItems', 1)
            ->where('todayItems.0.type', 'birthday')
            ->where('todayItems.0.age', 50)
        );
});

test('todayItemsCount reflects total when more than 5 items', function () {
    $user = User::factory()->create();

    CalendarEvent::factory()->count(6)->create([
        'starts_at' => today()->setTime(9, 0),
        'all_day' => false,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get('/')
        ->assertInertia(fn ($page) => $page
            ->has('todayItems', 5)
            ->where('todayItemsCount', 6)
        );
});

test("tomorrow's event does not appear in todayItems", function () {
    $user = User::factory()->create();
    CalendarEvent::factory()->create([
        'starts_at' => today()->addDay()->setTime(10, 0),
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get('/')
        ->assertInertia(fn ($page) => $page
            ->has('todayItems', 0)
            ->where('todayItemsCount', 0)
        );
});
