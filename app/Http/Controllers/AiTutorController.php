<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\AiEvaluation;
use App\Models\AiMessage;
use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiTutorController extends Controller
{
    protected GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Display the AI Tutor interface with strict role/enrollment context.
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // Administrator tidak menggunakan AI Tutor pembelajaran siswa
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        // Guru tidak menggunakan AI Tutor pendamping belajar siswa
        if ($user->role !== 'siswa') {
            return redirect()->route('dashboard')->with('error', 'Fitur AI Tutor dikhususkan untuk pendamping belajar siswa.');
        }

        // 1. Ambil hanya daftar kelas yang diikuti siswa
        $courses = $this->getAuthorizedCourses($user);
        $courseOptions = $courses->map(fn (Course $course) => [
            'id' => $course->id,
            'title' => $course->title,
            'description' => Str::limit((string) $course->description, 90),
        ])->values();

        // 2. Muat riwayat percakapan milik pengguna
        $conversations = AiConversation::where('user_id', $user->id)
            ->with([
                'material:id,course_id,topic,title,type',
                'course:id,title',
            ])
            ->orderBy('updated_at', 'desc')
            ->get();

        // 3. Resolusi konteks aktif (dari halaman pertemuan, conversation_id, atau default kelas tunggal)
        $requestedCourseId = $request->query('course_id');
        $requestedMeetingId = $request->query('meeting_id') ?? $request->query('material_id');
        $requestedTopic = $request->query('topic');
        $hasDirectContextParam = $request->filled('course_id')
            || $request->filled('meeting_id')
            || $request->filled('material_id')
            || $request->filled('topic');

        $activeConversation = null;
        $initialCourseId = null;
        $initialMeetingId = null;
        $initialMeetings = collect();
        $isAutoContext = false;
        $contextError = null;

        if ($request->filled('conversation_id')) {
            $activeConversation = $conversations->firstWhere('id', (int) $request->query('conversation_id'));

            if (!$activeConversation) {
                $contextError = 'Percakapan yang diminta tidak ditemukan atau bukan milik Anda.';
            } elseif ($activeConversation->course_id) {
                $authorizedCourse = $this->authorizeCourseAccess($user, (int) $activeConversation->course_id);
                if ($authorizedCourse) {
                    $initialCourseId = $authorizedCourse->id;
                    $initialMeetings = $this->getFormattedMeetings($authorizedCourse, $user);

                    if ($activeConversation->material_id && $initialMeetings->contains('id', (int) $activeConversation->material_id)) {
                        $initialMeetingId = (int) $activeConversation->material_id;
                    }
                }
            }
        } elseif ($hasDirectContextParam) {
            // Kondisi A: Dibuka langsung dari halaman pertemuan / aktivitas kelas
            if ($requestedMeetingId) {
                $targetMaterial = Material::find((int) $requestedMeetingId);

                if (!$targetMaterial) {
                    $contextError = 'Pertemuan atau materi yang dipilih tidak ditemukan.';
                } else {
                    $authorizedCourse = $this->authorizeCourseAccess($user, (int) $targetMaterial->course_id);
                    $courseMismatch = $requestedCourseId && (int) $targetMaterial->course_id !== (int) $requestedCourseId;
                    $hiddenFromStudent = $user->role === 'siswa' && !$targetMaterial->is_visible;

                    if (!$authorizedCourse || $courseMismatch || $hiddenFromStudent) {
                        $contextError = 'Anda tidak memiliki akses ke kelas atau pertemuan yang diminta.';
                    } else {
                        $initialCourseId = $authorizedCourse->id;
                        $initialMeetingId = $targetMaterial->id;
                        $initialMeetings = $this->getFormattedMeetings($authorizedCourse, $user);
                        $isAutoContext = true;
                    }
                }
            } elseif ($requestedCourseId) {
                $authorizedCourse = $this->authorizeCourseAccess($user, (int) $requestedCourseId);

                if (!$authorizedCourse) {
                    $contextError = 'Akses ditolak. Anda tidak terdaftar pada kelas tersebut.';
                } else {
                    $initialCourseId = $authorizedCourse->id;
                    $initialMeetings = $this->getFormattedMeetings($authorizedCourse, $user);

                    if ($requestedTopic) {
                        $matchedMeeting = $initialMeetings->firstWhere('topic', $requestedTopic);
                        if ($matchedMeeting) {
                            $initialMeetingId = $matchedMeeting['id'];
                            $isAutoContext = true;
                        }
                    }
                }
            }
        } else {
            // Kondisi B: Dibuka dari menu utama AI Tutor
            if ($conversations->isNotEmpty() && !$request->has('new')) {
                $activeConversation = $conversations->first();

                if ($activeConversation->course_id) {
                    $authorizedCourse = $this->authorizeCourseAccess($user, (int) $activeConversation->course_id);
                    if ($authorizedCourse) {
                        $initialCourseId = $authorizedCourse->id;
                        $initialMeetings = $this->getFormattedMeetings($authorizedCourse, $user);

                        if ($activeConversation->material_id && $initialMeetings->contains('id', (int) $activeConversation->material_id)) {
                            $initialMeetingId = (int) $activeConversation->material_id;
                        }
                    }
                }
            } elseif ($courses->count() === 1) {
                // Jika siswa hanya mengikuti 1 kelas, otomatis pilih kelas tersebut
                $singleCourse = $courses->first();
                $initialCourseId = $singleCourse->id;
                $initialMeetings = $this->getFormattedMeetings($singleCourse, $user);
            }
        }

        $messages = $activeConversation ? $activeConversation->messages()->get() : collect();

        $studyModes = [
            'tanya_jawab' => [
                'name' => 'Tanya Jawab',
                'desc' => 'Bebas bertanya konsep, rumus, definisi, dan contoh.',
                'icon' => 'chat',
            ],
            'penjelasan_sederhana' => [
                'name' => 'Penjelasan Sederhana',
                'desc' => 'Konsep dijelaskan dengan analogi mudah dan bahasa santai.',
                'icon' => 'lightbulb',
            ],
            'ringkasan' => [
                'name' => 'Ringkasan Materi',
                'desc' => 'Poin penting, inti pembahasan, istilah, dan kesimpulan.',
                'icon' => 'document',
            ],
            'latihan_soal' => [
                'name' => 'Latihan Soal Interaktif',
                'desc' => 'Kuis soal satu per satu dengan evaluasi dan umpan balik.',
                'icon' => 'quiz',
            ],
            'persiapan_ujian' => [
                'name' => 'Persiapan Ujian',
                'desc' => 'Bedah kisi-kisi, latihan topik sulit, dan tips belajar.',
                'icon' => 'academic',
            ],
        ];

        $difficulties = [
            'dasar' => 'Dasar (Pemula)',
            'menengah' => 'Menengah (Standar)',
            'lanjutan' => 'Lanjutan (Tantangan)',
        ];

        $evaluations = AiEvaluation::where('user_id', $user->id)
            ->with('material')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('ai-tutor.index', compact(
            'courses',
            'courseOptions',
            'conversations',
            'activeConversation',
            'messages',
            'studyModes',
            'difficulties',
            'initialCourseId',
            'initialMeetingId',
            'initialMeetings',
            'isAutoContext',
            'contextError',
            'evaluations'
        ));
    }

    /**
     * Dynamically fetch meetings/materials belonging to a specific authorized course.
     */
    public function getCourseMeetings(int $courseId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->role !== 'siswa') {
            return response()->json([
                'success' => false,
                'error' => 'Akses ditolak. Fitur AI Tutor hanya dapat digunakan oleh siswa.',
            ], 403);
        }

        $course = Course::find($courseId);
        if (!$course) {
            return response()->json([
                'success' => false,
                'error' => 'Kelas tidak ditemukan.',
            ], 404);
        }

        $authorizedCourse = $this->authorizeCourseAccess($user, $course->id);
        if (!$authorizedCourse) {
            return response()->json([
                'success' => false,
                'error' => 'Akses ditolak. Anda tidak terdaftar pada kelas ini.',
            ], 403);
        }

        $meetings = $this->getFormattedMeetings($authorizedCourse, $user);

        return response()->json([
            'success' => true,
            'course' => [
                'id' => $authorizedCourse->id,
                'title' => $authorizedCourse->title,
            ],
            'meetings' => $meetings->values(),
        ]);
    }

    /**
     * Send a message to AI Tutor and receive response scoped strictly to validated course & meeting context.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->role !== 'siswa') {
            return response()->json([
                'success' => false,
                'error' => 'Akses ditolak. Fitur AI Tutor hanya dapat digunakan oleh siswa.',
            ], 403);
        }

        $request->validate([
            'message' => 'required|string|max:3500',
            'conversation_id' => 'nullable|integer',
            'course_id' => 'nullable|integer',
            'meeting_id' => 'nullable|integer',
            'material_id' => 'nullable|integer',
            'study_mode' => 'nullable|string|in:tanya_jawab,penjelasan_sederhana,ringkasan,latihan_soal,persiapan_ujian',
            'difficulty' => 'nullable|string|in:dasar,menengah,lanjutan',
        ]);

        $userMessageText = trim($request->message);
        if ($userMessageText === '') {
            return response()->json([
                'success' => false,
                'error' => 'Pesan tidak boleh kosong.',
            ], 422);
        }

        // Validasi kepemilikan percakapan jika conversation_id dikirim
        $conversation = null;
        if ($request->filled('conversation_id')) {
            $conversation = AiConversation::where('id', (int) $request->input('conversation_id'))
                ->where('user_id', $user->id)
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'error' => 'Percakapan tidak ditemukan atau bukan milik Anda.',
                ], 403);
            }
        }

        $hasCourseParam = $request->has('course_id');
        $hasMeetingParam = $request->has('meeting_id') || $request->has('material_id');

        $requestedCourseId = $hasCourseParam
            ? ($request->filled('course_id') ? (int) $request->input('course_id') : null)
            : ($conversation?->course_id ? (int) $conversation->course_id : null);

        $rawMeetingInput = $request->has('meeting_id')
            ? $request->input('meeting_id')
            : $request->input('material_id');

        $requestedMeetingId = $hasMeetingParam
            ? (!empty($rawMeetingInput) ? (int) $rawMeetingInput : null)
            : ($conversation?->material_id ? (int) $conversation->material_id : null);

        // Validasi kelas di backend
        $activeCourse = null;
        if ($requestedCourseId !== null) {
            $activeCourse = $this->authorizeCourseAccess($user, $requestedCourseId);
            if (!$activeCourse) {
                return response()->json([
                    'success' => false,
                    'error' => 'Akses ditolak. Anda tidak terdaftar pada kelas yang dipilih.',
                ], 403);
            }
        }

        // Validasi pertemuan / materi dan kecocokan course_id di backend
        $activeMaterial = null;
        if ($requestedMeetingId !== null) {
            $activeMaterial = Material::with('course')->find($requestedMeetingId);
            if (!$activeMaterial) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pertemuan atau materi tidak ditemukan.',
                ], 422);
            }

            $materialCourse = $this->authorizeCourseAccess($user, (int) $activeMaterial->course_id);
            if (!$materialCourse) {
                return response()->json([
                    'success' => false,
                    'error' => 'Akses ditolak. Anda tidak dapat mengakses materi dari kelas lain.',
                ], 403);
            }

            if ($user->role === 'siswa' && !$activeMaterial->is_visible) {
                return response()->json([
                    'success' => false,
                    'error' => 'Materi pertemuan ini belum dipublikasikan oleh guru.',
                ], 403);
            }

            if ($activeCourse && (int) $activeMaterial->course_id !== (int) $activeCourse->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pertemuan yang dipilih tidak termasuk dalam kelas yang aktif.',
                ], 422);
            }

            $activeCourse = $activeCourse ?: $materialCourse;
        }

        $resolvedCourseId = $activeCourse?->id;
        $resolvedMeetingId = $activeMaterial?->id;

        if (!$conversation) {
            $conversation = AiConversation::create([
                'user_id' => $user->id,
                'course_id' => $resolvedCourseId,
                'material_id' => $resolvedMeetingId,
                'title' => Str::limit($userMessageText, 35, '...'),
                'study_mode' => $request->input('study_mode') ?: 'tanya_jawab',
                'difficulty' => $request->input('difficulty') ?: 'menengah',
            ]);
        } else {
            if ($request->filled('study_mode')) {
                $conversation->study_mode = $request->input('study_mode');
            }
            if ($request->filled('difficulty')) {
                $conversation->difficulty = $request->input('difficulty');
            }
            if ($hasCourseParam || $hasMeetingParam) {
                $conversation->course_id = $resolvedCourseId;
                $conversation->material_id = $resolvedMeetingId;
            }
            $conversation->save();
        }

        $userMessage = AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userMessageText,
        ]);

        // Ambil materi pendukung HANYA dari pertemuan/topik aktif pada kelas aktif (tidak pernah lintas kelas)
        $siblingMaterials = collect();
        if ($activeCourse && $activeMaterial && !empty($activeMaterial->topic)) {
            $siblingQuery = Material::where('course_id', $activeCourse->id)
                ->where('topic', $activeMaterial->topic)
                ->where('id', '!=', $activeMaterial->id)
                ->orderBy('order')
                ->orderBy('id')
                ->limit(4);

            if ($user->role === 'siswa') {
                $siblingQuery->where('is_visible', true);
            }

            $siblingMaterials = $siblingQuery->get();
        }

        $systemInstruction = $this->buildSystemInstruction(
            $user->name,
            $conversation->study_mode,
            $conversation->difficulty,
            $activeCourse,
            $activeMaterial,
            $siblingMaterials
        );

        $pastMessages = AiMessage::where('conversation_id', $conversation->id)
            ->latest('id')
            ->take(12)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->toArray();

        $response = $this->geminiService->chat($systemInstruction, $pastMessages, 0.7);

        if (!$response['success']) {
            return response()->json([
                'success' => false,
                'error' => $response['error'] ?: 'Maaf, terjadi kendala saat menghubungi AI Tutor. Silakan coba lagi.',
                'conversation_id' => $conversation->id,
                'course_id' => $resolvedCourseId,
                'meeting_id' => $resolvedMeetingId,
            ], 500);
        }

        $sources = null;
        if ($activeMaterial) {
            $sources = [
                [
                    'title' => $activeMaterial->title,
                    'course' => $activeCourse?->title ?? 'Mata Pelajaran',
                    'topic' => $activeMaterial->topic ?? 'Umum',
                    'type' => $this->resolveTypeLabel($activeMaterial->type),
                    'file' => $activeMaterial->file_path ? basename($activeMaterial->file_path) : null,
                ],
            ];
        }

        $aiMessage = AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $response['content'],
            'sources' => $sources,
        ]);

        $conversation->touch();

        if ($conversation->study_mode === 'latihan_soal') {
            $this->checkAndRecordEvaluation($user->id, $conversation, $activeMaterial, $userMessageText, $response['content']);
        }

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'conversation_title' => $conversation->title,
            'course_id' => $resolvedCourseId,
            'meeting_id' => $resolvedMeetingId,
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
     * Start a new conversation view cleanly with optional validated context.
     */
    public function newConversation(Request $request)
    {
        $params = ['new' => 1];

        if ($request->filled('course_id')) {
            $params['course_id'] = (int) $request->query('course_id');
        }
        if ($request->filled('meeting_id')) {
            $params['meeting_id'] = (int) $request->query('meeting_id');
        } elseif ($request->filled('material_id')) {
            $params['meeting_id'] = (int) $request->query('material_id');
        }

        return redirect()->route('ai-tutor.index', $params);
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
     * Return only courses the current user is enrolled in (siswa only).
     */
    protected function getAuthorizedCourses(User $user): Collection
    {
        if ($user->role !== 'siswa') {
            return collect();
        }

        return $user->enrolledCourses()
            ->orderBy('courses.title')
            ->get(['courses.id', 'courses.title', 'courses.description', 'courses.teacher_id']);
    }

    /**
     * Verify whether the user has access to a specific course ID.
     */
    protected function authorizeCourseAccess(User $user, int $courseId): ?Course
    {
        if ($user->role !== 'siswa') {
            return null;
        }

        return Course::where('id', $courseId)
            ->whereHas('students', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->first();
    }

    /**
     * Load formatted meetings/materials strictly for a single course.
     */
    protected function getFormattedMeetings(Course $course, User $user): Collection
    {
        $query = Material::where('course_id', $course->id)
            ->orderBy('topic')
            ->orderBy('order')
            ->orderBy('id');

        if ($user->role === 'siswa') {
            $query->where('is_visible', true);
        }

        return $query->get([
            'id',
            'course_id',
            'topic',
            'type',
            'title',
            'content',
            'file_path',
            'video_url',
            'order',
            'is_visible',
        ])->values()->map(fn (Material $material, int $index) => $this->formatMeetingOption($material, $index));
    }

    /**
     * Format a material record into a readable meeting dropdown option.
     */
    protected function formatMeetingOption(Material $material, int $index): array
    {
        $meetingNumber = $index + 1;
        $topic = trim((string) ($material->topic ?? ''));

        if ($topic !== '' && preg_match('/^(pertemuan|sesi|bab|modul|\d+\.)/i', $topic)) {
            $meetingLabel = $topic;
        } elseif ($topic !== '' && strtolower($topic) !== 'umum') {
            $meetingLabel = "Pertemuan {$meetingNumber} ({$topic})";
        } else {
            $meetingLabel = "Pertemuan {$meetingNumber}";
        }

        $hasInlineText = trim((string) $material->content) !== '';
        $hasAttachment = !empty($material->file_path) || !empty($material->video_url);

        return [
            'id' => $material->id,
            'course_id' => $material->course_id,
            'meeting_number' => $meetingNumber,
            'topic' => $topic ?: 'Umum',
            'meeting_label' => $meetingLabel,
            'title' => $material->title,
            'type' => $material->type,
            'type_label' => $this->resolveTypeLabel($material->type),
            'display_label' => "{$meetingLabel} — {$material->title}",
            'has_content' => $hasInlineText || $hasAttachment,
            'has_text_content' => $hasInlineText,
        ];
    }

    /**
     * Human-readable label for material type.
     */
    protected function resolveTypeLabel(?string $type): string
    {
        return match ($type) {
            'video' => 'Video Pembelajaran',
            'page' => 'Halaman Materi',
            'label' => 'Pengantar',
            default => 'Berkas Materi',
        };
    }

    /**
     * Safely extract readable text content from a material record or supported local file.
     */
    protected function extractReadableContent(Material $material): string
    {
        $content = trim((string) $material->content);

        if ($material->file_path && Storage::disk('public')->exists($material->file_path)) {
            $ext = strtolower(pathinfo($material->file_path, PATHINFO_EXTENSION));

            if ($ext === 'txt') {
                $fileText = trim((string) Storage::disk('public')->get($material->file_path));
                if ($fileText !== '') {
                    $content = $content !== '' ? ($content . "\n\n" . $fileText) : $fileText;
                }
            } elseif ($ext === 'docx' && class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
                try {
                    $fullPath = Storage::disk('public')->path($material->file_path);
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load($fullPath);
                    $extracted = [];

                    foreach ($phpWord->getSections() as $section) {
                        foreach ($section->getElements() as $element) {
                            if (method_exists($element, 'getText')) {
                                $extracted[] = $element->getText();
                            }
                        }
                    }

                    $docxText = trim(implode("\n", array_filter($extracted)));
                    if ($docxText !== '') {
                        $content = $content !== '' ? ($content . "\n\n" . $docxText) : $docxText;
                    }
                } catch (\Throwable $e) {
                    // Abaikan jika file docx rusak/tidak terbaca, fallback ke content teks yang tersedia
                }
            }
        }

        return Str::limit($content, 4000);
    }

    /**
     * Build rich system instruction strictly scoped to the active course and meeting.
     */
    protected function buildSystemInstruction(
        string $studentName,
        string $studyMode,
        string $difficulty,
        ?Course $course,
        ?Material $material,
        Collection $siblingMaterials
    ): string {
        $prompt = "Kamu adalah Eduva AI Tutor, asisten belajar interaktif, sabar, ramah, dan cerdas untuk siswa di platform Eduva LMS.\n";
        $prompt .= "Nama pengguna yang sedang kamu bantu adalah: {$studentName}.\n";
        $prompt .= "Gunakan Bahasa Indonesia yang alami, runtut, menyemangati, dan mudah dipahami.\n\n";

        $prompt .= "=== PEDOMAN MENGAJAR ===\n";
        $prompt .= "1. Jelaskan konsep secara terstruktur dari dasar ke lanjutan dengan bahasa yang sederhana dan mudah dipahami.\n";
        $prompt .= "2. Sertakan contoh konkret di dunia nyata dan analogi sederhana untuk konsep yang abstrak atau rumit.\n";
        $prompt .= "3. Format jawaban dengan Markdown yang rapi: gunakan **teks tebal** untuk kata kunci, daftar bernomor/bullet points untuk langkah, dan blok kode jika membahas pemrograman/rumus.\n";
        $prompt .= "4. Bersikaplah suportif dan ajak siswa berpikir aktif.\n";
        $prompt .= "5. ATURAN GAYA BAHASA: DILARANG KERAS menggunakan emoji atau emotikon apa pun dalam teks jawaban.\n\n";

        $prompt .= "=== MODE BELAJAR AKTIF: " . strtoupper(str_replace('_', ' ', $studyMode)) . " ===\n";
        switch ($studyMode) {
            case 'penjelasan_sederhana':
                $prompt .= "- Fokus pada penjelasan super sederhana seperti menjelaskan kepada pemula.\n";
                $prompt .= "- Hindari jargon teknis yang membingungkan; jika ada istilah penting, jelaskan artinya dengan analogi sehari-hari.\n";
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
                $prompt .= "- ATURAN PENTING: Berikan pertanyaan latihan SATU PER SATU secara interaktif. JANGAN langsung memberikan seluruh soal sekaligus.\n";
                $prompt .= "- Ketika siswa menjawab: nyatakan apakah jawaban BENAR atau SALAH, jelaskan alasannya, berikan skor (contoh: 100/100), lalu beri pertanyaan berikutnya.\n";
                break;

            case 'persiapan_ujian':
                $prompt .= "- Fokus pada persiapan ujian: ulas konsep yang paling sering keluar dalam ujian, tips cepat, dan kesalahan umum siswa.\n";
                break;

            case 'tanya_jawab':
            default:
                $prompt .= "- Jawab pertanyaan siswa secara akurat, runtut, ramah, dan tanyakan di akhir apakah ingin contoh tambahan.\n";
                break;
        }

        $prompt .= "\n=== KONTEKS KELAS & PERTEMUAN AKTIF ===\n";
        if ($course) {
            $prompt .= "- Kelas / Mata Pelajaran: {$course->title} (ID: {$course->id})\n";
            if (!empty($course->description)) {
                $prompt .= "- Deskripsi Kelas: " . Str::limit($course->description, 500) . "\n";
            }
        }

        if ($material) {
            $readableText = $this->extractReadableContent($material);
            $prompt .= "- Pertemuan / Topik Aktif: " . ($material->topic ?: 'Umum') . "\n";
            $prompt .= "- Judul Materi Utama: {$material->title} (ID: {$material->id}, Jenis: " . $this->resolveTypeLabel($material->type) . ")\n";

            if ($readableText !== '') {
                $prompt .= "- Isi Materi Pembelajaran Utama:\n\"\"\"\n{$readableText}\n\"\"\"\n";
            }
            if (!empty($material->file_path)) {
                $prompt .= "- Lampiran Dokumen: " . basename($material->file_path) . "\n";
            }
            if (!empty($material->video_url)) {
                $prompt .= "- Tautan Video Pembelajaran: {$material->video_url}\n";
            }

            $siblingContexts = [];
            foreach ($siblingMaterials as $sibling) {
                $sibText = $this->extractReadableContent($sibling);
                if ($sibText !== '') {
                    $siblingContexts[] = "* {$sibling->title}: " . Str::limit($sibText, 800);
                }
            }

            if (!empty($siblingContexts)) {
                $prompt .= "\n- Materi Pendukung pada Pertemuan yang Sama:\n" . implode("\n", $siblingContexts) . "\n";
            }

            $hasAnySourceText = $readableText !== '' || !empty($siblingContexts);

            $prompt .= "\nATURAN PENGGUNAAN KONTEKS MATERI:\n";
            $prompt .= "1. Utamakan materi dari pertemuan aktif di atas sebagai sumber referensi utama jawabanmu.\n";
            $prompt .= "2. Tetap jelaskan isi materi dengan bahasa yang sederhana dan mudah dipahami siswa.\n";
            $prompt .= "3. DILARANG KERAS mengarang isi materi, nomor halaman, atau isi file lampiran yang tidak tertulis di atas.\n";

            if (!$hasAnySourceText) {
                $prompt .= "4. PENTING: Konten teks detail untuk materi '{$material->title}' saat ini BELUM TERSEDIA dalam bentuk teks di sistem (mungkin hanya berupa judul, tautan video, atau dokumen biner tanpa teks). Beri tahu siswa secara jujur bahwa isi materi detail pada pertemuan ini belum tersedia untuk dibaca AI, dan JANGAN mengarang isinya. Jika siswa bertanya topik secara umum sesuai judul materi, kamu boleh membantu dengan pengetahuan umum sambil menegaskan bahwa itu di luar teks modul yang tersedia.\n";
            } else {
                $prompt .= "4. Jika pertanyaan siswa berada di luar cakupan materi di atas, sampaikan secara jujur bahwa hal tersebut belum dibahas pada materi '{$material->title}', lalu bantu jelaskan secara singkat dengan menegaskan bahwa itu adalah tambahan wawasan umum.\n";
            }
        } elseif ($course) {
            $prompt .= "- Pertemuan / Materi: Belum ada pertemuan spesifik yang dipilih.\n";
            $prompt .= "ATURAN: Beri tahu siswa jika materi pertemuan belum dipilih atau belum tersedia di kelas '{$course->title}'. Bantu pertanyaan umum terkait mata pelajaran tersebut tanpa mengarang isi modul pertemuan tertentu.\n";
        } else {
            $prompt .= "Siswa belum memilih kelas dan pertemuan. Kamu dapat membantu pertanyaan belajar secara umum, namun sarankan siswa memilih kelas dan pertemuan di bagian atas agar jawaban lebih terarah ke materi kuliah/pelajaran.\n";
        }

        return $prompt;
    }

    /**
     * Helper to detect and record evaluation score if in quiz mode.
     */
    protected function checkAndRecordEvaluation(int $userId, AiConversation $conversation, ?Material $material, string $studentAnswer, string $aiReply): void
    {
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
