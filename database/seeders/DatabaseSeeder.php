<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create 3 admin accounts
        User::factory()->create([
            'name' => 'Admin PLN 1',
            'email' => 'admin1@pln.com',
            'password' => 'admin123456',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Admin PLN 2',
            'email' => 'admin2@pln.com',
            'password' => 'admin123456',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Admin PLN 3',
            'email' => 'admin3@pln.com',
            'password' => 'admin123456',
            'role' => 'admin',
        ]);
    }
}
