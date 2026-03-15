<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Native\Mobile\Facades\SecureStorage;

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

test('verify with valid token from SecureStorage authenticates user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mobile')->plainTextToken;

    SecureStorage::shouldReceive('get')
        ->with('cocoon_auth_token')
        ->andReturn($token);

    $response = $this->post(route('biometric.verify'));

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/');
});

test('verify with no SecureStorage token fails', function () {
    SecureStorage::shouldReceive('get')
        ->with('cocoon_auth_token')
        ->andReturn(null);

    $response = $this->post(route('biometric.verify'));

    $this->assertGuest();
    $response->assertSessionHasErrors('biometric');
});

test('verify with invalid token in SecureStorage fails', function () {
    SecureStorage::shouldReceive('get')
        ->with('cocoon_auth_token')
        ->andReturn('invalid-token');

    $response = $this->post(route('biometric.verify'));

    $this->assertGuest();
    $response->assertSessionHasErrors('biometric');
});

test('verify with revoked token in SecureStorage fails', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mobile')->plainTextToken;
    PersonalAccessToken::findToken($token)->delete();

    SecureStorage::shouldReceive('get')
        ->with('cocoon_auth_token')
        ->andReturn($token);

    $response = $this->post(route('biometric.verify'));

    $this->assertGuest();
    $response->assertSessionHasErrors('biometric');
});

test('login flashes api token and saves to SecureStorage', function () {
    $user = User::factory()->create();

    SecureStorage::shouldReceive('set')
        ->with('cocoon_auth_token', \Mockery::type('string'))
        ->andReturn(true);

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
