<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeacherGradeRecapController extends Controller
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

        if (!$selectedCourse) {
            return view('teacher.academic.grades.index', [
                'courses' => $courses,
                'selectedCourse' => null,
                'students' => collect(),
                'assignments' => collect(),
                'exams' => collect(),
                'gradeData' => [],
                'assignmentWeight' => 40,
                'examWeight' => 60,
            ]);
        }

        // Bobot penilaian (default: 40% Tugas, 60% Ujian)
        $assignmentWeight = (int) $request->query('assignment_weight', 40);
        $examWeight = 100 - $assignmentWeight;

        $assignments = Quiz::where('course_id', $selectedCourse->id)
            ->where('type', 'assignment')
            ->orderBy('deadline', 'asc')
            ->get();

        $exams = Quiz::where('course_id', $selectedCourse->id)
            ->where('type', 'quiz')
            ->orderBy('deadline', 'asc')
            ->get();

        $studentsQuery = $selectedCourse->students()->orderBy('name');
        if ($request->filled('student_search')) {
            $s = $request->query('student_search');
            $studentsQuery->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('identity_number', 'like', "%{$s}%");
            });
        }
        $students = $studentsQuery->get();

        // Load all submissions for this course's quizzes
        $allQuizIds = $assignments->pluck('id')->merge($exams->pluck('id'));
        $submissions = Submission::whereIn('quiz_id', $allQuizIds)
            ->get()
            ->groupBy(['user_id', 'quiz_id']);

        $gradeData = [];
        $classTotalFinal = 0;
        $gradedStudentCount = 0;

        foreach ($students as $student) {
            $assignmentScores = [];
            foreach ($assignments as $a) {
                $sub = $submissions->get($student->id)?->get($a->id)?->first();
                $assignmentScores[$a->id] = $sub ? $sub->score : null;
            }

            $examScores = [];
            foreach ($exams as $e) {
                $sub = $submissions->get($student->id)?->get($e->id)?->first();
                $examScores[$e->id] = $sub ? $sub->score : null;
            }

            // Hitung rata-rata tugas
            $validAssignmentScores = array_filter($assignmentScores, fn ($v) => !is_null($v));
            $avgAssignment = count($validAssignmentScores) > 0
                ? round(array_sum($validAssignmentScores) / count($validAssignmentScores), 1)
                : 0;

            // Hitung rata-rata ujian
            $validExamScores = array_filter($examScores, fn ($v) => !is_null($v));
            $avgExam = count($validExamScores) > 0
                ? round(array_sum($validExamScores) / count($validExamScores), 1)
                : 0;

            // Nilai Akhir
            $finalScore = round(($avgAssignment * ($assignmentWeight / 100)) + ($avgExam * ($examWeight / 100)), 1);

            // Predikat
            $predicate = match (true) {
                $finalScore >= 85 => 'A',
                $finalScore >= 75 => 'B',
                $finalScore >= 65 => 'C',
                $finalScore >= 50 => 'D',
                default => 'E',
            };

            if (count($validAssignmentScores) > 0 || count($validExamScores) > 0) {
                $classTotalFinal += $finalScore;
                $gradedStudentCount++;
            }

            $gradeData[$student->id] = [
                'student' => $student,
                'assignment_scores' => $assignmentScores,
                'avg_assignment' => $avgAssignment,
                'exam_scores' => $examScores,
                'avg_exam' => $avgExam,
                'final_score' => $finalScore,
                'predicate' => $predicate,
            ];
        }

        $classAverage = $gradedStudentCount > 0 ? round($classTotalFinal / $gradedStudentCount, 1) : 0;

        return view('teacher.academic.grades.index', compact(
            'courses',
            'selectedCourse',
            'assignments',
            'exams',
            'students',
            'gradeData',
            'assignmentWeight',
            'examWeight',
            'classAverage'
        ));
    }

    public function updateScore(Request $request)
    {
        $user = $this->checkTeacher();

        $validated = $request->validate([
            'submission_id' => 'nullable|exists:submissions,id',
            'quiz_id' => 'required_without:submission_id|nullable|exists:quizzes,id',
            'user_id' => 'required_without:submission_id|nullable|exists:users,id',
            'score' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:1000',
        ]);

        if (!empty($validated['submission_id'])) {
            $submission = Submission::with('quiz.course', 'user')->findOrFail($validated['submission_id']);
            $quiz = $submission->quiz;
            $student = $submission->user;

            if ($user->role === 'guru' && $quiz->course->teacher_id !== $user->id) {
                abort(403, 'Akses ditolak.');
            }

            $submission->update([
                'score' => $validated['score'],
                'feedback' => $validated['feedback'] ?? $submission->feedback,
            ]);
        } else {
            $quiz = Quiz::with('course')->findOrFail($validated['quiz_id']);
            if ($user->role === 'guru' && $quiz->course->teacher_id !== $user->id) {
                abort(403, 'Akses ditolak.');
            }

            $student = User::findOrFail($validated['user_id']);

            $submission = Submission::updateOrCreate(
                [
                    'quiz_id' => $quiz->id,
                    'user_id' => $student->id,
                ],
                [
                    'score' => $validated['score'],
                    'feedback' => $validated['feedback'] ?? null,
                ]
            );
        }

        ActivityLog::record(
            'grade.update',
            "Guru {$user->name} memperbarui nilai siswa {$student->name} pada {$quiz->title}: {$validated['score']}"
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Nilai {$student->name} untuk {$quiz->title} berhasil disimpan.",
                'score' => $submission->score,
            ]);
        }

        return back()->with('success', "Nilai {$student->name} untuk {$quiz->title} berhasil disimpan.");
    }

    public function export(Request $request, ExcelService $excelService)
    {
        $user = $this->checkTeacher();
        $courseId = $request->query('course_id');
        $course = Course::with(['students', 'schoolClass'])->findOrFail($courseId);

        if ($user->role === 'guru' && $course->teacher_id !== $user->id) {
            abort(403);
        }

        $assignmentWeight = (int) $request->query('assignment_weight', 40);
        $examWeight = 100 - $assignmentWeight;

        $assignments = Quiz::where('course_id', $course->id)->where('type', 'assignment')->get();
        $exams = Quiz::where('course_id', $course->id)->where('type', 'quiz')->get();
        $students = $course->students()->orderBy('name')->get();

        $allQuizIds = $assignments->pluck('id')->merge($exams->pluck('id'));
        $submissions = Submission::whereIn('quiz_id', $allQuizIds)->get()->groupBy(['user_id', 'quiz_id']);

        $format = $request->query('format', 'xlsx');

        if ($format === 'pdf' || $format === 'print') {
            return view('teacher.academic.grades.print', compact(
                'course',
                'assignments',
                'exams',
                'students',
                'submissions',
                'assignmentWeight',
                'examWeight'
            ));
        }

        $headers = ['No', 'NIM', 'Nama Siswa', 'Kelas', 'Rata-rata Tugas (' . $assignmentWeight . '%)', 'Rata-rata Ujian (' . $examWeight . '%)', 'Nilai Akhir', 'Predikat'];
        $rows = [];
        $no = 1;

        foreach ($students as $student) {
            $assignmentScores = [];
            foreach ($assignments as $a) {
                $sub = $submissions->get($student->id)?->get($a->id)?->first();
                if ($sub && !is_null($sub->score)) $assignmentScores[] = $sub->score;
            }

            $examScores = [];
            foreach ($exams as $e) {
                $sub = $submissions->get($student->id)?->get($e->id)?->first();
                if ($sub && !is_null($sub->score)) $examScores[] = $sub->score;
            }

            $avgAssignment = count($assignmentScores) > 0 ? round(array_sum($assignmentScores) / count($assignmentScores), 1) : 0;
            $avgExam = count($examScores) > 0 ? round(array_sum($examScores) / count($examScores), 1) : 0;
            $finalScore = round(($avgAssignment * ($assignmentWeight / 100)) + ($avgExam * ($examWeight / 100)), 1);

            $predicate = match (true) {
                $finalScore >= 85 => 'A',
                $finalScore >= 75 => 'B',
                $finalScore >= 65 => 'C',
                $finalScore >= 50 => 'D',
                default => 'E',
            };

            $rows[] = [
                $no++,
                $student->identity_number ?? '-',
                $student->name,
                $course->schoolClass?->name ?? 'Reguler',
                $avgAssignment,
                $avgExam,
                $finalScore,
                $predicate,
            ];
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'rekap_nilai_') . '.xlsx';
        $excelService->createXlsx($headers, $rows, $tempPath);

        $filename = 'rekap_nilai_' . \Illuminate\Support\Str::slug($course->title) . '_' . date('Ymd_His') . '.xlsx';
        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }
}
