<?php

namespace App\Http\Controllers;

use App\Models\ExamLog;
use App\Models\Quiz;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubmissionController extends Controller
{
    public function store(Request $request, Quiz $quiz)
    {
        abort_unless($quiz->type === 'assignment' && auth()->user()->role === 'siswa' && $quiz->course->students()->where('users.id', auth()->id())->exists(), 403);
        $request->validate([
            'submission_file' => 'required|file|mimes:pdf,doc,docx,zip,rar|max:10240',
        ]);

        $file = $request->file('submission_file');
        $filename = time().'_'.$file->getClientOriginalName();
        $filePath = $file->storeAs('submissions', $filename, 'public');

        Submission::updateOrCreate(
            ['quiz_id' => $quiz->id, 'user_id' => Auth::id()],
            ['file_path' => $filePath]
        );

        return back()->with('success', 'Tugas berhasil dikumpulkan!');
    }

    public function index(Quiz $quiz)
    {
        abort_unless(auth()->user()->role === 'admin' || (auth()->user()->role === 'guru' && (int) $quiz->course->teacher_id === auth()->id()), 403);
        // For teacher to view submissions
        $quiz->load(['course', 'questions.options']);
        $submissions = Submission::where('quiz_id', $quiz->id)->with('user')->get();

        // Load exam logs grouped by user_id for anti-cheat violation tracking
        $examLogs = ExamLog::where('quiz_id', $quiz->id)
            ->where(fn ($q) => $q->where('counted', true)->orWhere('event_type', 'tab_switch'))
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('user_id');

        return view('submissions.index', compact('quiz', 'submissions', 'examLogs'));
    }

    public function grade(Request $request, Submission $submission)
    {
        abort_unless(auth()->user()->role === 'admin' || (auth()->user()->role === 'guru' && (int) $submission->quiz->course->teacher_id === auth()->id()), 403);
        $request->validate([
            'score' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $submission->update([
            'score' => $request->score,
            'feedback' => $request->feedback,
        ]);

        return back()->with('success', 'Nilai dan masukan untuk siswa berhasil disimpan!');
    }
}
