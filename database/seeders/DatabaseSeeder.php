<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin Account
        User::factory()->create([
            'name' => 'Admin Eduva',
            'email' => 'admin@eduva.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Guru Account
        User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'guru@eduva.com',
            'password' => Hash::make('password'),
            'role' => 'guru',
        ]);

        // Siswa Account
        User::factory()->create([
            'name' => 'Siswa Teladan',
            'email' => 'siswa@eduva.com',
            'password' => Hash::make('password'),
            'role' => 'siswa',
        ]);
    }
}
