<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile name can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Nouveau Nom',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->name)->toBe('Nouveau Nom');
});

test('email cannot be changed via profile update', function () {
    $user = User::factory()->create(['email' => 'kevin@example.com']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Kevin',
            'email' => 'hacker@example.com',
        ]);

    expect($user->refresh()->email)->toBe('kevin@example.com');
});
