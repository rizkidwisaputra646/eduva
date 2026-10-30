<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function store(Request $request, Course $course)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:quiz,assignment',
            'start_time' => 'nullable|date',
            'deadline' => 'nullable|date',
        ]);

        $course->quizzes()->create([
            'topic' => $request->topic,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
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
            'start_time' => 'nullable|date',
            'deadline' => 'nullable|date',
        ]);

        $quiz->update([
            'topic' => $request->topic,
            'title' => $request->title,
            'description' => $request->description,
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

    public function destroyQuestion(\App\Models\Question $question)
    {
        $question->options()->delete();
        $question->delete();
        return back()->with('success', 'Soal berhasil dihapus!');
    }

    public function attempt(Quiz $quiz)
    {
        // View for student to take the quiz
        $quiz->load('questions.options');
        return view('kuis.kerjakan', compact('quiz'));
    }

    public function submit(\Illuminate\Http\Request $request, Quiz $quiz)
    {
        $filePath = null;

        if ($request->hasFile('files')) {
            // Cukup ambil file pertama yang diunggah untuk demonstrasi/MVP
            $files = $request->file('files');
            $firstFile = reset($files);
            if ($firstFile) {
                $filename = time() . '_' . $firstFile->getClientOriginalName();
                $filePath = $firstFile->storeAs('submissions', $filename, 'public');
            }
        }

        // Handle quiz PG/Esai submission
        \App\Models\Submission::updateOrCreate(
            ['quiz_id' => $quiz->id, 'user_id' => \Illuminate\Support\Facades\Auth::id()],
            [
                'score' => 100, // Dummy score for now
                'file_path' => $filePath
            ]
        );

        return redirect()->route('kelas.show', $quiz->course_id)->with('success', 'Kuis berhasil diselesaikan!');
    }
}
