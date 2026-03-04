<?php

use App\Enums\EventCategory;
use App\Models\CalendarEvent;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('guests are redirected to login', function () {
    $this->get(route('calendar.index'))->assertRedirect('/login');
});

test('authenticated users can view the calendar index', function () {
    $this->actingAs($this->user)
        ->get(route('calendar.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Calendar/Index')
            ->has('events')
            ->has('birthdays')
            ->has('users')
            ->has('currentMonth')
        );
});

test('index returns events for the current month', function () {
    $event = CalendarEvent::factory()->create([
        'starts_at' => now()->startOfMonth()->addDays(5),
        'user_id' => $this->user->id,
    ]);

    CalendarEvent::factory()->create([
        'starts_at' => now()->subMonths(2),
        'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('calendar.index'))
        ->assertInertia(fn ($page) => $page
            ->has('events', 1)
            ->where('events.0.id', $event->id)
        );
});

test('index filters by month query param', function () {
    CalendarEvent::factory()->create([
        'starts_at' => now()->startOfMonth()->addDays(5),
        'user_id' => $this->user->id,
    ]);

    $nextMonth = now()->addMonth()->format('Y-m');

    $event = CalendarEvent::factory()->create([
        'starts_at' => now()->addMonth()->startOfMonth()->addDays(3),
        'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('calendar.index', ['month' => $nextMonth]))
        ->assertInertia(fn ($page) => $page
            ->has('events', 1)
            ->where('events.0.id', $event->id)
            ->where('currentMonth', $nextMonth)
        );
});

test('store creates a shared event', function () {
    $this->actingAs($this->user)
        ->post(route('calendar.store'), [
            'title' => 'Fête de famille',
            'category' => 'Loisir',
            'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'all_day' => true,
            'is_personal' => false,
        ])
        ->assertRedirect(route('calendar.index'));

    $this->assertDatabaseHas('calendar_events', [
        'title' => 'Fête de famille',
        'category' => 'Loisir',
        'is_personal' => false,
        'user_id' => null,
    ]);
});

test('store creates a personal event', function () {
    $this->actingAs($this->user)
        ->post(route('calendar.store'), [
            'title' => 'Rendez-vous médecin',
            'category' => 'Rdv',
            'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'all_day' => true,
            'is_personal' => true,
        ])
        ->assertRedirect(route('calendar.index'));

    $this->assertDatabaseHas('calendar_events', [
        'title' => 'Rendez-vous médecin',
        'is_personal' => true,
        'user_id' => $this->user->id,
    ]);
});

test('store validates title is required', function () {
    $this->actingAs($this->user)
        ->post(route('calendar.store'), [
            'title' => '',
            'category' => 'Loisir',
            'starts_at' => now()->addWeek()->format('Y-m-d'),
        ])
        ->assertSessionHasErrors(['title']);
});

test('store validates category is required', function () {
    $this->actingAs($this->user)
        ->post(route('calendar.store'), [
            'title' => 'Test',
            'category' => '',
            'starts_at' => now()->addWeek()->format('Y-m-d'),
        ])
        ->assertSessionHasErrors(['category']);
});

test('update modifies an event', function () {
    $event = CalendarEvent::factory()->create([
        'title' => 'Ancien titre',
        'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->patch(route('calendar.update', $event), [
            'title' => 'Nouveau titre',
            'category' => 'Pro',
            'starts_at' => $event->starts_at->format('Y-m-d H:i:s'),
            'all_day' => false,
            'is_personal' => false,
        ])
        ->assertRedirect(route('calendar.index'));

    $event->refresh();
    expect($event->title)->toBe('Nouveau titre');
    expect($event->category)->toBe(EventCategory::Pro);
});

test('destroy deletes an event', function () {
    $event = CalendarEvent::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->delete(route('calendar.destroy', $event))
        ->assertRedirect(route('calendar.index'));

    $this->assertDatabaseMissing('calendar_events', ['id' => $event->id]);
});

test('events include category color and label', function () {
    CalendarEvent::factory()->create([
        'category' => EventCategory::Conges->value,
        'starts_at' => now()->startOfMonth()->addDays(2),
        'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('calendar.index'))
        ->assertInertia(fn ($page) => $page
            ->where('events.0.category_color', '#10B981')
            ->where('events.0.category_label', 'Congés')
        );
});
