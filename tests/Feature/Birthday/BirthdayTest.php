<?php

use App\Models\Birthday;
use App\Models\User;

test('guests are redirected to login', function () {
    $this->get(route('birthdays.index'))->assertRedirect('/login');
});

test('authenticated users can view the birthdays index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('birthdays.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Birthdays/Index')
            ->has('birthdays')
        );
});

test('birthdays are ordered by month and day', function () {
    $user = User::factory()->create();
    Birthday::factory()->create(['name' => 'Décembre', 'date' => '1990-12-25', 'added_by' => $user->id]);
    Birthday::factory()->create(['name' => 'Janvier', 'date' => '1985-01-15', 'added_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('birthdays.index'))
        ->assertInertia(fn ($page) => $page
            ->where('birthdays.0.name', 'Janvier')
            ->where('birthdays.1.name', 'Décembre')
        );
});

test('birthdays include age', function () {
    $user = User::factory()->create();
    Birthday::factory()->create([
        'name' => 'Test',
        'date' => now()->subYears(30)->format('Y-m-d'),
        'added_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('birthdays.index'))
        ->assertInertia(fn ($page) => $page
            ->where('birthdays.0.age', 30)
        );
});

test('store creates a birthday with uuid and added_by', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('birthdays.store'), [
            'name' => 'Maman',
            'date' => '1970-05-15',
        ])
        ->assertRedirect(route('birthdays.index'));

    $this->assertDatabaseHas('birthdays', [
        'name' => 'Maman',
        'added_by' => $user->id,
    ]);

    $birthday = Birthday::query()->where('name', 'Maman')->first();
    expect($birthday->uuid)->not->toBeNull();
});

test('store validates name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('birthdays.store'), [
            'name' => '',
            'date' => '1990-01-01',
        ])
        ->assertSessionHasErrors(['name']);
});

test('store validates date is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('birthdays.store'), [
            'name' => 'Test',
            'date' => '',
        ])
        ->assertSessionHasErrors(['date']);
});

test('store validates date must not be in the future', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('birthdays.store'), [
            'name' => 'Futur',
            'date' => now()->addYear()->format('Y-m-d'),
        ])
        ->assertSessionHasErrors(['date']);
});

test('update modifies a birthday', function () {
    $user = User::factory()->create();
    $birthday = Birthday::factory()->create(['name' => 'Ancien nom', 'added_by' => $user->id]);

    $this->actingAs($user)
        ->put(route('birthdays.update', $birthday), [
            'name' => 'Nouveau nom',
            'date' => '1985-03-20',
        ])
        ->assertRedirect(route('birthdays.index'));

    $birthday->refresh();
    expect($birthday->name)->toBe('Nouveau nom');
    expect($birthday->date->format('Y-m-d'))->toBe('1985-03-20');
});

test('destroy deletes a birthday', function () {
    $user = User::factory()->create();
    $birthday = Birthday::factory()->create(['added_by' => $user->id]);

    $this->actingAs($user)
        ->delete(route('birthdays.destroy', $birthday))
        ->assertRedirect(route('birthdays.index'));

    $this->assertDatabaseMissing('birthdays', ['id' => $birthday->id]);
});
