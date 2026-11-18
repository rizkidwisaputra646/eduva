<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Quiz;
use App\Services\AiQuestionGeneratorService;
use App\Services\DocumentTextExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizAiGeneratorController extends Controller
{
    public const MAX_FILE_SIZE_KB = 15360; // 15MB

    protected DocumentTextExtractor $extractor;
    protected AiQuestionGeneratorService $generator;

    public function __construct(
        DocumentTextExtractor $extractor,
        AiQuestionGeneratorService $generator
    ) {
        $this->extractor = $extractor;
        $this->generator = $generator;
    }

    /**
     * Tampilkan halaman khusus konfigurasi AI Question Generator untuk kuis terkait.
     */
    public function show(Quiz $quiz)
    {
        $quiz->load('course');
        $this->authorizeQuizOwner($quiz);

        $existingSession = session()->get($this->sessionKey($quiz->id));

        return view('kuis.ai-generator', [
            'quiz' => $quiz,
            'hasPreviewSession' => !empty($existingSession['questions']),
            'maxQuestions' => AiQuestionGeneratorService::MAX_QUESTIONS,
            'maxFileSizeMb' => (int) (self::MAX_FILE_SIZE_KB / 1024),
        ]);
    }

    /**
     * Terima dokumen + konfigurasi, ekstrak teks, dan generate soal terstruktur dengan AI.
     */
    public function generate(Request $request, Quiz $quiz)
    {
        $quiz->load('course');
        $this->authorizeQuizOwner($quiz);

        $validated = $request->validate([
            'document' => 'required|file|max:' . self::MAX_FILE_SIZE_KB,
            'question_type' => 'required|in:multiple_choice,essay',
            'question_count' => 'required|integer|min:' . AiQuestionGeneratorService::MIN_QUESTIONS . '|max:' . AiQuestionGeneratorService::MAX_QUESTIONS,
            'difficulty' => 'required|in:mudah,sedang,sulit,campuran',
        ], [
            'document.required' => 'Silakan pilih atau seret dokumen pembelajaran terlebih dahulu.',
            'document.file' => 'Dokumen yang diunggah tidak valid.',
            'document.max' => 'Ukuran dokumen maksimal 15MB.',
            'question_type.required' => 'Pilih jenis soal yang ingin dihasilkan.',
            'question_type.in' => 'Jenis soal hanya mendukung Pilihan Ganda atau Esai.',
            'question_count.required' => 'Jumlah soal wajib diisi.',
            'question_count.integer' => 'Jumlah soal harus berupa angka bulat.',
            'question_count.min' => 'Jumlah soal minimal 1.',
            'question_count.max' => 'Jumlah soal maksimal ' . AiQuestionGeneratorService::MAX_QUESTIONS . ' per generate.',
            'difficulty.required' => 'Pilih tingkat kesulitan soal.',
            'difficulty.in' => 'Tingkat kesulitan tidak valid.',
        ]);

        $file = $request->file('document');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, DocumentTextExtractor::SUPPORTED_EXTENSIONS, true)) {
            $msg = 'Format berkas tidak didukung. Gunakan PDF, DOC, DOCX, PPT, PPTX, atau TXT.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $msg], 422);
            }
            return back()->withErrors(['document' => $msg])->withInput();
        }

        $extraction = $this->extractor->extract($file, $extension);
        if (!$extraction['success']) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $extraction['error']], 422);
            }
            return back()->withErrors(['document' => $extraction['error']])->withInput();
        }

        $type = $validated['question_type'];
        $count = (int) $validated['question_count'];
        $difficulty = $validated['difficulty'];

        $generated = $this->generator->generateFromText(
            $extraction['text'],
            $type,
            $count,
            $difficulty,
            false
        );

        if (!$generated['success']) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $generated['error']], 422);
            }
            return back()->withErrors(['ai' => $generated['error']])->withInput();
        }

        session()->put($this->sessionKey($quiz->id), [
            'user_id' => Auth::id(),
            'filename' => $file->getClientOriginalName(),
            'file_format' => strtoupper($extension),
            'file_size' => $file->getSize(),
            'question_type' => $type,
            'question_count' => $count,
            'difficulty' => $difficulty,
            'source_text' => $extraction['text'],
            'questions' => $generated['questions'],
            'generated_at' => now()->toIso8601String(),
        ]);

        $previewUrl = route('quizzes.ai-generator.preview', $quiz->id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect_url' => $previewUrl,
                'count' => count($generated['questions']),
            ]);
        }

        return redirect($previewUrl)->with('info', 'AI berhasil menyusun ' . count($generated['questions']) . ' soal dari dokumen.');
    }

    /**
     * Tampilkan halaman preview & edit hasil generate AI sebelum disimpan.
     */
    public function preview(Quiz $quiz)
    {
        $quiz->load('course');
        $this->authorizeQuizOwner($quiz);

        $data = session()->get($this->sessionKey($quiz->id));
        if (!$data || ($data['user_id'] ?? null) !== Auth::id() || empty($data['questions'])) {
            return redirect()->route('quizzes.ai-generator.show', $quiz->id)
                ->with('error', 'Sesi pratinjau AI Question Generator telah berakhir atau belum tersedia.');
        }

        return view('kuis.ai-generator-preview', [
            'quiz' => $quiz,
            'filename' => $data['filename'],
            'fileFormat' => $data['file_format'],
            'questionType' => $data['question_type'],
            'requestedCount' => $data['question_count'],
            'difficulty' => $data['difficulty'],
            'questions' => $data['questions'],
        ]);
    }

    /**
     * Generate ulang soal menggunakan teks sumber & konfigurasi yang tersimpan di sesi.
     */
    public function regenerate(Request $request, Quiz $quiz)
    {
        $quiz->load('course');
        $this->authorizeQuizOwner($quiz);

        $data = session()->get($this->sessionKey($quiz->id));
        if (!$data || ($data['user_id'] ?? null) !== Auth::id() || empty($data['source_text'])) {
            $msg = 'Sesi dokumen tidak ditemukan. Silakan unggah ulang dokumen pembelajaran.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $msg], 422);
            }
            return redirect()->route('quizzes.ai-generator.show', $quiz->id)->with('error', $msg);
        }

        $generated = $this->generator->generateFromText(
            $data['source_text'],
            $data['question_type'],
            (int) $data['question_count'],
            $data['difficulty'],
            true
        );

        if (!$generated['success']) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $generated['error']], 422);
            }
            return back()->withErrors(['ai' => $generated['error']]);
        }

        $data['questions'] = $generated['questions'];
        $data['generated_at'] = now()->toIso8601String();
        session()->put($this->sessionKey($quiz->id), $data);

        $previewUrl = route('quizzes.ai-generator.preview', $quiz->id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect_url' => $previewUrl,
                'count' => count($generated['questions']),
            ]);
        }

        return redirect($previewUrl)->with('info', 'Soal berhasil di-generate ulang (' . count($generated['questions']) . ' soal).');
    }

    /**
     * Simpan soal hasil AI (yang sudah direview/diedit) ke tabel questions & options yang sudah ada.
     */
    public function store(Request $request, Quiz $quiz)
    {
        $quiz->load('course');
        $this->authorizeQuizOwner($quiz);

        $request->validate([
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'nullable|string',
            'questions.*.type' => 'required|in:multiple_choice,essay',
            'questions.*.points' => 'nullable|integer|min:1|max:100',
        ], [
            'questions.required' => 'Tidak ada soal untuk disimpan.',
            'questions.min' => 'Minimal harus ada 1 soal yang disimpan.',
        ]);

        $submitted = $request->input('questions', []);
        $activeItems = [];

        foreach ($submitted as $index => $qData) {
            if (!empty($qData['delete']) && (string) $qData['delete'] === '1') {
                continue;
            }

            $qText = trim((string) ($qData['question_text'] ?? ''));
            if ($qText === '') {
                throw ValidationException::withMessages([
                    "questions.{$index}.question_text" => 'Teks pertanyaan pada soal #' . ($index + 1) . ' tidak boleh kosong.',
                ]);
            }

            $type = $qData['type'];
            $points = (int) ($qData['points'] ?? ($type === 'multiple_choice' ? 10 : 20));
            if ($points < 1) {
                $points = 10;
            }

            if ($type === 'multiple_choice') {
                $rawOptions = is_array($qData['options'] ?? null) ? $qData['options'] : [];
                $options = [
                    'A' => trim((string) ($rawOptions['A'] ?? '')),
                    'B' => trim((string) ($rawOptions['B'] ?? '')),
                    'C' => trim((string) ($rawOptions['C'] ?? '')),
                    'D' => trim((string) ($rawOptions['D'] ?? '')),
                ];

                if ($options['A'] === '' || $options['B'] === '' || $options['C'] === '' || $options['D'] === '') {
                    throw ValidationException::withMessages([
                        "questions.{$index}.options" => 'Pilihan A, B, C, dan D pada soal #' . ($index + 1) . ' wajib diisi.',
                    ]);
                }

                $correct = strtoupper(trim((string) ($qData['correct_answer'] ?? 'A')));
                if (!in_array($correct, ['A', 'B', 'C', 'D'], true)) {
                    throw ValidationException::withMessages([
                        "questions.{$index}.correct_answer" => 'Kunci jawaban pada soal #' . ($index + 1) . ' harus A, B, C, atau D.',
                    ]);
                }

                $activeItems[] = [
                    'type' => 'multiple_choice',
                    'question_text' => $qText,
                    'points' => $points,
                    'explanation' => trim((string) ($qData['explanation'] ?? '')) ?: null,
                    'options' => $options,
                    'correct_answer' => $correct,
                ];
            } else {
                $idealAnswer = trim((string) ($qData['ideal_answer'] ?? ''));
                $rubric = trim((string) ($qData['rubric'] ?? ''));
                $rawExplanation = trim((string) ($qData['explanation'] ?? ''));

                if ($idealAnswer !== '' || $rubric !== '') {
                    $explanation = trim(implode("\n\n", array_filter([
                        $idealAnswer !== '' ? "Kunci Jawaban Ideal:\n{$idealAnswer}" : null,
                        $rubric !== '' ? "Pedoman Penilaian:\n{$rubric}" : null,
                    ])));
                } else {
                    $explanation = $rawExplanation;
                }

                $activeItems[] = [
                    'type' => 'essay',
                    'question_text' => $qText,
                    'points' => $points,
                    'explanation' => $explanation !== '' ? $explanation : null,
                ];
            }
        }

        if (empty($activeItems)) {
            throw ValidationException::withMessages([
                'questions' => 'Minimal harus ada 1 soal aktif yang disimpan ke bank soal.',
            ]);
        }

        DB::transaction(function () use ($quiz, $activeItems) {
            foreach ($activeItems as $item) {
                $question = $quiz->questions()->create([
                    'question_text' => $item['question_text'],
                    'type' => $item['type'],
                    'points' => $item['points'],
                    'explanation' => $item['explanation'],
                ]);

                if ($item['type'] === 'multiple_choice') {
                    foreach ($item['options'] as $key => $optionText) {
                        $question->options()->create([
                            'option_text' => $optionText,
                            'is_correct' => ($key === $item['correct_answer']),
                        ]);
                    }
                }
            }
        });

        session()->forget($this->sessionKey($quiz->id));

        ActivityLog::record(
            'quiz.ai_generate',
            'Guru ' . Auth::user()->name . ' menyimpan ' . count($activeItems) . ' soal dari AI Question Generator ke kuis "' . $quiz->title . '"'
        );

        return redirect()->route('quizzes.show', $quiz->id)
            ->with('success', count($activeItems) . ' soal dari AI Question Generator berhasil disimpan ke bank soal kuis!');
    }

    /**
     * Batalkan pratinjau dan bersihkan sesi sementara.
     */
    public function cancel(Quiz $quiz)
    {
        $quiz->load('course');
        $this->authorizeQuizOwner($quiz);

        session()->forget($this->sessionKey($quiz->id));

        return redirect()->route('quizzes.show', $quiz->id)
            ->with('info', 'Proses AI Question Generator dibatalkan.');
    }

    protected function authorizeQuizOwner(Quiz $quiz): void
    {
        $user = Auth::user();

        if (!$user || !in_array($user->role, ['guru', 'admin'], true)) {
            abort(403, 'Akses ditolak. Fitur AI Question Generator hanya tersedia untuk guru.');
        }

        if ($user->role === 'guru' && (!$quiz->course || (int) $quiz->course->teacher_id !== (int) $user->id)) {
            abort(403, 'Akses ditolak. Anda bukan pengampu kelas untuk kuis ini.');
        }
    }

    protected function sessionKey(int $quizId): string
    {
        return 'quiz_ai_gen_' . $quizId;
    }
}

