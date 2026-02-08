<?php

use App\Models\User;

it('allows whitelisted users to access protected routes', function () {
    $user = User::factory()->create(['email' => 'kevininc155@gmail.com']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/user');

    $response->assertSuccessful()
        ->assertJsonPath('email', 'kevininc155@gmail.com');
});

it('blocks non-whitelisted users from accessing protected routes', function () {
    $user = User::factory()->create(['email' => 'intruder@example.com']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/user');

    $response->assertForbidden();
});

it('blocks unauthenticated requests', function () {
    $response = $this->getJson('/api/user');

    $response->assertUnauthorized();
});
