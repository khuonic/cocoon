<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (config('cocon.allowed_users', []) as $userData) {
            User::factory()->create([
                'name' => $userData['name'],
                'email' => $userData['email'],
            ]);
        }
    }
}
