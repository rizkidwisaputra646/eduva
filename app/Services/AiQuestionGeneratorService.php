<?php

namespace App\Services;

use Illuminate\Support\Str;

class AiQuestionGeneratorService
{
    public const MIN_QUESTIONS = 1;
    public const MAX_QUESTIONS = 20;

    protected GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Generate structured questions strictly from extracted document text.
     *
     * @return array{success: bool, questions: array, error: ?string}
     */
    public function generateFromText(
        string $sourceText,
        string $type,
        int $count,
        string $difficulty,
        bool $isRegenerate = false
    ): array {
        $cleanSource = trim($sourceText);
        if (mb_strlen($cleanSource) < 20) {
            return [
                'success' => false,
                'questions' => [],
                'error' => 'Teks sumber dokumen terlalu pendek untuk dibuatkan soal.',
            ];
        }

        $count = max(self::MIN_QUESTIONS, min(self::MAX_QUESTIONS, $count));
        $systemInstruction = $this->buildSystemInstruction($type, $difficulty);
        $userPrompt = $this->buildUserPrompt($cleanSource, $type, $count, $difficulty, $isRegenerate);

        $response = $this->geminiService->generateJson(
            $systemInstruction,
            $userPrompt,
            $isRegenerate ? 0.65 : 0.4,
            8192
        );

        if (!$response['success']) {
            return [
                'success' => false,
                'questions' => [],
                'error' => $response['error'] ?: 'Gagal menghubungi layanan AI untuk membuat soal.',
            ];
        }

        $decoded = $this->decodeJsonPayload((string) $response['content']);
        if ($decoded === null) {
            return [
                'success' => false,
                'questions' => [],
                'error' => 'Format respons AI tidak valid. Silakan coba generate ulang.',
            ];
        }

        $rawQuestions = isset($decoded['questions']) && is_array($decoded['questions'])
            ? $decoded['questions']
            : (array_is_list($decoded) ? $decoded : []);

        $normalized = $this->normalizeQuestions($rawQuestions, $type, $difficulty, $count);

        if (empty($normalized)) {
            return [
                'success' => false,
                'questions' => [],
                'error' => 'AI tidak menghasilkan soal yang valid dari dokumen ini. Silakan coba lagi.',
            ];
        }

        return [
            'success' => true,
            'questions' => $normalized,
            'error' => null,
        ];
    }

    protected function buildSystemInstruction(string $type, string $difficulty): string
    {
        $typeLabel = $type === 'multiple_choice' ? 'Pilihan Ganda (4 opsi: A, B, C, D)' : 'Esai / Uraian';
        $diffGuide = match ($difficulty) {
            'mudah' => 'Mudah (fokus ingatan, definisi dasar, dan pemahaman konsep langsung dari teks).',
            'sulit' => 'Sulit (fokus analisis, studi kasus, perbandingan, sintesis, dan penalaran tingkat tinggi berbasis teks).',
            'campuran' => 'Campuran (distribusikan secara seimbang antara soal Mudah, Sedang, dan Sulit).',
            default => 'Sedang (fokus pemahaman konsep, penerapan, dan hubungan antar konsep dalam teks).',
        };

        $instruction = "Kamu adalah pakar evaluasi pendidikan profesional untuk LMS EDUVA.\n";
        $instruction .= "Tugasmu adalah menyusun soal ujian berkualitas tinggi dalam Bahasa Indonesia yang baku, jelas, dan pedagogis.\n\n";
        $instruction .= "ATURAN KETAT:\n";
        $instruction .= "1. Seluruh pertanyaan, opsi jawaban, kunci jawaban, dan pembahasan WAJIB bersumber langsung dari teks dokumen materi pembelajaran yang diberikan.\n";
        $instruction .= "2. DILARANG KERAS mengarang fakta, teori, angka, atau topik di luar isi dokumen sumber.\n";
        $instruction .= "3. Jenis soal yang diminta HANYA: {$typeLabel}. Jangan mencampur jenis soal lain.\n";
        $instruction .= "4. Tingkat kesulitan: {$diffGuide}\n";
        $instruction .= "5. Jangan gunakan emoji apa pun dan keluarkan HANYA objek JSON murni yang valid.\n\n";

        if ($type === 'multiple_choice') {
            $instruction .= "FORMAT OUTPUT JSON WAJIB:\n";
            $instruction .= "{\n";
            $instruction .= "  \"questions\": [\n";
            $instruction .= "    {\n";
            $instruction .= "      \"question_text\": \"Teks pertanyaan yang jelas tanpa nomor soal\",\n";
            $instruction .= "      \"options\": {\n";
            $instruction .= "        \"A\": \"Teks pilihan A\",\n";
            $instruction .= "        \"B\": \"Teks pilihan B\",\n";
            $instruction .= "        \"C\": \"Teks pilihan C\",\n";
            $instruction .= "        \"D\": \"Teks pilihan D\"\n";
            $instruction .= "      },\n";
            $instruction .= "      \"correct_answer\": \"A\",\n";
            $instruction .= "      \"explanation\": \"Pembahasan ringkas mengapa jawaban tersebut benar berdasarkan materi.\",\n";
            $instruction .= "      \"difficulty\": \"mudah|sedang|sulit\"\n";
            $instruction .= "    }\n";
            $instruction .= "  ]\n";
            $instruction .= "}\n";
            $instruction .= "Pastikan setiap soal memiliki tepat 4 pilihan (A, B, C, D) yang masuk akal dan hanya 1 jawaban benar pada field correct_answer (A/B/C/D).";
        } else {
            $instruction .= "FORMAT OUTPUT JSON WAJIB:\n";
            $instruction .= "{\n";
            $instruction .= "  \"questions\": [\n";
            $instruction .= "    {\n";
            $instruction .= "      \"question_text\": \"Teks pertanyaan esai yang jelas dan menuntut penjelasan, tanpa nomor soal\",\n";
            $instruction .= "      \"ideal_answer\": \"Kunci jawaban / contoh jawaban ideal yang lengkap sesuai materi dokumen.\",\n";
            $instruction .= "      \"rubric\": \"Pedoman penilaian sederhana (poin-poin penting yang harus ada pada jawaban siswa).\",\n";
            $instruction .= "      \"difficulty\": \"mudah|sedang|sulit\"\n";
            $instruction .= "    }\n";
            $instruction .= "  ]\n";
            $instruction .= "}\n";
        }

        return $instruction;
    }

    protected function buildUserPrompt(
        string $sourceText,
        string $type,
        int $count,
        string $difficulty,
        bool $isRegenerate
    ): string {
        $typeLabel = $type === 'multiple_choice' ? 'Pilihan Ganda' : 'Esai';
        $regenNote = $isRegenerate
            ? "\nCATATAN REGENERASI: Ini adalah permintaan generate ulang. Susun variasi soal yang berbeda dari sekadar mengulang kalimat yang sama, tetap mengacu sepenuhnya pada materi di bawah.\n"
            : '';

        return "Buatlah tepat {$count} buah soal {$typeLabel} dengan tingkat kesulitan '{$difficulty}' berdasarkan dokumen pembelajaran berikut.{$regenNote}\n"
            . "=== ISI DOKUMEN PEMBELAJARAN ===\n"
            . Str::limit($sourceText, DocumentTextExtractor::MAX_TEXT_LENGTH, '')
            . "\n=== AKHIR DOKUMEN ===\n"
            . "Pastikan array \"questions\" berisi tepat {$count} soal.";
    }

    protected function decodeJsonPayload(string $raw): ?array
    {
        $trimmed = trim($raw);

        // Strip markdown fences if present
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $trimmed, $m)) {
            $trimmed = trim($m[1]);
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Try isolating the outermost JSON object
        $firstBrace = strpos($trimmed, '{');
        $lastBrace = strrpos($trimmed, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $slice = substr($trimmed, $firstBrace, $lastBrace - $firstBrace + 1);
            $decoded = json_decode($slice, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected function normalizeQuestions(array $rawQuestions, string $type, string $requestedDifficulty, int $limit): array
    {
        $normalized = [];

        foreach ($rawQuestions as $item) {
            if (!is_array($item)) {
                continue;
            }

            $qText = trim((string) ($item['question_text'] ?? $item['question'] ?? ''));
            if ($qText === '') {
                continue;
            }

            // Strip leading "1. " if AI still added numbering
            $qText = preg_replace('/^\d+[\.\)]\s+/', '', $qText) ?? $qText;

            $itemDifficulty = strtolower(trim((string) ($item['difficulty'] ?? '')));
            if (!in_array($itemDifficulty, ['mudah', 'sedang', 'sulit'], true)) {
                $itemDifficulty = $requestedDifficulty === 'campuran' ? 'sedang' : $requestedDifficulty;
            }

            $index = count($normalized) + 1;

            if ($type === 'multiple_choice') {
                $rawOpts = is_array($item['options'] ?? null) ? $item['options'] : [];
                $options = [
                    'A' => trim((string) ($rawOpts['A'] ?? $rawOpts['a'] ?? $rawOpts[0] ?? '')),
                    'B' => trim((string) ($rawOpts['B'] ?? $rawOpts['b'] ?? $rawOpts[1] ?? '')),
                    'C' => trim((string) ($rawOpts['C'] ?? $rawOpts['c'] ?? $rawOpts[2] ?? '')),
                    'D' => trim((string) ($rawOpts['D'] ?? $rawOpts['d'] ?? $rawOpts[3] ?? '')),
                ];

                if ($options['A'] === '' || $options['B'] === '' || $options['C'] === '' || $options['D'] === '') {
                    continue;
                }

                $correctRaw = strtoupper(trim((string) ($item['correct_answer'] ?? $item['answer'] ?? 'A')));
                preg_match('/[A-D]/', $correctRaw, $m);
                $correctAnswer = $m[0] ?? 'A';

                $normalized[] = [
                    'id' => 'ai_' . $index,
                    'number' => $index,
                    'type' => 'multiple_choice',
                    'question_text' => $qText,
                    'options' => $options,
                    'correct_answer' => $correctAnswer,
                    'explanation' => trim((string) ($item['explanation'] ?? '')),
                    'difficulty' => $itemDifficulty,
                    'points' => 10,
                ];
            } else {
                $idealAnswer = trim((string) ($item['ideal_answer'] ?? $item['answer_key'] ?? $item['explanation'] ?? ''));
                $rubric = trim((string) ($item['rubric'] ?? ''));

                $combinedExplanation = trim(implode("\n\n", array_filter([
                    $idealAnswer !== '' ? "Kunci Jawaban Ideal:\n{$idealAnswer}" : null,
                    $rubric !== '' ? "Pedoman Penilaian:\n{$rubric}" : null,
                ])));

                $normalized[] = [
                    'id' => 'ai_' . $index,
                    'number' => $index,
                    'type' => 'essay',
                    'question_text' => $qText,
                    'ideal_answer' => $idealAnswer,
                    'rubric' => $rubric,
                    'explanation' => $combinedExplanation,
                    'difficulty' => $itemDifficulty,
                    'points' => 20,
                ];
            }

            if (count($normalized) >= $limit) {
                break;
            }
        }

        return $normalized;
    }
}

