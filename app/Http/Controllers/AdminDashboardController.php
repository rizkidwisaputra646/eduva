<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses terbatas untuk Administrator.');
        }

        // 1. Statistik Utama
        $totalStudents = User::where('role', 'siswa')->count();
        $totalTeachers = User::where('role', 'guru')->count();
        $totalCourses = Course::count();
        $activeCourses = Course::where('is_active', true)->count();
        $inactiveCourses = Course::where('is_active', false)->count();

        $activeUsers = User::where('is_active', true)->count();
        $inactiveUsers = User::where('is_active', false)->count();
        $mustChangePasswordUsers = User::where('must_change_password', true)->count();

        // 2. Pertumbuhan Akun & Kelas (7 Hari Terakhir)
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $newStudentsThisWeek = User::where('role', 'siswa')->where('created_at', '>=', $sevenDaysAgo)->count();
        $newTeachersThisWeek = User::where('role', 'guru')->where('created_at', '>=', $sevenDaysAgo)->count();
        $newCoursesThisWeek = Course::where('created_at', '>=', $sevenDaysAgo)->count();

        // Pertumbuhan 30 Hari Terakhir
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $newUsersThisMonth = User::where('created_at', '>=', $thirtyDaysAgo)->count();
        $newCoursesThisMonth = Course::where('created_at', '>=', $thirtyDaysAgo)->count();

        // 3. Aktivitas Terbaru Sistem
        $recentActivities = ActivityLog::latest()->take(8)->get();

        // 4. Kelas Terbaru yang Dibuat Guru
        $recentCourses = Course::with('teacher')
            ->withCount('students')
            ->latest()
            ->take(5)
            ->get();

        // 5. Guru Paling Aktif Berdasarkan Jumlah Kelas
        $activeTeachers = User::where('role', 'guru')
            ->withCount(['courses' => function($q) {
                // Course::where('teacher_id', $user->id)
            }])
            ->orderBy('courses_count', 'desc')
            ->take(4)
            ->get();

        return view('admin.dashboard', compact(
            'totalStudents',
            'totalTeachers',
            'totalCourses',
            'activeCourses',
            'inactiveCourses',
            'activeUsers',
            'inactiveUsers',
            'mustChangePasswordUsers',
            'newStudentsThisWeek',
            'newTeachersThisWeek',
            'newCoursesThisWeek',
            'newUsersThisMonth',
            'newCoursesThisMonth',
            'recentActivities',
            'recentCourses'
        ));
    }
}

