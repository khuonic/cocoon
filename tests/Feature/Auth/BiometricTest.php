<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

test('guest can view biometric login page', function () {
    $response = $this->get(route('biometric.login'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('auth/BiometricLogin'));
});

test('authenticated user is redirected from biometric login', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('biometric.login'));

    $response->assertRedirect('/');
});

test('verify with valid token authenticates user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mobile')->plainTextToken;

    $response = $this->post(route('biometric.verify'), [
        'token' => $token,
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/');
});

test('verify with invalid token fails', function () {
    $response = $this->post(route('biometric.verify'), [
        'token' => 'invalid-token',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('token');
});

test('verify with revoked token fails', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mobile')->plainTextToken;

    PersonalAccessToken::findToken($token)->delete();

    $response = $this->post(route('biometric.verify'), [
        'token' => $token,
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('token');
});

test('verify requires token field', function () {
    $response = $this->post(route('biometric.verify'), []);

    $this->assertGuest();
    $response->assertSessionHasErrors('token');
});

test('login flashes api token', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/');
    $response->assertSessionHas('api_token');

    $flashedToken = session('api_token');
    expect(PersonalAccessToken::findToken($flashedToken))->not->toBeNull();
});

test('logout flashes logged out signal', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertSessionHas('logged_out', true);
});
