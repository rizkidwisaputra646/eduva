<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user->role === 'admin') {
            return redirect()->route('admin.courses.index');
        }

        if ($user->role === 'guru') {
            $courses = Course::with('teacher')->withCount(['students', 'materials', 'quizzes'])->where('teacher_id', $user->id)->get();
        } else {
            $courses = $user->enrolledCourses()->with('teacher')->withCount(['materials', 'quizzes'])->get();
        }
        
        return view('kelas.index', compact('courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $course = Course::create([
            'title' => $request->title,
            'description' => $request->description,
            'teacher_id' => Auth::id(),
            'invitation_code' => strtoupper(\Illuminate\Support\Str::random(6)),
            'is_active' => true,
        ]);

        \App\Models\ActivityLog::record('course.create', "Guru " . Auth::user()->name . " membuat kelas baru: {$course->title} (Kode: {$course->invitation_code})");

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

        if (!$course->is_active) {
            return back()->with('error', 'Kelas ini sedang dinonaktifkan oleh administrator.');
        }

        if (Auth::user()->enrolledCourses()->where('course_id', $course->id)->exists()) {
            return back()->with('error', 'Anda sudah tergabung dalam kelas ini.');
        }

        Auth::user()->enrolledCourses()->attach($course->id);

        \App\Models\ActivityLog::record('course.join', "Siswa " . Auth::user()->name . " bergabung ke kelas " . $course->title . " (Kode: " . $course->invitation_code . ")");

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

    /**
     * Delete an entire meeting (topic) and all its activities inside a course.
     */
    public function destroyMeeting(Request $request, Course $course)
    {
        $user = Auth::user();
        if ($user->role !== 'admin' && ($user->role !== 'guru' || $course->teacher_id !== $user->id)) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus pertemuan pada kelas ini.');
        }

        $validated = $request->validate([
            'topic' => 'required|string|max:255',
        ]);

        $topic = (string) $validated['topic'];

        $deletedCount = DB::transaction(function () use ($course, $topic) {
            $count = 0;

            $materials = $course->materials()->where('topic', $topic)->get();
            foreach ($materials as $material) {
                if ($material->file_path && Storage::disk('public')->exists($material->file_path)) {
                    Storage::disk('public')->delete($material->file_path);
                }
                $material->delete();
                $count++;
            }

            $quizzes = $course->quizzes()->with('submissions')->where('topic', $topic)->get();
            foreach ($quizzes as $quiz) {
                foreach ($quiz->submissions as $submission) {
                    if ($submission->file_path && Storage::disk('public')->exists($submission->file_path)) {
                        Storage::disk('public')->delete($submission->file_path);
                    }
                }
                $quiz->delete();
                $count++;
            }

            $attendances = $course->attendances()->where('topic', $topic)->get();
            foreach ($attendances as $attendance) {
                $attendance->delete();
                $count++;
            }

            $announcementsDeleted = $course->announcements()->where('topic', $topic)->delete();
            $count += $announcementsDeleted;

            return $count;
        });

        if ($deletedCount === 0) {
            return back()->with('error', 'Pertemuan "' . $topic . '" tidak ditemukan pada kelas ini.');
        }

        \App\Models\ActivityLog::record(
            'meeting.delete',
            "Guru " . $user->name . " menghapus pertemuan \"{$topic}\" pada kelas {$course->title}",
            $user
        );

        return back()->with('success', 'Pertemuan "' . $topic . '" beserta seluruh aktivitas di dalamnya berhasil dihapus.');
    }

    public function show(Course $course)
    {
        $user = Auth::user();
        if ($user->role === 'admin') {
            return redirect()->route('admin.courses.show', $course->id);
        }

        $userId = $user->id;
        $isTeacher = $user->role === 'guru';

        // Validasi keamanan akses: jika siswa tidak terdaftar di kelas, tolak akses langsung
        if ($user->role === 'siswa' && !$course->students()->where('course_user.user_id', $userId)->exists()) {
            abort(403, 'Akses ditolak. Anda tidak terdaftar dalam kelas ini atau sudah keluar dari keanggotaan kelas.');
        }

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

        // Extract list of real unique meeting/topic names created in this course (tanpa data dummy/hardcode)
        $existingTopics = collect()
            ->merge($groupedActivities->keys())
            ->merge($course->announcements->pluck('topic'))
            ->map(fn ($topic) => trim((string) $topic))
            ->filter(fn (string $topic) => $topic !== '')
            ->unique()
            ->values();

        $courseMeetings = $existingTopics
            ->map(function (string $topic, int $index) use ($groupedActivities, $course) {
                $activityCount = $groupedActivities->has($topic)
                    ? $groupedActivities->get($topic)->count()
                    : 0;

                return $this->formatCourseMeeting($topic, $index, $activityCount, $course);
            })
            ->values();

        return view('kelas.show', compact('course', 'groupedActivities', 'existingTopics', 'courseMeetings'));
    }

    /**
     * Format a course's real topic/meeting record into a structured option for the Add Activity modal.
     */
    protected function formatCourseMeeting(string $topic, int $index, int $activityCount, Course $course): array
    {
        $meetingNumber = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
        $cleanTitle = $topic;

        if (preg_match('/^(?:pertemuan|sesi|bab|modul)\s*0*(\d+)\s*(?:[:\-\—\.]\s*(.+))?$/iu', $topic, $matches)) {
            $meetingNumber = str_pad((string) ((int) $matches[1]), 2, '0', STR_PAD_LEFT);
            $cleanTitle = isset($matches[2]) && trim($matches[2]) !== '' ? trim($matches[2]) : $topic;
            $displayLabel = isset($matches[2]) && trim($matches[2]) !== ''
                ? "Pertemuan {$meetingNumber} — {$cleanTitle}"
                : "Pertemuan {$meetingNumber}";
        } elseif (preg_match('/^0*(\d+)\s*[\.\-\—]\s*(.+)$/u', $topic, $matches)) {
            $meetingNumber = str_pad((string) ((int) $matches[1]), 2, '0', STR_PAD_LEFT);
            $cleanTitle = trim($matches[2]);
            $displayLabel = "Pertemuan {$meetingNumber} — {$cleanTitle}";
        } else {
            $displayLabel = "Pertemuan {$meetingNumber} — {$cleanTitle}";
        }

        return [
            'id' => $topic,
            'topic' => $topic,
            'meeting_number' => $meetingNumber,
            'title' => $cleanTitle,
            'display_label' => $displayLabel,
            'course_id' => $course->id,
            'course_title' => $course->title,
            'activity_count' => $activityCount,
        ];
    }

    public function removeStudent(Course $course, \App\Models\User $user)
    {
        $course->students()->detach($user->id);
        return back()->with('success', 'Siswa ' . $user->name . ' berhasil dikeluarkan dari kelas.');
    }

    public function leave(Course $course)
    {
        $user = Auth::user();

        if ($user->role !== 'siswa') {
            abort(403, 'Hanya siswa yang dapat menggunakan fitur keluar dari kelas.');
        }

        if (!$course->students()->where('course_user.user_id', $user->id)->exists()) {
            return redirect()->route('kelas.index')->with('error', 'Anda tidak terdaftar di kelas ini.');
        }

        // Hapus relasi keanggotaan siswa dari kelas tanpa menghapus rekaman nilai dan submission
        $course->students()->detach($user->id);

        \App\Models\ActivityLog::record('course.leave', "Siswa " . $user->name . " keluar dari kelas " . $course->title, $user);

        return redirect()->route('kelas.index')
            ->with('success', 'Anda telah berhasil keluar dari kelas "' . $course->title . '".');
    }
}
