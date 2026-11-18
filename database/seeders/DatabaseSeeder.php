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
        User::firstOrCreate(
            ['identity_number' => 'admin'],
            [
                'name' => 'Admin Eduva',
                'email' => 'admin@eduva.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'must_change_password' => false,
            ]
        );

        // Guru Account
        User::firstOrCreate(
            ['identity_number' => '198501012010011001'],
            [
                'name' => 'Budi Santoso',
                'email' => 'guru@eduva.com',
                'password' => Hash::make('password'),
                'role' => 'guru',
                'must_change_password' => false,
            ]
        );

        // Siswa Account
        User::firstOrCreate(
            ['identity_number' => '202401001'],
            [
                'name' => 'Siswa Teladan',
                'email' => 'siswa@eduva.com',
                'password' => Hash::make('password'),
                'role' => 'siswa',
                'must_change_password' => false,
            ]
        );

        // Pengaturan Sistem Default
        \App\Models\SystemSetting::firstOrCreate(
            ['key' => 'app_name'],
            ['value' => 'LMS Eduva', 'type' => 'text', 'description' => 'Nama platform pembelajaran']
        );
        \App\Models\SystemSetting::firstOrCreate(
            ['key' => 'institution_name'],
            ['value' => 'SMK / Universitas Eduva', 'type' => 'text', 'description' => 'Nama institusi pendidikan']
        );
        \App\Models\SystemSetting::firstOrCreate(
            ['key' => 'academic_year'],
            ['value' => '2026/2027', 'type' => 'text', 'description' => 'Tahun ajaran aktif']
        );
        \App\Models\SystemSetting::firstOrCreate(
            ['key' => 'academic_semester'],
            ['value' => 'Ganjil', 'type' => 'text', 'description' => 'Semester akademik berjalan']
        );
        \App\Models\SystemSetting::firstOrCreate(
            ['key' => 'default_password'],
            ['value' => 'eduva123@', 'type' => 'text', 'description' => 'Password default untuk akun baru dan reset']
        );
        \App\Models\SystemSetting::firstOrCreate(
            ['key' => 'allow_student_leave_course'],
            ['value' => '1', 'type' => 'boolean', 'description' => 'Izinkan siswa keluar dari kelas secara mandiri']
        );
    }
}
