<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user->role === 'guru') {
            $courses = Course::with('teacher')->where('teacher_id', $user->id)->get();
        } elseif ($user->role === 'siswa') {
            $courses = $user->enrolledCourses()->with('teacher')->get();
        } else {
            $courses = Course::with('teacher')->get(); // admin
        }
        
        return view('kelas.index', compact('courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        Course::create([
            'title' => $request->title,
            'description' => $request->description,
            'teacher_id' => Auth::id(),
            'invitation_code' => strtoupper(\Illuminate\Support\Str::random(6)),
        ]);

        return redirect()->route('kelas.index')->with('success', 'Mata Pelajaran berhasil ditambahkan!');
    }

    public function join(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10',
        ]);

        $course = Course::where('invitation_code', $request->code)->first();

        if (!$course) {
            return back()->with('error', 'Kode kelas tidak ditemukan!');
        }

        if (Auth::user()->enrolledCourses()->where('course_id', $course->id)->exists()) {
            return back()->with('error', 'Anda sudah tergabung dalam kelas ini.');
        }

        Auth::user()->enrolledCourses()->attach($course->id);

        return redirect()->route('kelas.show', $course->id)->with('success', 'Berhasil bergabung ke kelas ' . $course->title . '!');
    }

    public function update(Request $request, Course $course)
    {
        if (Auth::user()->role !== 'admin' && $course->teacher_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah kelas ini.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $course->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Data kelas berhasil diperbarui!');
    }

    public function destroy(Course $course)
    {
        if (Auth::user()->role !== 'admin' && $course->teacher_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus kelas ini.');
        }

        $title = $course->title;
        $course->delete();

        return redirect()->route('kelas.index')->with('success', 'Kelas "' . $title . '" berhasil dihapus beserta seluruh aktivitasnya.');
    }

    public function show(Course $course)
    {
        $userId = Auth::id();
        $isTeacher = Auth::user()->role === 'guru' || Auth::user()->role === 'admin';

        $course->load([
            'materials', 
            'quizzes.submissions' => function($q) use ($userId) {
                $q->where('user_id', $userId);
            }, 
            'attendances.logs' => function($q) use ($userId) {
                $q->where('user_id', $userId);
            },
            'teacher', 
            'students', 
            'announcements' => function($q) {
                $q->latest();
            }, 
            'announcements.user'
        ]);
        
        $activities = collect();

        // 1. Materials (File, Halaman, Video, Label)
        foreach($course->materials as $m) {
            if (!$isTeacher && !$m->is_visible) continue;
            $m->activity_type = 'material';
            $activities->push($m);
        }

        // 2. Quizzes & Assignments
        foreach($course->quizzes as $q) {
            if (!$isTeacher && !$q->is_visible) continue;
            $q->activity_type = $q->type; // 'quiz' or 'assignment'
            $activities->push($q);
        }

        // 3. Attendances
        foreach($course->attendances as $att) {
            if (!$isTeacher && !$att->is_visible) continue;
            $att->activity_type = 'attendance';
            $activities->push($att);
        }

        // Group by topic, sort chronologically or by order
        $groupedActivities = $activities->sortBy('order')->groupBy('topic');

        // Extract list of all unique topic names in course
        $existingTopics = collect([
            'Umum',
            '01. Pengantar Kuliah & Konsep Dasar',
            '02. Pembahasan Materi Lanjutan',
        ])
        ->merge($activities->pluck('topic'))
        ->merge($course->announcements->pluck('topic')->filter())
        ->unique()
        ->values();

        return view('kelas.show', compact('course', 'groupedActivities', 'existingTopics'));
    }

    public function removeStudent(Course $course, \App\Models\User $user)
    {
        $course->students()->detach($user->id);
        return back()->with('success', 'Siswa ' . $user->name . ' berhasil dikeluarkan dari kelas.');
    }
}
