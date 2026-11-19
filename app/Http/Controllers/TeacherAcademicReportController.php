<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\SchoolClass;
use App\Models\Submission;
use App\Models\User;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherAcademicReportController extends Controller
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

        $selectedCourseId = $request->query('course_id', $courses->first()?->id);
        $selectedCourse = $courses->firstWhere('id', $selectedCourseId);

        $students = collect();
        $reportSummary = [];

        if ($selectedCourse) {
            $studentsQuery = $selectedCourse->students()->with(['activeClassHistory.schoolClass', 'activeClassHistory.academicYear'])->orderBy('name');
            if ($request->filled('search')) {
                $s = $request->query('search');
                $studentsQuery->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('identity_number', 'like', "%{$s}%");
                });
            }
            $students = $studentsQuery->paginate(15)->withQueryString();

            // Preload sessions & quizzes for summary
            $sessions = Attendance::where('course_id', $selectedCourse->id)->get();
            $assignments = Quiz::where('course_id', $selectedCourse->id)->where('type', 'assignment')->get();
            $exams = Quiz::where('course_id', $selectedCourse->id)->where('type', 'quiz')->get();

            $studentIds = $students->pluck('id');
            $logs = AttendanceLog::whereIn('attendance_id', $sessions->pluck('id'))
                ->whereIn('user_id', $studentIds)
                ->get()
                ->groupBy('user_id');

            $allQuizIds = $assignments->pluck('id')->merge($exams->pluck('id'));
            $submissions = Submission::whereIn('quiz_id', $allQuizIds)
                ->whereIn('user_id', $studentIds)
                ->get()
                ->groupBy(['user_id', 'quiz_id']);

            foreach ($students as $student) {
                // Kehadiran
                $sLogs = $logs->get($student->id, collect());
                $hCount = $sLogs->where('status', 'hadir')->count();
                $attPct = $sessions->count() > 0 ? round(($hCount / $sessions->count()) * 100, 1) : 0;

                // Tugas
                $asgnScores = [];
                foreach ($assignments as $a) {
                    $sub = $submissions->get($student->id)?->get($a->id)?->first();
                    if ($sub && !is_null($sub->score)) $asgnScores[] = $sub->score;
                }
                $avgAsgn = count($asgnScores) > 0 ? round(array_sum($asgnScores) / count($asgnScores), 1) : 0;

                // Ujian
                $exmScores = [];
                foreach ($exams as $e) {
                    $sub = $submissions->get($student->id)?->get($e->id)?->first();
                    if ($sub && !is_null($sub->score)) $exmScores[] = $sub->score;
                }
                $avgExm = count($exmScores) > 0 ? round(array_sum($exmScores) / count($exmScores), 1) : 0;

                // Nilai Akhir (40% Tugas, 60% Ujian)
                $finalScore = round(($avgAsgn * 0.4) + ($avgExm * 0.6), 1);

                $reportSummary[$student->id] = [
                    'attendance_pct' => $attPct,
                    'avg_assignment' => $avgAsgn,
                    'avg_exam' => $avgExm,
                    'final_score' => $finalScore,
                ];
            }
        }

        return view('teacher.academic.reports.index', compact(
            'courses',
            'selectedCourse',
            'students',
            'reportSummary'
        ));
    }

    public function studentDetail(User $user, Request $request)
    {
        $teacher = $this->checkTeacher();
        $courses = $this->getTeacherCourses($teacher);

        $selectedCourseId = $request->query('course_id', $courses->first()?->id);
        $course = $courses->firstWhere('id', $selectedCourseId);

        if (!$course) {
            abort(404, 'Mata pelajaran tidak ditemukan.');
        }

        // Pastikan siswa terdaftar pada kelas ini
        if (!$course->students()->where('course_user.user_id', $user->id)->exists()) {
            abort(404, 'Siswa tidak terdaftar pada mata pelajaran ini.');
        }

        $user->load(['activeClassHistory.schoolClass', 'activeClassHistory.academicYear']);

        // 1. Data Kehadiran
        $sessions = Attendance::where('course_id', $course->id)->orderBy('start_time', 'asc')->get();
        $studentLogs = AttendanceLog::whereIn('attendance_id', $sessions->pluck('id'))
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('attendance_id');

        $attCounts = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0];
        foreach ($sessions as $s) {
            $log = $studentLogs->get($s->id);
            $status = $log ? $log->status : 'alpa';
            $attCounts[$status]++;
        }
        $totalSessions = $sessions->count();
        $attPct = $totalSessions > 0 ? round(($attCounts['hadir'] / $totalSessions) * 100, 1) : 0;

        // 2. Data Tugas
        $assignments = Quiz::where('course_id', $course->id)
            ->where('type', 'assignment')
            ->with(['submissions' => fn ($q) => $q->where('user_id', $user->id)])
            ->orderBy('deadline', 'asc')
            ->get();

        $submittedAssignmentsCount = 0;
        $assignmentScores = [];
        foreach ($assignments as $a) {
            $sub = $a->submissions->first();
            if ($sub) {
                $submittedAssignmentsCount++;
                if (!is_null($sub->score)) $assignmentScores[] = $sub->score;
            }
        }
        $avgAssignment = count($assignmentScores) > 0 ? round(array_sum($assignmentScores) / count($assignmentScores), 1) : 0;

        // 3. Data Ujian
        $exams = Quiz::where('course_id', $course->id)
            ->where('type', 'quiz')
            ->with(['submissions' => fn ($q) => $q->where('user_id', $user->id)])
            ->orderBy('deadline', 'asc')
            ->get();

        $attemptedExamsCount = 0;
        $examScores = [];
        foreach ($exams as $e) {
            $sub = $e->submissions->first();
            if ($sub) {
                $attemptedExamsCount++;
                if (!is_null($sub->score)) $examScores[] = $sub->score;
            }
        }
        $avgExam = count($examScores) > 0 ? round(array_sum($examScores) / count($examScores), 1) : 0;

        // Nilai Akhir (40% + 60%)
        $finalScore = round(($avgAssignment * 0.4) + ($avgExam * 0.6), 1);
        $predicate = match (true) {
            $finalScore >= 85 => 'A',
            $finalScore >= 75 => 'B',
            $finalScore >= 65 => 'C',
            $finalScore >= 50 => 'D',
            default => 'E',
        };

        return view('teacher.academic.reports.student', compact(
            'user',
            'course',
            'sessions',
            'studentLogs',
            'attCounts',
            'attPct',
            'assignments',
            'submittedAssignmentsCount',
            'avgAssignment',
            'exams',
            'attemptedExamsCount',
            'avgExam',
            'finalScore',
            'predicate'
        ));
    }

    public function export(Request $request, ExcelService $excelService)
    {
        $teacher = $this->checkTeacher();
        $courseId = $request->query('course_id');
        $course = Course::with(['students.activeClassHistory.schoolClass', 'schoolClass'])->findOrFail($courseId);

        if ($teacher->role === 'guru' && $course->teacher_id !== $teacher->id) {
            abort(403);
        }

        $sessions = Attendance::where('course_id', $course->id)->get();
        $assignments = Quiz::where('course_id', $course->id)->where('type', 'assignment')->get();
        $exams = Quiz::where('course_id', $course->id)->where('type', 'quiz')->get();

        $students = $course->students()->orderBy('name')->get();

        $allQuizIds = $assignments->pluck('id')->merge($exams->pluck('id'));
        $submissions = Submission::whereIn('quiz_id', $allQuizIds)->get()->groupBy(['user_id', 'quiz_id']);
        $logs = AttendanceLog::whereIn('attendance_id', $sessions->pluck('id'))->get()->groupBy('user_id');

        $format = $request->query('format', 'xlsx');
        if ($format === 'pdf' || $format === 'print') {
            return view('teacher.academic.reports.print', compact(
                'course',
                'students',
                'sessions',
                'assignments',
                'exams',
                'submissions',
                'logs'
            ));
        }

        $headers = [
            'No',
            'NIM',
            'Nama Siswa',
            'Kelas',
            'Kehadiran (%)',
            'Rata-rata Tugas',
            'Rata-rata Ujian',
            'Nilai Akhir',
            'Predikat',
            'Status Kelengkapan Tugas'
        ];

        $rows = [];
        $no = 1;

        foreach ($students as $student) {
            $sLogs = $logs->get($student->id, collect());
            $hCount = $sLogs->where('status', 'hadir')->count();
            $attPct = $sessions->count() > 0 ? round(($hCount / $sessions->count()) * 100, 1) : 0;

            $asgnScores = [];
            $submittedAsgnCount = 0;
            foreach ($assignments as $a) {
                $sub = $submissions->get($student->id)?->get($a->id)?->first();
                if ($sub) {
                    $submittedAsgnCount++;
                    if (!is_null($sub->score)) $asgnScores[] = $sub->score;
                }
            }
            $avgAsgn = count($asgnScores) > 0 ? round(array_sum($asgnScores) / count($asgnScores), 1) : 0;

            $exmScores = [];
            foreach ($exams as $e) {
                $sub = $submissions->get($student->id)?->get($e->id)?->first();
                if ($sub && !is_null($sub->score)) $exmScores[] = $sub->score;
            }
            $avgExm = count($exmScores) > 0 ? round(array_sum($exmScores) / count($exmScores), 1) : 0;

            $finalScore = round(($avgAsgn * 0.4) + ($avgExm * 0.6), 1);
            $predicate = match (true) {
                $finalScore >= 85 => 'A',
                $finalScore >= 75 => 'B',
                $finalScore >= 65 => 'C',
                $finalScore >= 50 => 'D',
                default => 'E',
            };

            $asgnStatus = $assignments->count() > 0
                ? "{$submittedAsgnCount}/{$assignments->count()} Selesai"
                : 'Tidak Ada Tugas';

            $rows[] = [
                $no++,
                $student->identity_number ?? '-',
                $student->name,
                $student->activeClassHistory?->schoolClass?->name ?? ($course->schoolClass?->name ?? 'Reguler'),
                $attPct . '%',
                $avgAsgn,
                $avgExm,
                $finalScore,
                $predicate,
                $asgnStatus,
            ];
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'laporan_akademik_') . '.xlsx';
        $excelService->createXlsx($headers, $rows, $tempPath);

        $filename = 'laporan_akademik_' . \Illuminate\Support\Str::slug($course->title) . '_' . date('Ymd_His') . '.xlsx';
        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }
}
