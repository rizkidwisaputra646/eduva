<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminSettingController extends Controller
{
    private function checkAdmin(): void
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses terbatas untuk Administrator.');
        }
    }

    public function index()
    {
        $this->checkAdmin();

        $settings = [
            'app_name' => SystemSetting::get('app_name', 'LMS Eduva'),
            'institution_name' => SystemSetting::get('institution_name', 'SMK / Universitas Eduva'),
            'academic_year' => SystemSetting::get('academic_year', '2026/2027'),
            'academic_semester' => SystemSetting::get('academic_semester', 'Ganjil'),
            'default_password' => SystemSetting::get('default_password', 'eduva123@'),
            'allow_student_leave_course' => SystemSetting::get('allow_student_leave_course', '1'),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $this->checkAdmin();

        $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'institution_name' => ['required', 'string', 'max:255'],
            'academic_year' => ['required', 'string', 'max:20'],
            'academic_semester' => ['required', 'in:Ganjil,Genap,Pendek'],
            'default_password' => ['required', 'string', 'min:6'],
            'allow_student_leave_course' => ['nullable', 'boolean'],
        ]);

        SystemSetting::set('app_name', $request->app_name, 'text', 'Nama platform pembelajaran');
        SystemSetting::set('institution_name', $request->institution_name, 'text', 'Nama institusi pendidikan');
        SystemSetting::set('academic_year', $request->academic_year, 'text', 'Tahun ajaran berjalan');
        SystemSetting::set('academic_semester', $request->academic_semester, 'text', 'Semester akademik berjalan');
        SystemSetting::set('default_password', $request->default_password, 'text', 'Password default untuk akun baru dan reset');
        SystemSetting::set('allow_student_leave_course', $request->boolean('allow_student_leave_course') ? '1' : '0', 'boolean', 'Izinkan siswa keluar dari kelas secara mandiri');

        ActivityLog::record(
            'system.settings_update',
            "Admin memperbarui konfigurasi umum platform Eduva ({$request->app_name} - {$request->institution_name})"
        );

        return back()->with('success', 'Pengaturan sistem berhasil diperbarui!');
    }
}

