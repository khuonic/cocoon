<?php

use App\Models\User;

it('returns a token for valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'kevininc155@gmail.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'kevininc155@gmail.com',
        'password' => 'password',
        'device_name' => 'test-device',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure(['token', 'user'])
        ->assertJsonPath('user.email', 'kevininc155@gmail.com');
});

it('rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'kevininc155@gmail.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'kevininc155@gmail.com',
        'password' => 'wrong-password',
        'device_name' => 'test-device',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('rejects non-whitelisted email', function () {
    User::factory()->create([
        'email' => 'intruder@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'intruder@example.com',
        'password' => 'password',
        'device_name' => 'test-device',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('requires email, password and device_name', function () {
    $response = $this->postJson('/api/login', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password', 'device_name']);
});
