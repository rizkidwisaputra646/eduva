<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function store(Request $request, Course $course)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && ($user->role === 'siswa' || ($user->role === 'guru' && $course->teacher_id !== $user->id))) {
            abort(403, 'Anda tidak memiliki akses untuk menambahkan aktivitas pada kelas ini.');
        }

        if (!$request->filled('topic') && $request->filled('meeting_id')) {
            $request->merge(['topic' => $request->input('meeting_id')]);
        }

        $rules = [
            'topic' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:quiz,assignment',
            'duration_hours' => 'nullable|integer|min:0|max:24',
            'duration_minutes' => 'nullable|integer|min:0|max:59',
            'duration' => 'nullable|integer|min:0',
            'start_time' => 'nullable|date',
            'deadline' => $request->filled('start_time') ? 'nullable|date|after_or_equal:start_time' : 'nullable|date',
            'detect_tab_switch' => 'nullable|boolean',
        ];

        $request->validate($rules, [
            'deadline.after_or_equal' => 'Batas waktu tidak boleh lebih awal dari waktu mulai.',
        ]);

        $hours = (int)$request->input('duration_hours', 0);
        $minutes = (int)$request->input('duration_minutes', 0);
        $duration = ($hours * 60) + $minutes;
        if ($duration <= 0 && $request->filled('duration')) {
            $duration = (int)$request->input('duration');
        }
        $duration = $duration > 0 ? $duration : null;

        $course->quizzes()->create([
            'topic' => $request->topic,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'duration' => $duration,
            'detect_tab_switch' => $request->boolean('detect_tab_switch'),
            'start_time' => $request->start_time,
            'deadline' => $request->deadline,
            'is_visible' => true,
        ]);

        return back()->with('success', ucfirst($request->type) . ' berhasil ditambahkan!');
    }

    public function update(Request $request, Quiz $quiz)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_hours' => 'nullable|integer|min:0|max:24',
            'duration_minutes' => 'nullable|integer|min:0|max:59',
            'duration' => 'nullable|integer|min:0',
            'start_time' => 'nullable|date',
            'deadline' => 'nullable|date',
            'detect_tab_switch' => 'nullable|boolean',
        ]);

        $hours = (int)$request->input('duration_hours', 0);
        $minutes = (int)$request->input('duration_minutes', 0);
        $duration = ($hours * 60) + $minutes;
        if ($duration <= 0 && $request->filled('duration')) {
            $duration = (int)$request->input('duration');
        }
        $duration = $duration > 0 ? $duration : null;

        // Validasi: Durasi hanya dapat diubah selama belum ada siswa yang sedang aktif ujian
        $hasActiveSession = $quiz->examSessions()->where('status', 'in_progress')->exists();
        if ($duration !== $quiz->duration && $hasActiveSession) {
            return back()->with('error', 'Durasi tidak dapat diubah karena ada siswa yang sedang aktif mengerjakan ujian ini.');
        }

        $quiz->update([
            'topic' => $request->topic,
            'title' => $request->title,
            'description' => $request->description,
            'duration' => $duration,
            'detect_tab_switch' => $request->boolean('detect_tab_switch'),
            'start_time' => $request->start_time,
            'deadline' => $request->deadline,
        ]);

        return back()->with('success', ucfirst($quiz->type) . ' berhasil diperbarui!');
    }

    public function toggleVisibility(Quiz $quiz)
    {
        $quiz->update([
            'is_visible' => !$quiz->is_visible,
        ]);

        return back()->with('success', 'Status visibilitas berhasil diubah!');
    }

    public function destroy(Quiz $quiz)
    {
        $type = $quiz->type;
        $quiz->delete();
        return back()->with('success', ucfirst($type) . ' berhasil dihapus!');
    }

    public function show(Quiz $quiz)
    {
        $quiz->load('questions.options', 'course');
        return view('kuis.show', compact('quiz'));
    }

    public function storeQuestion(Request $request, Quiz $quiz)
    {
        $request->validate([
            'question_text' => 'required|string',
            'type' => 'required|in:multiple_choice,essay',
            'points' => 'required|integer|min:1',
        ]);

        $question = $quiz->questions()->create([
            'question_text' => $request->question_text,
            'type' => $request->type,
            'points' => $request->points,
            'explanation' => $request->explanation,
        ]);

        if ($request->type === 'multiple_choice') {
            // Validation for options
            $request->validate([
                'options' => 'required|array|min:2',
                'options.*' => 'required|string',
                'correct_option' => 'required|integer',
            ]);

            foreach ($request->options as $index => $optionText) {
                $question->options()->create([
                    'option_text' => $optionText,
                    'is_correct' => ($index == $request->correct_option),
                ]);
            }
        }

        return back()->with('success', 'Soal berhasil ditambahkan!');
    }

    public function updateQuestion(Request $request, Question $question)
    {
        $request->validate([
            'question_text' => 'required|string',
            'points' => 'required|integer|min:1',
            'explanation' => 'nullable|string',
        ]);

        $question->update([
            'question_text' => $request->question_text,
            'points' => $request->points,
            'explanation' => $request->explanation,
        ]);

        if ($question->type === 'multiple_choice') {
            $request->validate([
                'options' => 'required|array|min:2',
                'correct_option' => 'required',
            ]);

            $question->options()->delete();

            $correctIndex = $request->input('correct_option');
            foreach ($request->options as $index => $optionText) {
                if (trim($optionText) === '') continue;

                $question->options()->create([
                    'option_text' => $optionText,
                    'is_correct' => ($index == $correctIndex),
                ]);
            }
        }

        return back()->with('success', 'Soal berhasil diperbarui!');
    }

    public function destroyQuestion(\App\Models\Question $question)
    {
        $question->options()->delete();
        $question->delete();
        return back()->with('success', 'Soal berhasil dihapus!');
    }

    public function attempt(Quiz $quiz)
    {
        $userId = \Illuminate\Support\Facades\Auth::id();

        // 1. Cek apakah sudah pernah submit ujian
        $submission = \App\Models\Submission::where('quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->first();

        if ($submission) {
            return redirect()->route('kelas.show', $quiz->course_id)
                ->with('info', 'Anda sudah menyelesaikan dan mengumpulkan ujian ini.');
        }

        // 2. Cek apakah sesi ujian sudah pernah dimulai oleh siswa
        $examSession = \App\Models\ExamSession::where('quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->first();

        $quiz->load('questions.options');

        $isStarted = false;
        $remainingSeconds = -1; // -1 berarti unlimited
        $isTimedOut = false;

        if ($examSession) {
            if ($examSession->status === 'submitted') {
                return redirect()->route('kelas.show', $quiz->course_id)
                    ->with('info', 'Ujian telah dikumpulkan.');
            }

            // Jika durasi diatur, hitung sisa detik waktu server
            if ($quiz->duration > 0 && $examSession->expires_at) {
                $remainingSeconds = $examSession->remaining_seconds;
                if ($remainingSeconds <= 0) {
                    $isTimedOut = true;
                    $examSession->update(['status' => 'time_out']);
                }
            }

            $isStarted = true;
        }

        return view('kuis.kerjakan', compact('quiz', 'examSession', 'isStarted', 'remainingSeconds', 'isTimedOut'));
    }

    public function startSession(Quiz $quiz)
    {
        $userId = \Illuminate\Support\Facades\Auth::id();

        // Cek submission
        $submission = \App\Models\Submission::where('quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->first();

        if ($submission) {
            return response()->json([
                'status' => 'already_submitted',
                'message' => 'Anda sudah menyelesaikan ujian ini.',
            ], 400);
        }

        $now = now();
        $expiresAt = ($quiz->duration && $quiz->duration > 0)
            ? $now->copy()->addMinutes($quiz->duration)
            : null;

        $examSession = \App\Models\ExamSession::firstOrCreate(
            ['quiz_id' => $quiz->id, 'user_id' => $userId],
            [
                'started_at' => $now,
                'expires_at' => $expiresAt,
                'status' => 'in_progress',
            ]
        );

        return response()->json([
            'status' => 'success',
            'session_id' => $examSession->id,
            'started_at' => $examSession->started_at->toIso8601String(),
            'expires_at' => $examSession->expires_at?->toIso8601String(),
            'remaining_seconds' => $examSession->remaining_seconds,
            'duration_minutes' => $quiz->duration,
        ]);
    }

    public function logEvent(Request $request, Quiz $quiz)
    {
        if (!$quiz->detect_tab_switch) {
            return response()->json([
                'status' => 'disabled',
                'logged' => false,
                'message' => 'Deteksi pindah tab dinonaktifkan untuk ujian ini.'
            ]);
        }

        $userId = \Illuminate\Support\Facades\Auth::id();
        $log = \App\Models\ExamLog::create([
            'quiz_id' => $quiz->id,
            'user_id' => $userId,
            'event_type' => $request->input('event_type', 'tab_switch'),
            'details' => $request->input('details', 'Berpindah tab atau meninggalkan jendela browser'),
        ]);

        $totalViolations = \App\Models\ExamLog::where('quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->count();

        return response()->json([
            'status' => 'success',
            'logged' => true,
            'total_violations' => $totalViolations,
            'timestamp' => $log->created_at->format('H:i:s'),
        ]);
    }

    public function submit(\Illuminate\Http\Request $request, Quiz $quiz)
    {
        $quiz->load(['questions.options']);
        $questions = $quiz->questions;

        $rawAnswers = $request->input('answers', []);
        $uploadedFiles = $request->file('files', []);

        $answersData = [];
        $totalMaxPoints = 0;
        $totalEarnedPoints = 0;
        $hasEssay = false;
        $primaryFilePath = null;

        foreach ($questions as $q) {
            $totalMaxPoints += (float)$q->points;

            if ($q->type === 'multiple_choice') {
                $selectedOptionId = isset($rawAnswers[$q->id]) ? (int)$rawAnswers[$q->id] : null;
                $selectedOption = $selectedOptionId ? $q->options->firstWhere('id', $selectedOptionId) : null;
                $isCorrect = $selectedOption ? (bool)$selectedOption->is_correct : false;
                $pointsEarned = $isCorrect ? (float)$q->points : 0;
                $totalEarnedPoints += $pointsEarned;

                $answersData[$q->id] = [
                    'question_id' => $q->id,
                    'type' => 'multiple_choice',
                    'selected_option_id' => $selectedOptionId,
                    'selected_option_text' => $selectedOption?->option_text,
                    'is_correct' => $isCorrect,
                    'points_earned' => $pointsEarned,
                    'max_points' => (float)$q->points,
                ];
            } else {
                $hasEssay = true;
                $textAnswer = isset($rawAnswers[$q->id]) ? (string)$rawAnswers[$q->id] : null;
                $essayFilePath = null;

                if (isset($uploadedFiles[$q->id]) && $uploadedFiles[$q->id]->isValid()) {
                    $file = $uploadedFiles[$q->id];
                    $filename = time() . '_' . $q->id . '_' . $file->getClientOriginalName();
                    $essayFilePath = $file->storeAs('submissions', $filename, 'public');
                    if (!$primaryFilePath) {
                        $primaryFilePath = $essayFilePath;
                    }
                }

                $answersData[$q->id] = [
                    'question_id' => $q->id,
                    'type' => 'essay',
                    'text_answer' => $textAnswer,
                    'file_path' => $essayFilePath,
                    'points_earned' => null,
                    'max_points' => (float)$q->points,
                ];
            }
        }

        // Jika ada file yang diunggah secara umum
        if (!$primaryFilePath && $request->hasFile('files')) {
            $files = $request->file('files');
            $firstFile = is_array($files) ? reset($files) : $files;
            if ($firstFile && $firstFile->isValid()) {
                $filename = time() . '_' . $firstFile->getClientOriginalName();
                $primaryFilePath = $firstFile->storeAs('submissions', $filename, 'public');
            }
        }

        // Hitung nilai akhir:
        // Jika semua soal pilihan ganda, nilai dihitung otomatis secara proporsional (0-100)
        // Jika ada esai murni atau campuran, guru dapat memberikan/mengubah nilai akhir secara manual
        if ($totalMaxPoints > 0) {
            $calculatedScore = round(($totalEarnedPoints / $totalMaxPoints) * 100);
        } else {
            $calculatedScore = $hasEssay ? null : 100;
        }

        $userId = \Illuminate\Support\Facades\Auth::id();

        // Cek sesi ujian
        $examSession = \App\Models\ExamSession::where('quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->first();

        $isTimeOut = $request->boolean('is_timeout');
        if ($examSession && $quiz->duration > 0 && $examSession->remaining_seconds === 0) {
            $isTimeOut = true;
        }

        \App\Models\Submission::updateOrCreate(
            ['quiz_id' => $quiz->id, 'user_id' => $userId],
            [
                'score' => $calculatedScore,
                'file_path' => $primaryFilePath,
                'answers' => $answersData,
            ]
        );

        if ($examSession) {
            $examSession->update([
                'status' => $isTimeOut ? 'time_out' : 'submitted',
                'submitted_at' => now(),
            ]);
        } else {
            \App\Models\ExamSession::create([
                'quiz_id' => $quiz->id,
                'user_id' => $userId,
                'started_at' => now(),
                'expires_at' => $quiz->duration > 0 ? now() : null,
                'submitted_at' => now(),
                'status' => $isTimeOut ? 'time_out' : 'submitted',
            ]);
        }

        $successMsg = $isTimeOut 
            ? 'Waktu ujian telah berakhir. Jawaban Anda telah otomatis tersimpan dan dikumpulkan!' 
            : 'Kuis berhasil diselesaikan dan dikumpulkan!';

        return redirect()->route('kelas.show', $quiz->course_id)->with('success', $successMsg);
    }
}
