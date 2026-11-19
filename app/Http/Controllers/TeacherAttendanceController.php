<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Course;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeacherAttendanceController extends Controller
{
    private function checkTeacher(): User
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user->role !== 'guru' && $user->role !== 'admin') {
            abort(403, 'Akses terbatas untuk Pengajar dan Administrator.');
        }
        return $user;
    }

    private function getTeacherCourses(User $user)
    {
        if ($user->role === 'admin') {
            return Course::with(['students', 'schoolClass'])->orderBy('title')->get();
        }

        return Course::where('teacher_id', $user->id)
            ->with(['students', 'schoolClass'])
            ->orderBy('title')
            ->get();
    }

    public function index(Request $request)
    {
        $user = $this->checkTeacher();
        $courses = $this->getTeacherCourses($user);
        $courseIds = $courses->pluck('id');

        $query = Attendance::whereIn('course_id', $courseIds)
            ->with(['course', 'logs'])
            ->withCount([
                'logs as hadir_count' => fn ($q) => $q->where('status', 'hadir'),
                'logs as izin_count' => fn ($q) => $q->where('status', 'izin'),
                'logs as sakit_count' => fn ($q) => $q->where('status', 'sakit'),
                'logs as alpa_count' => fn ($q) => $q->where('status', 'alpa'),
            ]);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->query('course_id'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('topic', 'like', "%{$search}%");
            });
        }

        $attendances = $query->latest('start_time')->paginate(12)->withQueryString();

        return view('teacher.academic.attendance.index', compact('attendances', 'courses'));
    }

    public function create(Request $request)
    {
        $user = $this->checkTeacher();
        $courses = $this->getTeacherCourses($user);

        $selectedCourseId = $request->query('course_id', $courses->first()?->id);
        $selectedCourse = $courses->firstWhere('id', $selectedCourseId);

        return view('teacher.academic.attendance.create', compact('courses', 'selectedCourse'));
    }

    public function store(Request $request)
    {
        $user = $this->checkTeacher();

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'topic' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'description' => 'nullable|string',
            'initial_status' => 'nullable|in:hadir,alpa',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        if ($user->role === 'guru' && $course->teacher_id !== $user->id) {
            abort(403, 'Anda bukan pengampu kelas ini.');
        }

        DB::transaction(function () use ($validated, $course, $request, $user) {
            $attendance = Attendance::create([
                'course_id' => $course->id,
                'school_class_id' => $course->school_class_id,
                'topic' => $validated['topic'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'is_active' => true,
                'is_visible' => true,
            ]);

            // Jika guru memilih inisialisasi kehadiran otomatis untuk seluruh siswa kelas
            if ($request->filled('auto_populate')) {
                $status = $validated['initial_status'] ?? 'hadir';
                $students = $course->students;
                foreach ($students as $student) {
                    AttendanceLog::create([
                        'attendance_id' => $attendance->id,
                        'user_id' => $student->id,
                        'status' => $status,
                        'note' => 'Presensi sesi dibuat oleh pengajar',
                    ]);
                }
            }

            ActivityLog::record(
                'attendance.create',
                "Guru {$user->name} membuat sesi absensi: {$attendance->title} ({$course->title})"
            );
        });

        return redirect()->route('teacher.academic.attendance.index')->with('success', 'Sesi absensi berhasil dibuat.');
    }

    public function edit(Attendance $attendance)
    {
        $user = $this->checkTeacher();
        if ($user->role === 'guru' && $attendance->course->teacher_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        $attendance->load(['course.students', 'logs.user']);
        $students = $attendance->course->students()->orderBy('name')->get();
        $logs = $attendance->logs->keyBy('user_id');

        return view('teacher.academic.attendance.edit', compact('attendance', 'students', 'logs'));
    }

    public function update(Request $request, Attendance $attendance)
    {
        $user = $this->checkTeacher();
        if ($user->role === 'guru' && $attendance->course->teacher_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'attendances' => 'required|array',
            'attendances.*.status' => 'required|in:hadir,izin,sakit,alpa',
            'attendances.*.note' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($attendance, $validated, $user) {
            foreach ($validated['attendances'] as $userId => $item) {
                AttendanceLog::updateOrCreate(
                    [
                        'attendance_id' => $attendance->id,
                        'user_id' => $userId,
                    ],
                    [
                        'status' => $item['status'],
                        'note' => $item['note'] ?? null,
                    ]
                );
            }

            ActivityLog::record(
                'attendance.batch_update',
                "Guru {$user->name} memperbarui daftar kehadiran siswa sesi: {$attendance->title}"
            );
        });

        return back()->with('success', 'Data presensi siswa berhasil diperbarui.');
    }

    public function recap(Request $request)
    {
        $user = $this->checkTeacher();
        $courses = $this->getTeacherCourses($user);

        $selectedCourseId = $request->query('course_id', $courses->first()?->id);
        $selectedCourse = $courses->firstWhere('id', $selectedCourseId);

        if (!$selectedCourse) {
            return view('teacher.academic.attendance.recap', [
                'courses' => $courses,
                'selectedCourse' => null,
                'students' => collect(),
                'sessions' => collect(),
                'matrix' => [],
            ]);
        }

        // Sessions of this course
        $sessionsQuery = Attendance::where('course_id', $selectedCourse->id)->orderBy('start_time', 'asc');
        if ($request->filled('start_date')) {
            $sessionsQuery->whereDate('start_time', '>=', $request->query('start_date'));
        }
        if ($request->filled('end_date')) {
            $sessionsQuery->whereDate('start_time', '<=', $request->query('end_date'));
        }
        $sessions = $sessionsQuery->get();

        // Students of this course
        $studentsQuery = $selectedCourse->students()->orderBy('name');
        if ($request->filled('student_search')) {
            $s = $request->query('student_search');
            $studentsQuery->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('identity_number', 'like', "%{$s}%");
            });
        }
        $students = $studentsQuery->get();

        // All logs in these sessions
        $allLogs = AttendanceLog::whereIn('attendance_id', $sessions->pluck('id'))
            ->get()
            ->groupBy(['user_id', 'attendance_id']);

        $matrix = [];
        $summary = [
            'total_hadir' => 0,
            'total_izin' => 0,
            'total_sakit' => 0,
            'total_alpa' => 0,
        ];

        foreach ($students as $student) {
            $h = 0; $i = 0; $s = 0; $a = 0;
            $rowSessions = [];

            foreach ($sessions as $session) {
                $log = $allLogs->get($student->id)?->get($session->id)?->first();
                $status = $log ? $log->status : 'alpa';
                $rowSessions[$session->id] = [
                    'status' => $status,
                    'note' => $log?->note,
                ];

                if ($status === 'hadir') $h++;
                elseif ($status === 'izin') $i++;
                elseif ($status === 'sakit') $s++;
                else $a++;
            }

            $total = count($sessions);
            $percentage = $total > 0 ? round(($h / $total) * 100, 1) : 0;

            $matrix[$student->id] = [
                'student' => $student,
                'sessions' => $rowSessions,
                'hadir' => $h,
                'izin' => $i,
                'sakit' => $s,
                'alpa' => $a,
                'percentage' => $percentage,
            ];

            $summary['total_hadir'] += $h;
            $summary['total_izin'] += $i;
            $summary['total_sakit'] += $s;
            $summary['total_alpa'] += $a;
        }

        return view('teacher.academic.attendance.recap', compact('courses', 'selectedCourse', 'sessions', 'students', 'matrix', 'summary'));
    }

    public function export(Request $request, ExcelService $excelService)
    {
        $user = $this->checkTeacher();
        $courseId = $request->query('course_id');
        $course = Course::with(['students', 'schoolClass'])->findOrFail($courseId);

        if ($user->role === 'guru' && $course->teacher_id !== $user->id) {
            abort(403);
        }

        $sessions = Attendance::where('course_id', $course->id)->orderBy('start_time', 'asc')->get();
        $students = $course->students()->orderBy('name')->get();

        $allLogs = AttendanceLog::whereIn('attendance_id', $sessions->pluck('id'))
            ->get()
            ->groupBy(['user_id', 'attendance_id']);

        $format = $request->query('format', 'xlsx');

        if ($format === 'pdf' || $format === 'print') {
            return view('teacher.academic.attendance.print', compact('course', 'sessions', 'students', 'allLogs'));
        }

        // Export Native Excel
        $headers = ['No', 'NIM', 'Nama Siswa', 'Kelas'];
        foreach ($sessions as $idx => $s) {
            $headers[] = 'P' . ($idx + 1) . ' (' . $s->start_time->format('d/m') . ')';
        }
        $headers[] = 'Hadir';
        $headers[] = 'Izin';
        $headers[] = 'Sakit';
        $headers[] = 'Alpa';
        $headers[] = 'Persentase (%)';

        $rows = [];
        $no = 1;
        foreach ($students as $student) {
            $h = 0; $i = 0; $s = 0; $a = 0;
            $row = [
                $no++,
                $student->identity_number ?? '-',
                $student->name,
                $course->schoolClass?->name ?? 'Reguler',
            ];

            foreach ($sessions as $session) {
                $log = $allLogs->get($student->id)?->get($session->id)?->first();
                $status = $log ? strtoupper(substr($log->status, 0, 1)) : 'A';
                $row[] = $status;

                $st = $log ? $log->status : 'alpa';
                if ($st === 'hadir') $h++;
                elseif ($st === 'izin') $i++;
                elseif ($st === 'sakit') $s++;
                else $a++;
            }

            $total = count($sessions);
            $pct = $total > 0 ? round(($h / $total) * 100, 1) : 0;

            $row[] = $h;
            $row[] = $i;
            $row[] = $s;
            $row[] = $a;
            $row[] = $pct . '%';

            $rows[] = $row;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'rekap_absensi_') . '.xlsx';
        $excelService->createXlsx($headers, $rows, $tempPath);

        $filename = 'rekap_absensi_' . \Illuminate\Support\Str::slug($course->title) . '_' . date('Ymd_His') . '.xlsx';
        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }
}
