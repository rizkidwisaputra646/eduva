<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\AiEvaluation;
use App\Models\AiMessage;
use App\Models\Course;
use App\Models\Material;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AiTutorController extends Controller
{
    protected GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Display the AI Tutor interface.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Get available materials according to user's role
        if ($user->role === 'guru') {
            $courses = Course::where('teacher_id', $user->id)
                ->with(['materials' => function ($q) {
                    $q->orderBy('topic')->orderBy('order');
                }])
                ->get();
        } else {
            // For students, get enrolled courses or all available courses
            $courses = Course::whereHas('students', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->with(['materials' => function ($q) {
                $q->where('is_visible', true)->orderBy('topic')->orderBy('order');
            }])->get();

            // Fallback: if student hasn't joined any, load public/all courses
            if ($courses->isEmpty()) {
                $courses = Course::with(['materials' => function ($q) {
                    $q->where('is_visible', true)->orderBy('topic')->orderBy('order');
                }])->get();
            }
        }

        // 2. Load user's conversations
        $conversations = AiConversation::where('user_id', $user->id)
            ->with(['material', 'course'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // 3. Determine active conversation
        $activeConversation = null;
        if ($request->filled('conversation_id')) {
            $activeConversation = $conversations->firstWhere('id', $request->conversation_id);
        }

        // Pre-selection of material if provided via query (?material_id=...)
        $preselectedMaterialId = $request->query('material_id');
        $preselectedCourseId = $request->query('course_id');

        if (!$activeConversation && $conversations->isNotEmpty() && !$request->has('new')) {
            $activeConversation = $conversations->first();
        }

        // Load messages for active conversation
        $messages = collect();
        if ($activeConversation) {
            $messages = $activeConversation->messages()->get();
        }

        // Study modes definition
        $studyModes = [
            'tanya_jawab' => [
                'name' => 'Tanya Jawab',
                'desc' => 'Bebas bertanya konsep, rumus, definisi, dan contoh.',
                'icon' => 'chat'
            ],
            'penjelasan_sederhana' => [
                'name' => 'Penjelasan Sederhana',
                'desc' => 'Konsep dijelaskan dengan analogi mudah dan bahasa santai.',
                'icon' => 'lightbulb'
            ],
            'ringkasan' => [
                'name' => 'Ringkasan Materi',
                'desc' => 'Poin penting, inti pembahasan, istilah, dan kesimpulan.',
                'icon' => 'document'
            ],
            'latihan_soal' => [
                'name' => 'Latihan Soal Interaktif',
                'desc' => 'Kuis soal satu per satu dengan evaluasi dan umpan balik.',
                'icon' => 'quiz'
            ],
            'persiapan_ujian' => [
                'name' => 'Persiapan Ujian',
                'desc' => 'Bedah kisi-kisi, latihan topik sulit, dan tips belajar.',
                'icon' => 'academic'
            ]
        ];

        // Difficulties
        $difficulties = [
            'dasar' => 'Dasar (Pemula)',
            'menengah' => 'Menengah (Standar)',
            'lanjutan' => 'Lanjutan (Tantangan)'
        ];

        // Suggested prompts
        $suggestedPrompts = [
            'Jelaskan konsep dasar materi ini dengan analogi sederhana yang mudah dimengerti.',
            'Buatkan ringkasan poin-poin paling penting beserta kesimpulannya.',
            'Uji pemahaman saya dengan 1 pertanyaan latihan interaktif.',
            'Apa kesalahan umum yang sering dialami siswa saat mempelajari topik ini?',
            'Berikan contoh penerapan konsep ini dalam kehidupan sehari-hari atau industri.'
        ];

        // Recent evaluations for student mastery widget
        $evaluations = AiEvaluation::where('user_id', $user->id)
            ->with('material')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('ai-tutor.index', compact(
            'courses',
            'conversations',
            'activeConversation',
            'messages',
            'studyModes',
            'difficulties',
            'suggestedPrompts',
            'preselectedMaterialId',
            'preselectedCourseId',
            'evaluations'
        ));
    }

    /**
     * Send a message to AI Tutor and receive response.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:3500',
            'conversation_id' => 'nullable|integer',
            'material_id' => 'nullable|integer',
            'course_id' => 'nullable|integer',
            'study_mode' => 'nullable|string|in:tanya_jawab,penjelasan_sederhana,ringkasan,latihan_soal,persiapan_ujian',
            'difficulty' => 'nullable|string|in:dasar,menengah,lanjutan',
        ]);

        $user = Auth::user();
        $userMessageText = trim($request->message);

        if (empty($userMessageText)) {
            return response()->json([
                'success' => false,
                'error' => 'Pesan tidak boleh kosong.'
            ], 422);
        }

        // Find or create conversation
        $conversation = null;
        if ($request->filled('conversation_id')) {
            $conversation = AiConversation::where('id', $request->conversation_id)
                ->where('user_id', $user->id)
                ->first();
        }

        if (!$conversation) {
            $conversation = AiConversation::create([
                'user_id' => $user->id,
                'course_id' => $request->course_id ?: null,
                'material_id' => $request->material_id ?: null,
                'title' => Str::limit($userMessageText, 35, '...'),
                'study_mode' => $request->study_mode ?: 'tanya_jawab',
                'difficulty' => $request->difficulty ?: 'menengah',
            ]);
        } else {
            // Update mode/material if updated by user
            if ($request->filled('study_mode')) {
                $conversation->study_mode = $request->study_mode;
            }
            if ($request->filled('difficulty')) {
                $conversation->difficulty = $request->difficulty;
            }
            if ($request->filled('material_id')) {
                $conversation->material_id = $request->material_id;
            }
            $conversation->save();
        }

        // Store user message
        $userMessage = AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userMessageText,
        ]);

        // Load material context if attached
        $material = null;
        if ($conversation->material_id) {
            $material = Material::with('course')->find($conversation->material_id);
        }

        // Build System Instruction
        $systemInstruction = $this->buildSystemInstruction(
            $user->name,
            $conversation->study_mode,
            $conversation->difficulty,
            $material
        );

        // Load latest messages for context (in chronological order)
        $pastMessages = AiMessage::where('conversation_id', $conversation->id)
            ->latest('id')
            ->take(12)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($msg) {
                return [
                    'role' => $msg->role,
                    'content' => $msg->content,
                ];
            })
            ->toArray();

        // Call Gemini Service
        $response = $this->geminiService->chat($systemInstruction, $pastMessages, 0.7);

        if (!$response['success']) {
            return response()->json([
                'success' => false,
                'error' => $response['error'] ?: 'Maaf, terjadi kendala saat menghubungi AI Tutor. Silakan coba lagi.',
                'conversation_id' => $conversation->id,
            ], 500);
        }

        // Prepare source citations if material is present
        $sources = null;
        if ($material) {
            $sources = [
                [
                    'title' => $material->title,
                    'course' => $material->course->title ?? 'Mata Pelajaran',
                    'topic' => $material->topic ?? 'Umum',
                    'type' => $material->type === 'video' ? 'Video Pembelajaran' : ($material->type === 'page' ? 'Modul Teks' : 'Dokumen Materi'),
                    'file' => $material->file_path ? basename($material->file_path) : null,
                ]
            ];
        }

        // Save AI message
        $aiMessage = AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $response['content'],
            'sources' => $sources,
        ]);

        // Touch conversation timestamp
        $conversation->touch();

        // Auto-detect if this was a quiz check/evaluation
        if ($conversation->study_mode === 'latihan_soal') {
            $this->checkAndRecordEvaluation($user->id, $conversation, $material, $userMessageText, $response['content']);
        }

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'conversation_title' => $conversation->title,
            'user_message' => [
                'id' => $userMessage->id,
                'role' => 'user',
                'content' => $userMessage->content,
                'created_at' => $userMessage->created_at->format('H:i'),
            ],
            'ai_message' => [
                'id' => $aiMessage->id,
                'role' => 'assistant',
                'content' => $aiMessage->content,
                'sources' => $aiMessage->sources,
                'created_at' => $aiMessage->created_at->format('H:i'),
            ],
        ]);
    }

    /**
     * Start a new conversation.
     */
    public function newConversation(Request $request)
    {
        $user = Auth::user();

        $conversation = AiConversation::create([
            'user_id' => $user->id,
            'course_id' => $request->query('course_id') ?: null,
            'material_id' => $request->query('material_id') ?: null,
            'title' => 'Percakapan Baru',
            'study_mode' => $request->query('study_mode', 'tanya_jawab'),
            'difficulty' => $request->query('difficulty', 'menengah'),
        ]);

        return redirect()->route('ai-tutor.index', ['conversation_id' => $conversation->id]);
    }

    /**
     * Rename conversation title.
     */
    public function renameConversation(Request $request, AiConversation $conversation): JsonResponse
    {
        if ($conversation->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'error' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:100',
        ]);

        $conversation->update(['title' => $request->title]);

        return response()->json([
            'success' => true,
            'title' => $conversation->title,
        ]);
    }

    /**
     * Delete a conversation.
     */
    public function deleteConversation(AiConversation $conversation)
    {
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        $conversation->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('ai-tutor.index')->with('success', 'Percakapan berhasil dihapus.');
    }

    /**
     * Build rich system instruction for AI Tutor.
     */
    protected function buildSystemInstruction(string $studentName, string $studyMode, string $difficulty, ?Material $material): string
    {
        $prompt = "Kamu adalah Eduva AI Tutor, asisten belajar interaktif, sabar, ramah, dan cerdas untuk siswa di platform Eduva LMS.\n";
        $prompt .= "Nama siswa yang sedang kamu bantu adalah: {$studentName}.\n";
        $prompt .= "Gunakan Bahasa Indonesia yang alami, runtut, menyemangati, dan mudah dipahami siswa.\n\n";

        // Guidelines on explanation
        $prompt .= "=== PEDOMAN MENGAJAR ===\n";
        $prompt .= "1. Jelaskan konsep secara terstruktur dari dasar ke lanjutan.\n";
        $prompt .= "2. Sertakan contoh konkret di dunia nyata dan analogi sederhana untuk konsep yang abstrak atau rumit.\n";
        $prompt .= "3. Format jawaban dengan Markdown yang rapi: gunakan **teks tebal** untuk kata kunci/istilah, daftar bernomor/bullet points untuk langkah, tabel sederhana jika membandingkan sesuatu, dan blok kode dengan tag bahasa jika membahas pemrograman/rumus.\n";
        $prompt .= "4. Bersikaplah suportif dan ajak siswa berpikir aktif, jangan hanya memberikan jawaban instan satu arah.\n\n";

        // Mode specific guidelines
        $prompt .= "=== MODE BELAJAR AKTIF: " . strtoupper(str_replace('_', ' ', $studyMode)) . " ===\n";
        switch ($studyMode) {
            case 'penjelasan_sederhana':
                $prompt .= "- Fokus pada penjelasan super sederhana seperti menjelaskan kepada pemula.\n";
                $prompt .= "- Hindari jargon teknis yang membingungkan. Jika ada istilah teknis, jelaskan artinya dengan analogi sehari-hari.\n";
                $prompt .= "- Buat langkah-langkah yang sangat mudah diikuti.\n";
                break;

            case 'ringkasan':
                $prompt .= "- Buat ringkasan yang komprehensif dan padat dengan format:\n";
                $prompt .= "  * **Inti Pembahasan**\n";
                $prompt .= "  * **Poin-Poin Penting**\n";
                $prompt .= "  * **Istilah & Definisi Kunci**\n";
                $prompt .= "  * **Kesimpulan & Hal yang Perlu Diingat**\n";
                break;

            case 'latihan_soal':
                $prompt .= "- Tingkat kesulitan soal: " . strtoupper($difficulty) . ".\n";
                $prompt .= "- ATURAN PENTING: Berikan pertanyaan latihan SATU PER SATU secara interaktif. JANGAN LANGSUNG MEMBERIKAN KUNCI JAWABAN atau seluruh soal sekaligus.\n";
                $prompt .= "- Tunggu siswa menjawab pertanyaan tersebut terlebih dahulu.\n";
                $prompt .= "- Ketika siswa memberikan jawaban:\n";
                $prompt .= "  1. Berikan penilaian apakah jawaban BENAR atau SALAH secara eksplisit.\n";
                $prompt .= "  2. Jelaskan alasannya dengan ramah dan edukatif.\n";
                $prompt .= "  3. Berikan skor (contoh: 100/100 jika benar, atau 0-50 jika ada kekurangan).\n";
                $prompt .= "  4. Kemudian berikan pertanyaan berikutnya untuk mengecek pemahaman lebih lanjut.\n";
                break;

            case 'persiapan_ujian':
                $prompt .= "- Fokus pada persiapan ujian: ulas konsep-konsep yang paling sering keluar dalam ujian.\n";
                $prompt .= "- Berikan tips cara menyelesaikan soal ujian dengan cepat dan tepat.\n";
                $prompt .= "- Tunjukkan jebakan soal (common mistakes) yang sering terjadi.\n";
                break;

            case 'tanya_jawab':
            default:
                $prompt .= "- Jawab pertanyaan siswa dengan lengkap, ramah, dan akurat.\n";
                $prompt .= "- Berikan penjelasan konseptual beserta contoh.\n";
                $prompt .= "- Tanyakan di akhir apakah siswa sudah paham atau ingin contoh tambahan.\n";
                break;
        }

        // Material context
        $prompt .= "\n=== KONTEKS MATERI PEMBELAJARAN ===\n";
        if ($material) {
            $prompt .= "Siswa sedang mempelajari materi berikut:\n";
            $prompt .= "- Judul Materi: " . $material->title . "\n";
            $prompt .= "- Mata Pelajaran / Kelas: " . ($material->course->title ?? '-') . "\n";
            $prompt .= "- Topik / Pertemuan: " . ($material->topic ?? '-') . "\n";
            if (!empty($material->content)) {
                $prompt .= "- Isi Materi Pembelajaran:\n\"\"\"\n" . Str::limit($material->content, 4000) . "\n\"\"\"\n";
            }
            if (!empty($material->file_path)) {
                $prompt .= "- File Dokumen Terlampir: " . basename($material->file_path) . "\n";
            }
            if (!empty($material->video_url)) {
                $prompt .= "- Video Terlampir: " . $material->video_url . "\n";
            }

            $prompt .= "\nATURAN PENGGUNAAN MATERI:\n";
            $prompt .= "1. Jadikan materi di atas sebagai sumber referensi utama jawabanmu.\n";
            $prompt .= "2. Sebutkan nama materi ('" . $material->title . "') sebagai rujukan jika relevan.\n";
            $prompt .= "3. JANGAN PERNAH MENGARANG nomor halaman atau dokumen fiktif yang tidak ada.\n";
            $prompt .= "4. Jika pertanyaan siswa tidak tercakup di dalam isi materi di atas, sampaikan secara jujur kepada siswa bahwa hal tersebut tidak dibahas di materi '" . $material->title . "'. Kamu boleh membantu menjawab menggunakan pengetahuan umum, tetapi WAJIB menegaskan bahwa itu adalah informasi wawasan tambahan di luar modul materi.\n";
        } else {
            $prompt .= "Saat ini siswa belum memilih materi spesifik. Kamu dapat menjawab pertanyaan pembelajaran secara umum. Jika siswa menanyakan hal yang membutuhkan modul khusus, sarankan siswa untuk memilih materi pembelajaran dari menu di atas.\n";
        }

        return $prompt;
    }

    /**
     * Helper to detect and record evaluation score if in quiz mode.
     */
    protected function checkAndRecordEvaluation(int $userId, AiConversation $conversation, ?Material $material, string $studentAnswer, string $aiReply): void
    {
        // Simple heuristic: check if AI explicitly marks Benar / Tepat / Skor
        $isCorrect = false;
        $score = 0;

        $lowerReply = strtolower($aiReply);
        if (str_contains($lowerReply, 'jawabanmu benar') || str_contains($lowerReply, 'benar!') || str_contains($lowerReply, 'tepat sekali') || str_contains($lowerReply, '100/100')) {
            $isCorrect = true;
            $score = 100;
        } elseif (str_contains($lowerReply, 'hampir benar') || str_contains($lowerReply, 'kurang tepat')) {
            $isCorrect = false;
            $score = 50;
        } elseif (str_contains($lowerReply, 'salah') || str_contains($lowerReply, 'belum tepat')) {
            $isCorrect = false;
            $score = 0;
        } else {
            // Not an evaluation response, skip recording
            return;
        }

        AiEvaluation::create([
            'user_id' => $userId,
            'conversation_id' => $conversation->id,
            'material_id' => $material ? $material->id : null,
            'question' => Str::limit($conversation->messages()->where('role', 'assistant')->latest()->skip(1)->value('content') ?? 'Pertanyaan Latihan', 255),
            'student_answer' => Str::limit($studentAnswer, 500),
            'is_correct' => $isCorrect,
            'score' => $score,
            'feedback' => Str::limit($aiReply, 500),
            'difficulty' => $conversation->difficulty,
        ]);
    }
}
