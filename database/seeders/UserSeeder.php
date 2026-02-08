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
        User::factory()->create([
            'name' => 'Kevin',
            'email' => 'kevininc155@gmail.com',
        ]);

        User::factory()->create([
            'name' => 'Lola',
            'email' => 'lolavivant@hotmail.fr',
        ]);
    }
}
