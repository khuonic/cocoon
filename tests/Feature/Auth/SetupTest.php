<?php

use App\Models\ExpenseCategory;
use App\Models\User;

test('setup page is accessible when no users exist', function () {
    $this->get(route('setup'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/Setup'));
});

test('setup redirects to login when users already exist', function () {
    User::factory()->create();

    $this->get(route('setup'))
        ->assertRedirect('/login');
});

test('login redirects to setup when no users exist', function () {
    $this->get('/login')
        ->assertRedirect(route('setup'));
});

test('setup creates both users and categories', function () {
    $this->post(route('setup'), [
        'email' => 'kevininc155@gmail.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertRedirect('/');

    expect(User::count())->toBe(2);
    expect(User::where('email', 'kevininc155@gmail.com')->exists())->toBeTrue();
    expect(User::where('email', 'lolavivant@hotmail.fr')->exists())->toBeTrue();
    expect(ExpenseCategory::count())->toBeGreaterThan(0);
});

test('setup auto-logs in the user who created the account', function () {
    $this->post(route('setup'), [
        'email' => 'kevininc155@gmail.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $this->assertAuthenticated();
    $this->assertAuthenticatedAs(User::where('email', 'kevininc155@gmail.com')->first());
});

test('setup rejects email not in whitelist', function () {
    $this->post(route('setup'), [
        'email' => 'intruder@evil.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertSessionHasErrors('email');

    expect(User::count())->toBe(0);
});

test('setup validates password confirmation', function () {
    $this->post(route('setup'), [
        'email' => 'kevininc155@gmail.com',
        'password' => 'Password123!',
        'password_confirmation' => 'WrongConfirmation!',
    ])->assertSessionHasErrors('password');

    expect(User::count())->toBe(0);
});

test('setup store is blocked when users already exist', function () {
    User::factory()->create();

    $this->post(route('setup'), [
        'email' => 'kevininc155@gmail.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertRedirect('/login');

    expect(User::count())->toBe(1);
});

test('partner can login with the same password after setup', function () {
    $this->post(route('setup'), [
        'email' => 'kevininc155@gmail.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    auth()->logout();

    $this->post('/login', [
        'email' => 'lolavivant@hotmail.fr',
        'password' => 'Password123!',
    ])->assertRedirect('/');

    $this->assertAuthenticatedAs(User::where('email', 'lolavivant@hotmail.fr')->first());
});
