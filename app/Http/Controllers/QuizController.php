<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\ExamLog;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Services\ExamLockdownService;
use App\Services\ExamMonitoringService;
use App\Services\ExamSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    private function authorizeManagement(Quiz $quiz): void
    {
        abort_unless(auth()->user()->role === 'admin' || (auth()->user()->role === 'guru' && (int) $quiz->course->teacher_id === auth()->id()), 403);
    }

    public function store(Request $request, Course $course)
    {
        $user = Auth::user();
        if ($user && ($user->role === 'siswa' || ($user->role === 'guru' && $course->teacher_id !== $user->id))) {
            abort(403, 'Anda tidak memiliki akses untuk menambahkan aktivitas pada kelas ini.');
        }

        if (! $request->filled('topic') && $request->filled('meeting_id')) {
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

        $hours = (int) $request->input('duration_hours', 0);
        $minutes = (int) $request->input('duration_minutes', 0);
        $duration = ($hours * 60) + $minutes;
        if ($duration <= 0 && $request->filled('duration')) {
            $duration = (int) $request->input('duration');
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

        return back()->with('success', ucfirst($request->type).' berhasil ditambahkan!');
    }

    public function update(Request $request, Quiz $quiz)
    {
        $this->authorizeManagement($quiz);
        abort_unless(auth()->user()->role === 'admin' || (auth()->user()->role === 'guru' && (int) $quiz->course->teacher_id === auth()->id()), 403);
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

        $hours = (int) $request->input('duration_hours', 0);
        $minutes = (int) $request->input('duration_minutes', 0);
        $duration = ($hours * 60) + $minutes;
        if ($duration <= 0 && $request->filled('duration')) {
            $duration = (int) $request->input('duration');
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

        return back()->with('success', ucfirst($quiz->type).' berhasil diperbarui!');
    }

    public function toggleVisibility(Quiz $quiz)
    {
        $this->authorizeManagement($quiz);
        $quiz->update([
            'is_visible' => ! $quiz->is_visible,
        ]);

        return back()->with('success', 'Status visibilitas berhasil diubah!');
    }

    public function destroy(Quiz $quiz)
    {
        $this->authorizeManagement($quiz);
        $type = $quiz->type;
        $quiz->delete();

        return back()->with('success', ucfirst($type).' berhasil dihapus!');
    }

    public function show(Quiz $quiz)
    {
        $this->authorizeManagement($quiz);
        $quiz->load('questions.options', 'course');

        return view('kuis.show', compact('quiz'));
    }

    public function storeQuestion(Request $request, Quiz $quiz)
    {
        $this->authorizeManagement($quiz);
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
        $this->authorizeManagement($question->quiz);
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
                if (trim($optionText) === '') {
                    continue;
                }

                $question->options()->create([
                    'option_text' => $optionText,
                    'is_correct' => ($index == $correctIndex),
                ]);
            }
        }

        return back()->with('success', 'Soal berhasil diperbarui!');
    }

    public function destroyQuestion(Question $question)
    {
        $this->authorizeManagement($question->quiz);
        $question->options()->delete();
        $question->delete();

        return back()->with('success', 'Soal berhasil dihapus!');
    }

    public function attempt(Quiz $quiz)
    {
        app(ExamMonitoringService::class)->eligible($quiz);
        $existingPolicy = $quiz->examSessions()->where('user_id', auth()->id())->value('policy_snapshot');
        app(ExamLockdownService::class)->verify(request(), is_string($existingPolicy) ? json_decode($existingPolicy, true) : ($existingPolicy ?? ExamMonitoringService::policy($quiz)));
        $userId = Auth::id();

        // 1. Cek apakah sudah pernah submit ujian
        $submission = Submission::where('quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->first();

        if ($submission) {
            return redirect()->route('kelas.show', $quiz->course_id)
                ->with('info', 'Anda sudah menyelesaikan dan mengumpulkan ujian ini.');
        }

        // 2. Cek apakah sesi ujian sudah pernah dimulai oleh siswa
        $examSession = ExamSession::where('quiz_id', $quiz->id)
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
        app(ExamMonitoringService::class)->eligible($quiz);
        app(ExamLockdownService::class)->verify(request(), ExamMonitoringService::policy($quiz));
        abort_unless(auth()->user()->role === 'siswa' && $quiz->course->students()->where('users.id', auth()->id())->exists(), 403);
        $userId = Auth::id();

        // Cek submission
        $submission = Submission::where('quiz_id', $quiz->id)
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

        $examSession = ExamSession::firstOrCreate(
            ['quiz_id' => $quiz->id, 'user_id' => $userId],
            [
                'started_at' => $now,
                'policy_snapshot' => ExamMonitoringService::policy($quiz),
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
        $legacySession = app(ExamMonitoringService::class)->session($quiz);
        app(ExamMonitoringService::class)->validateClient($legacySession, $request->input('instance_id'));
        abort_unless($request->user()->role === 'siswa' && $quiz->course->students()->where('users.id', $request->user()->id)->exists(), 403);
        abort_unless(ExamSession::where('quiz_id', $quiz->id)->where('user_id', $request->user()->id)->where('status', 'in_progress')->whereNull('submitted_at')->exists(), 409);
        $request->validate(['event_type' => 'required|in:tab_switch', 'details' => 'nullable|string|max:500']);
        if (! $quiz->detect_tab_switch) {
            return response()->json([
                'status' => 'disabled',
                'logged' => false,
                'message' => 'Deteksi pindah tab dinonaktifkan untuk ujian ini.',
            ]);
        }

        $userId = Auth::id();
        $log = ExamLog::create([
            'quiz_id' => $quiz->id,
            'user_id' => $userId,
            'event_type' => $request->input('event_type', 'tab_switch'),
            'details' => $request->input('details', 'Berpindah tab atau meninggalkan jendela browser'),
        ]);

        $totalViolations = ExamLog::where('quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->count();

        return response()->json([
            'status' => 'success',
            'logged' => true,
            'total_violations' => $totalViolations,
            'timestamp' => $log->created_at->format('H:i:s'),
        ]);
    }

    public function submit(Request $request, Quiz $quiz)
    {
        app(ExamMonitoringService::class)->eligible($quiz);
        if (is_array($request->input('answers'))) {
            $request->merge(['answers' => array_map(fn ($v) => is_int($v) ? (string) $v : $v, $request->input('answers'))]);
        }
        $service = app(ExamMonitoringService::class);
        abort_unless(auth()->user()->role === 'siswa' && $quiz->course->students()->where('users.id', auth()->id())->exists(), 403);
        $request->validate(['answers' => 'sometimes|array|max:500', 'answers.*' => 'nullable|string|max:20000', 'files' => 'sometimes|array', 'files.*' => 'nullable|file|max:10240|mimes:pdf,doc,docx,zip,rar,png,jpg,jpeg,txt', 'instance_id' => 'nullable|uuid']);
        $s = ExamSession::firstOrCreate(['quiz_id' => $quiz->id, 'user_id' => auth()->id()], ['started_at' => now(), 'status' => 'in_progress', 'policy_snapshot' => ExamMonitoringService::policy($quiz)]);
        if ($s->submitted_at) {
            return redirect()->route('kelas.show', $quiz->course_id)->with('info', 'Ujian telah dikumpulkan.');
        }
        $service->validateClient($s, $request->input('instance_id'));
        app(ExamLockdownService::class)->verify($request, $s->policy_snapshot ?? []);
        $expired = $s->expires_at && now()->gt($s->expires_at);
        $files = $s->draft_files ?? [];
        if (! $expired) {
            foreach ($request->file('files', []) as $id => $file) {
                abort_unless($quiz->questions()->whereKey($id)->where('type', 'essay')->exists(), 422);
                $files[$id] = $file->store('submissions', 'public');
            }
        }
        $reason = ($expired || $request->boolean('is_timeout')) ? 'timeout' : 'submitted';
        app(ExamSubmissionService::class)->finish($s, $reason, $expired ? null : $request->input('answers', $s->draft_answers ?? []), $files);

        return redirect()->route('kelas.show', $quiz->course_id)->with('success', 'Jawaban ujian telah dikumpulkan.');
    }
}
