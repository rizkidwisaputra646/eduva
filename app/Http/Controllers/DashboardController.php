<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $data = [];

        if ($user->role === 'guru') {
            // Data untuk Guru
            $data['total_courses'] = Course::where('teacher_id', $user->id)->count();
            $data['total_materials'] = Material::whereHas('course', function($q) use ($user) {
                $q->where('teacher_id', $user->id);
            })->count();
            
            // Total unique students enrolled in teacher's courses
            $data['total_students'] = User::whereHas('enrolledCourses', function($q) use ($user) {
                $q->where('teacher_id', $user->id);
            })->count(); 
            
            // Pengumpulan tugas/kuis siswa yang belum dinilai
            $data['ungraded_submissions'] = Submission::whereHas('quiz.course', function($q) use ($user) {
                $q->where('teacher_id', $user->id);
            })->whereNull('score')
              ->with(['user', 'quiz.course'])
              ->latest()
              ->take(5)
              ->get();

            $data['ungraded_count'] = Submission::whereHas('quiz.course', function($q) use ($user) {
                $q->where('teacher_id', $user->id);
            })->whereNull('score')->count();

            // Kelas yang diampu oleh guru
            $data['teacher_courses'] = Course::where('teacher_id', $user->id)
                ->withCount(['students', 'materials', 'quizzes'])
                ->latest()
                ->take(4)
                ->get();

            // Materi terbaru yang ditambahkan guru
            $data['recent_materials'] = Material::whereHas('course', function($q) use ($user) {
                $q->where('teacher_id', $user->id);
            })->with('course')->latest()->take(5)->get();

        } elseif ($user->role === 'siswa') {
            // Data untuk Siswa (Data Riil)
            $enrolledCourses = $user->enrolledCourses()
                ->with('teacher')
                ->withCount(['materials', 'quizzes'])
                ->get();

            $data['enrolled_courses'] = $enrolledCourses;
            $data['total_courses'] = $enrolledCourses->count();

            $courseIds = $enrolledCourses->pluck('id');

            // Submissions yang sudah diselesaikan siswa
            $studentSubmissions = Submission::where('user_id', $user->id)->get();
            $data['completed_tasks_count'] = $studentSubmissions->count();

            $gradedSubmissions = $studentSubmissions->whereNotNull('score');
            $data['average_score'] = $gradedSubmissions->count() > 0 
                ? round($gradedSubmissions->avg('score'), 1) 
                : null;

            // Kuis / Tugas aktif yang belum dikerjakan oleh siswa
            $data['pending_tasks'] = Quiz::whereIn('course_id', $courseIds)
                ->where('is_visible', true)
                ->whereDoesntHave('submissions', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->with('course')
                ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END, deadline ASC')
                ->take(5)
                ->get();

            // Materi terbaru yang tersedia untuk siswa
            $data['recent_materials'] = Material::whereIn('course_id', $courseIds)
                ->where('is_visible', true)
                ->with('course')
                ->latest()
                ->take(4)
                ->get();
        } else {
            // Admin dialihkan langsung ke Pusat Dashboard Administrator
            return redirect()->route('admin.dashboard');
        }

        return view('dashboard', $data);
    }
}
