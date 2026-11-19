<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Course;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\SchoolClass;
use App\Models\StudentClassHistory;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAcademicMonitoringController extends Controller
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

        $academicYears = AcademicYear::orderBy('is_active', 'desc')->orderBy('name', 'desc')->get();
        $activeYear = AcademicYear::getActive();

        $selectedYearId = $request->query('academic_year_id', $activeYear?->id);

        // 1. Statistik Kunci
        $stats = [
            'total_classes' => SchoolClass::when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))->count(),
            'total_students' => User::where('role', 'siswa')->count(),
            'total_teachers' => User::where('role', 'guru')->count(),
            'active_accounts' => User::where('is_active', true)->count(),
            'total_placed_students' => StudentClassHistory::where('status', 'active')
                ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
                ->distinct('user_id')->count('user_id'),
            'total_courses' => Course::count(),
            'total_materials' => Material::count(),
            'total_assignments' => Quiz::where('type', 'assignment')->count(),
            'total_exams' => Quiz::where('type', 'quiz')->count(),
        ];

        // 2. Distribusi Siswa per Kelas
        $classesDistribution = SchoolClass::when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->with(['homeroomTeacher', 'academicYear'])
            ->withCount(['activeStudentHistories as students_count'])
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        // 3. Ringkasan Kehadiran Global Sekolah
        $attendanceLogsCount = AttendanceLog::count();
        $attendanceStats = [
            'hadir' => AttendanceLog::where('status', 'hadir')->count(),
            'izin' => AttendanceLog::where('status', 'izin')->count(),
            'sakit' => AttendanceLog::where('status', 'sakit')->count(),
            'alpa' => AttendanceLog::where('status', 'alpa')->count(),
        ];
        $attendanceStats['total'] = array_sum($attendanceStats);
        $attendanceStats['rate'] = $attendanceStats['total'] > 0
            ? round(($attendanceStats['hadir'] / $attendanceStats['total']) * 100, 1)
            : 0;

        // 4. Aktivitas Pengumpulan & Penilaian Tugas
        $submissionStats = [
            'total_submissions' => Submission::count(),
            'graded_submissions' => Submission::whereNotNull('score')->count(),
            'pending_submissions' => Submission::whereNull('score')->count(),
        ];

        return view('admin.academic.monitoring.index', compact(
            'academicYears',
            'activeYear',
            'selectedYearId',
            'stats',
            'classesDistribution',
            'attendanceStats',
            'submissionStats'
        ));
    }
}
