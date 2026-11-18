<?php

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\WordQuestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuizImportController extends Controller
{
    protected WordQuestionService $wordService;

    public function __construct(WordQuestionService $wordService)
    {
        $this->wordService = $wordService;
    }

    /**
     * Download .docx template for multiple choice or essay questions
     */
    public function downloadTemplate(string $type): BinaryFileResponse
    {
        if (!in_array($type, ['multiple_choice', 'essay'])) {
            abort(404, 'Tipe template tidak dikenali.');
        }

        $filePath = $this->wordService->generateTemplate($type);
        $fileName = ($type === 'multiple_choice')
            ? 'Template_Soal_Pilihan_Ganda_Eduva.docx'
            : 'Template_Soal_Essay_Eduva.docx';

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Handle document upload, parse questions, and store in session for preview
     */
    public function upload(Request $request, Quiz $quiz)
    {
        $request->validate([
            'question_type' => 'required|in:multiple_choice,essay',
            'document' => 'required|file|max:10240',
        ], [
            'document.required' => 'Silakan pilih berkas dokumen Word (.docx) terlebih dahulu.',
            'document.max' => 'Ukuran berkas maksimal 10MB.',
            'question_type.required' => 'Pilih jenis soal yang akan diimpor.',
        ]);

        $file = $request->file('document');
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension !== 'docx') {
            return back()->withErrors(['document' => 'Format berkas harus Microsoft Word (.docx).']);
        }

        $type = $request->input('question_type');
        $parsed = $this->wordService->parseDocx($file, $type);

        if (!$parsed['success'] || empty($parsed['questions'])) {
            return back()->withErrors([
                'document' => $parsed['message'] ?? 'Format dokumen Word tidak sesuai atau tidak ditemukan soal yang valid.'
            ]);
        }

        // Store parsed questions in session for preview & editing
        $sessionKey = 'quiz_import_' . $quiz->id;
        session()->put($sessionKey, [
            'type' => $type,
            'filename' => $file->getClientOriginalName(),
            'questions' => $parsed['questions'],
            'errors' => $parsed['errors'] ?? [],
        ]);

        return redirect()->route('quizzes.import.preview', $quiz->id)
            ->with('info', $parsed['message']);
    }

    /**
     * Display the preview of parsed questions for teacher review and inline editing
     */
    public function preview(Quiz $quiz)
    {
        $quiz->load('course');
        $sessionKey = 'quiz_import_' . $quiz->id;
        $importData = session()->get($sessionKey);

        if (!$importData || empty($importData['questions'])) {
            return redirect()->route('quizzes.show', $quiz->id)
                ->with('error', 'Sesi pratinjau impor telah berakhir atau tidak ada data.');
        }

        return view('kuis.import-preview', [
            'quiz' => $quiz,
            'type' => $importData['type'],
            'filename' => $importData['filename'],
            'questions' => $importData['questions'],
            'warningNotes' => $importData['errors'] ?? [],
        ]);
    }

    /**
     * Confirm and persist approved questions into the database
     */
    public function confirm(Request $request, Quiz $quiz)
    {
        $request->validate([
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.type' => 'required|in:multiple_choice,essay',
            'questions.*.points' => 'required|numeric|min:1',
        ], [
            'questions.required' => 'Tidak ada soal yang dipilih untuk diimpor.',
            'questions.min' => 'Minimal harus ada 1 soal yang diimpor.',
        ]);

        $submittedQuestions = $request->input('questions');
        $importedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($submittedQuestions as $qData) {
                // If user excluded or marked deleted
                if (isset($qData['delete']) && $qData['delete'] == '1') {
                    continue;
                }

                $question = $quiz->questions()->create([
                    'question_text' => $qData['question_text'],
                    'type' => $qData['type'],
                    'points' => (int)$qData['points'],
                    'explanation' => $qData['explanation'] ?? null,
                ]);

                if ($qData['type'] === 'multiple_choice' && !empty($qData['options'])) {
                    $correctAnswer = strtoupper(trim($qData['correct_answer'] ?? 'A'));

                    foreach ($qData['options'] as $key => $optionText) {
                        if (trim($optionText) === '') continue;

                        $question->options()->create([
                            'option_text' => $optionText,
                            'is_correct' => (strtoupper(trim($key)) === $correctAnswer),
                        ]);
                    }
                }

                $importedCount++;
            }

            DB::commit();

            // Clear session
            session()->forget('quiz_import_' . $quiz->id);

            return redirect()->route('quizzes.show', $quiz->id)
                ->with('success', "Berhasil mengimpor {$importedCount} soal dari dokumen Word ke dalam ujian!");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['general' => 'Terjadi kesalahan saat menyimpan soal: ' . $e->getMessage()]);
        }
    }
}
