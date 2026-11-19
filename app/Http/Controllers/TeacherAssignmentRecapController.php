<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherAssignmentRecapController extends Controller
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

        $selectedCourseId = $request->query('course_id', $courses->first()?->id);
        $selectedCourse = $courses->firstWhere('id', $selectedCourseId);

        $assignments = Quiz::where('type', 'assignment')
            ->when($selectedCourseId, fn ($q) => $q->where('course_id', $selectedCourseId))
            ->whereIn('course_id', $courseIds)
            ->with(['course.students', 'submissions.user'])
            ->latest('deadline')
            ->get();

        $selectedAssignmentId = $request->query('assignment_id', $assignments->first()?->id);
        $selectedAssignment = $assignments->firstWhere('id', $selectedAssignmentId);

        $submissionRows = collect();

        if ($selectedAssignment) {
            $students = $selectedAssignment->course->students()->orderBy('name')->get();
            $submissions = $selectedAssignment->submissions->keyBy('user_id');

            foreach ($students as $student) {
                $sub = $submissions->get($student->id);
                $status = 'belum';

                if ($sub) {
                    if (!is_null($sub->score)) {
                        $status = 'dinilai';
                    } else {
                        $status = ($selectedAssignment->deadline && $sub->created_at > $selectedAssignment->deadline)
                            ? 'terlambat'
                            : 'sudah';
                    }
                }

                // Filter status
                if ($request->filled('status') && $request->query('status') !== $status) {
                    continue;
                }

                $submissionRows->push([
                    'student' => $student,
                    'submission' => $sub,
                    'status' => $status,
                ]);
            }
        }

        return view('teacher.academic.assignments.index', compact(
            'courses',
            'selectedCourse',
            'assignments',
            'selectedAssignment',
            'submissionRows'
        ));
    }

    public function export(Request $request, ExcelService $excelService)
    {
        $user = $this->checkTeacher();
        $assignmentId = $request->query('assignment_id');
        $assignment = Quiz::with(['course.students', 'submissions.user'])->findOrFail($assignmentId);

        if ($user->role === 'guru' && $assignment->course->teacher_id !== $user->id) {
            abort(403);
        }

        $students = $assignment->course->students()->orderBy('name')->get();
        $submissions = $assignment->submissions->keyBy('user_id');

        $format = $request->query('format', 'xlsx');
        if ($format === 'pdf' || $format === 'print') {
            return view('teacher.academic.assignments.print', compact('assignment', 'students', 'submissions'));
        }

        $headers = ['No', 'NIM', 'Nama Siswa', 'Status Pengumpulan', 'Waktu Pengumpulan', 'Nilai', 'Feedback Pengajar'];
        $rows = [];
        $no = 1;

        foreach ($students as $student) {
            $sub = $submissions->get($student->id);
            $statusText = 'Belum Mengumpulkan';
            $submittedAt = '-';
            $score = '-';
            $feedback = '-';

            if ($sub) {
                $submittedAt = $sub->created_at->format('d/m/Y H:i');
                if (!is_null($sub->score)) {
                    $statusText = 'Sudah Dinilai';
                    $score = $sub->score;
                } elseif ($assignment->deadline && $sub->created_at > $assignment->deadline) {
                    $statusText = 'Terlambat';
                } else {
                    $statusText = 'Sudah Mengumpulkan';
                }
                $feedback = $sub->feedback ?? '-';
            }

            $rows[] = [
                $no++,
                $student->identity_number ?? '-',
                $student->name,
                $statusText,
                $submittedAt,
                $score,
                $feedback,
            ];
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'rekap_tugas_') . '.xlsx';
        $excelService->createXlsx($headers, $rows, $tempPath);

        $filename = 'rekap_tugas_' . \Illuminate\Support\Str::slug($assignment->title) . '_' . date('Ymd_His') . '.xlsx';
        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }
}
