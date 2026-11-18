<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminCourseController extends Controller
{
    private function checkAdmin(): void
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses terbatas untuk Administrator.');
        }
    }

    public function index(Request $request)
    {
        $this->checkAdmin();

        $query = Course::with('teacher')->withCount(['students', 'materials', 'quizzes']);

        // Filter Status Aktif
        $status = $request->query('status');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        // Filter Guru
        $teacherId = $request->query('teacher_id');
        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }

        // Pencarian (Nama kelas, kode kelas, nama guru)
        $search = $request->query('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('invitation_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('teacher', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%")
                         ->orWhere('identity_number', 'like', "%{$search}%");
                  });
            });
        }

        $courses = $query->latest()->paginate(12)->withQueryString();

        $teachers = User::where('role', 'guru')->orderBy('name')->get();

        $counts = [
            'all' => Course::count(),
            'active' => Course::where('is_active', true)->count(),
            'inactive' => Course::where('is_active', false)->count(),
        ];

        return view('admin.courses.index', compact('courses', 'teachers', 'counts', 'status', 'teacherId', 'search'));
    }

    public function show(Course $course)
    {
        $this->checkAdmin();

        $course->load([
            'teacher',
            'students' => function ($q) {
                $q->orderBy('name');
            },
            'materials',
            'quizzes',
            'attendances',
        ]);

        return view('admin.courses.show', compact('course'));
    }

    public function toggleStatus(Course $course)
    {
        $this->checkAdmin();

        $newStatus = !$course->is_active;
        $course->update(['is_active' => $newStatus]);

        $statusText = $newStatus ? 'diaktifkan kembali' : 'dinonaktifkan';

        ActivityLog::record(
            'course.toggle_status',
            "Admin mengubah status kelas {$course->title} (Kode: {$course->invitation_code}) menjadi " . ($newStatus ? 'Aktif' : 'Nonaktif')
        );

        return back()->with('success', "Kelas '{$course->title}' berhasil {$statusText}.");
    }
}

