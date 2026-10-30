<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function store(\Illuminate\Http\Request $request, \App\Models\Quiz $quiz)
    {
        $request->validate([
            'submission_file' => 'required|file|mimes:pdf,doc,docx,zip,rar|max:10240',
        ]);

        $file = $request->file('submission_file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('submissions', $filename, 'public');

        \App\Models\Submission::updateOrCreate(
            ['quiz_id' => $quiz->id, 'user_id' => \Illuminate\Support\Facades\Auth::id()],
            ['file_path' => $filePath]
        );

        return back()->with('success', 'Tugas berhasil dikumpulkan!');
    }

    public function index(\App\Models\Quiz $quiz)
    {
        // For teacher to view submissions
        $submissions = \App\Models\Submission::where('quiz_id', $quiz->id)->with('user')->get();
        return view('submissions.index', compact('quiz', 'submissions'));
    }

    public function grade(\Illuminate\Http\Request $request, \App\Models\Submission $submission)
    {
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
