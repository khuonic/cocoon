<?php

use App\Models\Birthday;
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
            ->has('todayBirthdays')
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

test('dashboard shows today birthdays', function () {
    $user = User::factory()->create();
    Birthday::factory()->create([
        'name' => 'Maman',
        'date' => now()->subYears(50)->format('Y-m-d'),
        'added_by' => $user->id,
    ]);
    Birthday::factory()->create([
        'name' => 'Autre',
        'date' => now()->addDay()->format('Y-m-d'),
        'added_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get('/')
        ->assertInertia(fn ($page) => $page
            ->has('todayBirthdays', 1)
            ->where('todayBirthdays.0.name', 'Maman')
            ->where('todayBirthdays.0.age', 50)
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
