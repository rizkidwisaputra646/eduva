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

        // Tahun Ajaran Default
        $academicYear = \App\Models\AcademicYear::firstOrCreate(
            ['name' => '2026/2027', 'semester' => 'Ganjil'],
            [
                'is_active' => true,
                'status' => 'active',
                'start_date' => '2026-07-15',
                'end_date' => '2026-12-20',
            ]
        );

        // Kelas Rombel Contoh
        $guru = User::where('identity_number', '198501012010011001')->first();
        $class1 = \App\Models\SchoolClass::firstOrCreate(
            ['name' => '12 IPA A', 'academic_year_id' => $academicYear->id],
            [
                'level' => '12',
                'department' => 'IPA',
                'homeroom_teacher_id' => $guru?->id,
                'status' => 'active',
            ]
        );

        \App\Models\SchoolClass::firstOrCreate(
            ['name' => '12 IPA C', 'academic_year_id' => $academicYear->id],
            [
                'level' => '12',
                'department' => 'IPA',
                'status' => 'active',
            ]
        );

        \App\Models\SchoolClass::firstOrCreate(
            ['name' => '10 IPS A', 'academic_year_id' => $academicYear->id],
            [
                'level' => '10',
                'department' => 'IPS',
                'status' => 'active',
            ]
        );

        // Penempatan Siswa Contoh jika belum ditempatkan
        $siswa = User::where('identity_number', '202401001')->first();
        if ($siswa && $class1 && !$siswa->activeClassHistory()->exists()) {
            \App\Models\StudentClassHistory::create([
                'user_id' => $siswa->id,
                'school_class_id' => $class1->id,
                'academic_year_id' => $academicYear->id,
                'status' => 'active',
                'assigned_at' => now(),
                'notes' => 'Penempatan awal tahun ajaran baru',
            ]);
        }
    }
}
