<?php

use App\Models\SweetMessage;
use App\Models\User;

test('guests cannot store a sweet message', function () {
    $this->post(route('sweet-messages.store'), ['content' => 'Hello'])
        ->assertRedirect('/login');
});

test('store creates a new sweet message', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('sweet-messages.store'), ['content' => 'Je t\'aime'])
        ->assertRedirect('/');

    $this->assertDatabaseHas('sweet_messages', [
        'user_id' => $user->id,
        'content' => 'Je t\'aime',
    ]);
});

test('store updates existing sweet message', function () {
    $user = User::factory()->create();
    SweetMessage::factory()->create(['user_id' => $user->id, 'content' => 'Ancien']);

    $this->actingAs($user)
        ->post(route('sweet-messages.store'), ['content' => 'Nouveau'])
        ->assertRedirect('/');

    expect(SweetMessage::query()->where('user_id', $user->id)->count())->toBe(1);
    expect(SweetMessage::query()->where('user_id', $user->id)->first()->content)->toBe('Nouveau');
});

test('store validates content is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('sweet-messages.store'), ['content' => ''])
        ->assertSessionHasErrors(['content']);
});

test('store validates content max length', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('sweet-messages.store'), ['content' => str_repeat('a', 501)])
        ->assertSessionHasErrors(['content']);
});
