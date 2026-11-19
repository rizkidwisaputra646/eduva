<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherExamRecapController extends Controller
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

        $exams = Quiz::where('type', 'quiz')
            ->when($selectedCourseId, fn ($q) => $q->where('course_id', $selectedCourseId))
            ->whereIn('course_id', $courseIds)
            ->with(['course.students', 'submissions'])
            ->latest('start_time')
            ->get();

        $selectedExamId = $request->query('exam_id', $exams->first()?->id);
        $selectedExam = $exams->firstWhere('id', $selectedExamId);

        $examStats = [
            'total_students' => 0,
            'attempted' => 0,
            'unattempted' => 0,
            'highest' => 0,
            'lowest' => 0,
            'average' => 0,
            'distribution' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0],
        ];

        $studentRows = collect();

        if ($selectedExam) {
            $students = $selectedExam->course->students()->orderBy('name')->get();
            $submissions = $selectedExam->submissions->keyBy('user_id');

            $scores = [];
            foreach ($students as $student) {
                $sub = $submissions->get($student->id);
                $hasAttempted = !is_null($sub);
                $score = $sub?->score;

                if ($hasAttempted && !is_null($score)) {
                    $scores[] = (float) $score;
                    if ($score >= 85) $examStats['distribution']['A']++;
                    elseif ($score >= 75) $examStats['distribution']['B']++;
                    elseif ($score >= 65) $examStats['distribution']['C']++;
                    elseif ($score >= 50) $examStats['distribution']['D']++;
                    else $examStats['distribution']['E']++;
                }

                $studentRows->push([
                    'student' => $student,
                    'submission' => $sub,
                    'has_attempted' => $hasAttempted,
                    'score' => $score,
                ]);
            }

            $examStats['total_students'] = $students->count();
            $examStats['attempted'] = count($scores);
            $examStats['unattempted'] = $examStats['total_students'] - $examStats['attempted'];
            $examStats['highest'] = count($scores) > 0 ? max($scores) : 0;
            $examStats['lowest'] = count($scores) > 0 ? min($scores) : 0;
            $examStats['average'] = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0;
        }

        return view('teacher.academic.exams.index', compact(
            'courses',
            'selectedCourse',
            'exams',
            'selectedExam',
            'examStats',
            'studentRows'
        ));
    }

    public function export(Request $request, ExcelService $excelService)
    {
        $user = $this->checkTeacher();
        $examId = $request->query('exam_id');
        $exam = Quiz::with(['course.students', 'submissions.user'])->findOrFail($examId);

        if ($user->role === 'guru' && $exam->course->teacher_id !== $user->id) {
            abort(403);
        }

        $students = $exam->course->students()->orderBy('name')->get();
        $submissions = $exam->submissions->keyBy('user_id');

        $format = $request->query('format', 'xlsx');
        if ($format === 'pdf' || $format === 'print') {
            return view('teacher.academic.exams.print', compact('exam', 'students', 'submissions'));
        }

        $headers = ['No', 'NIM', 'Nama Siswa', 'Status Ujian', 'Waktu Pengerjaan', 'Nilai Ujian', 'Predikat'];
        $rows = [];
        $no = 1;

        foreach ($students as $student) {
            $sub = $submissions->get($student->id);
            $status = $sub ? 'Sudah Mengerjakan' : 'Belum Mengerjakan';
            $time = $sub ? $sub->created_at->format('d/m/Y H:i') : '-';
            $score = $sub && !is_null($sub->score) ? $sub->score : '-';

            $predicate = '-';
            if ($sub && !is_null($sub->score)) {
                $val = (float) $sub->score;
                $predicate = match (true) {
                    $val >= 85 => 'A',
                    $val >= 75 => 'B',
                    $val >= 65 => 'C',
                    $val >= 50 => 'D',
                    default => 'E',
                };
            }

            $rows[] = [
                $no++,
                $student->identity_number ?? '-',
                $student->name,
                $status,
                $time,
                $score,
                $predicate,
            ];
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'rekap_ujian_') . '.xlsx';
        $excelService->createXlsx($headers, $rows, $tempPath);

        $filename = 'rekap_ujian_' . \Illuminate\Support\Str::slug($exam->title) . '_' . date('Ymd_His') . '.xlsx';
        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }
}
