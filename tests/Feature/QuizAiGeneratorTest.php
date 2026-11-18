<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use App\Services\DocumentTextExtractor;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;
use ZipArchive;

class QuizAiGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $otherTeacher;
    private User $student;
    private Course $course;
    private Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'role' => 'guru',
            'identity_number' => '198501012010011001',
            'is_active' => true,
        ]);

        $this->otherTeacher = User::factory()->create([
            'role' => 'guru',
            'identity_number' => '198501012010011099',
            'is_active' => true,
        ]);

        $this->student = User::factory()->create([
            'role' => 'siswa',
            'identity_number' => '202401001',
            'is_active' => true,
        ]);

        $this->course = Course::create([
            'title' => 'Pemrograman Web Lanjut',
            'description' => 'Laravel & Arsitektur MVC',
            'teacher_id' => $this->teacher->id,
            'invitation_code' => 'PWL123',
            'is_active' => true,
        ]);
        $this->course->students()->attach($this->student->id);

        $this->quiz = Quiz::create([
            'course_id' => $this->course->id,
            'topic' => '01. Pengantar MVC',
            'title' => 'Ujian Bab 1',
            'description' => 'Evaluasi pemahaman konsep dasar',
            'type' => 'quiz',
            'is_visible' => true,
        ]);
    }

    public function test_quiz_show_displays_both_manual_and_ai_generator_methods(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('quizzes.show', $this->quiz->id));

        $response->assertStatus(200);
        $response->assertSee('Buat Soal Manual');
        $response->assertSee('+ Pilihan Ganda');
        $response->assertSee('+ Esai');
        $response->assertSee('Import Word (.docx)');
        $response->assertSee('AI Question Generator');
        $response->assertSee(route('quizzes.ai-generator.show', $this->quiz->id));
    }

    public function test_authorization_blocks_students_and_other_teachers(): void
    {
        $this->actingAs($this->student)
            ->get(route('quizzes.ai-generator.show', $this->quiz->id))
            ->assertStatus(403);

        $this->actingAs($this->student)
            ->post(route('quizzes.ai-generator.generate', $this->quiz->id), [])
            ->assertStatus(403);

        $this->actingAs($this->otherTeacher)
            ->get(route('quizzes.ai-generator.show', $this->quiz->id))
            ->assertStatus(403);

        $this->actingAs($this->otherTeacher)
            ->post(route('quizzes.ai-generator.store', $this->quiz->id), [])
            ->assertStatus(403);
    }

    public function test_document_extractor_supports_pdf_docx_pptx_doc_ppt_and_txt(): void
    {
        $extractor = new DocumentTextExtractor();

        // 1. TXT
        $txtFile = UploadedFile::fake()->createWithContent(
            'materi.txt',
            'Laravel menggunakan arsitektur Model-View-Controller untuk memisahkan logika bisnis dan tampilan.'
        );
        $txtRes = $extractor->extract($txtFile);
        $this->assertTrue($txtRes['success']);
        $this->assertStringContainsString('Model-View-Controller', $txtRes['text']);

        // 2. DOCX
        $docxPath = $this->createSampleDocx('Eloquent ORM memetakan setiap tabel database ke kelas Model di Laravel.');
        $docxRes = $extractor->extract($docxPath, 'docx');
        @unlink($docxPath);
        $this->assertTrue($docxRes['success']);
        $this->assertStringContainsString('Eloquent ORM', $docxRes['text']);

        // 3. PPTX
        $pptxPath = $this->createSamplePptx([
            'Slide Pertama: Middleware memfilter request HTTP yang masuk ke aplikasi.',
            'Slide Kedua: Blade adalah template engine bawaan framework Laravel.',
        ]);
        $pptxRes = $extractor->extract($pptxPath, 'pptx');
        @unlink($pptxPath);
        $this->assertTrue($pptxRes['success']);
        $this->assertStringContainsString('Middleware memfilter request HTTP', $pptxRes['text']);
        $this->assertStringContainsString('Blade adalah template engine', $pptxRes['text']);

        // 4. PDF
        $pdfPath = $this->createSamplePdf('Route di Laravel didefinisikan di dalam direktori routes/web.php.');
        $pdfRes = $extractor->extract($pdfPath, 'pdf');
        @unlink($pdfPath);
        $this->assertTrue($pdfRes['success']);
        $this->assertStringContainsString('routes/web.php', $pdfRes['text']);

        // 5. Legacy DOC (UTF-16LE binary stream)
        $docContent = "\xD0\xCF\x11\xE0" . mb_convert_encoding('Migration digunakan untuk mengelola skema tabel database secara terstruktur.', 'UTF-16LE', 'UTF-8');
        $docFile = UploadedFile::fake()->createWithContent('legacy.doc', $docContent);
        $docRes = $extractor->extract($docFile);
        $this->assertTrue($docRes['success']);
        $this->assertStringContainsString('Migration digunakan', $docRes['text']);

        // 6. Legacy PPT (TextCharsAtom binary record 0x0FA0)
        $pptText = 'PowerPoint Lama: Controller menangani logika request dan mengembalikan response.';
        $utf16Ppt = mb_convert_encoding($pptText, 'UTF-16LE', 'UTF-8');
        $pptBinary = "\xD0\xCF\x11\xE0" . "\x00\x00\xA0\x0F" . pack('V', strlen($utf16Ppt)) . $utf16Ppt;
        $pptFile = UploadedFile::fake()->createWithContent('legacy.ppt', $pptBinary);
        $pptRes = $extractor->extract($pptFile);
        $this->assertTrue($pptRes['success']);
        $this->assertStringContainsString('Controller menangani logika', $pptRes['text']);
    }

    public function test_unsupported_file_format_and_empty_document_are_rejected(): void
    {
        $unsupported = UploadedFile::fake()->create('gambar.jpg', 50, 'image/jpeg');
        $res1 = $this->actingAs($this->teacher)->postJson(route('quizzes.ai-generator.generate', $this->quiz->id), [
            'document' => $unsupported,
            'question_type' => 'multiple_choice',
            'question_count' => 3,
            'difficulty' => 'sedang',
        ]);
        $res1->assertStatus(422)
            ->assertJson(['success' => false]);

        $emptyTxt = UploadedFile::fake()->createWithContent('kosong.txt', '   ');
        $res2 = $this->actingAs($this->teacher)->postJson(route('quizzes.ai-generator.generate', $this->quiz->id), [
            'document' => $emptyTxt,
            'question_type' => 'multiple_choice',
            'question_count' => 3,
            'difficulty' => 'sedang',
        ]);
        $res2->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_generate_multiple_choice_via_pdf_preview_edit_delete_save_and_student_attempt(): void
    {
        $pdfPath = $this->createSamplePdf('Laravel MVC memisahkan Model, View, dan Controller. Eloquent membantu query database.');
        $pdfFile = new UploadedFile($pdfPath, 'modul_mvc.pdf', 'application/pdf', null, true);

        $mockGemini = Mockery::mock(GeminiService::class);
        $mockGemini->shouldReceive('generateJson')
            ->once()
            ->withArgs(function (string $systemInstruction, string $userPrompt) {
                return str_contains($systemInstruction, 'Pilihan Ganda')
                    && str_contains($systemInstruction, 'Sulit')
                    && str_contains($userPrompt, 'tepat 2 buah soal')
                    && str_contains($userPrompt, 'Laravel MVC memisahkan Model');
            })
            ->andReturn([
                'success' => true,
                'content' => json_encode([
                    'questions' => [
                        [
                            'question_text' => 'Apa fungsi utama pola MVC di Laravel?',
                            'options' => [
                                'A' => 'Memisahkan Model, View, dan Controller',
                                'B' => 'Menghapus database SQL',
                                'C' => 'Menonaktifkan routing web',
                                'D' => 'Mengkompilasi CSS saja',
                            ],
                            'correct_answer' => 'A',
                            'explanation' => 'MVC memisahkan data, tampilan, dan alur kontrol.',
                            'difficulty' => 'sulit',
                        ],
                        [
                            'question_text' => 'Komponen mana yang menangani query database?',
                            'options' => [
                                'A' => 'Blade View',
                                'B' => 'Eloquent Model',
                                'C' => 'CSS Asset',
                                'D' => 'Session Cookie',
                            ],
                            'correct_answer' => 'B',
                            'explanation' => 'Eloquent Model mengelola interaksi tabel database.',
                            'difficulty' => 'sulit',
                        ],
                    ],
                ]),
                'model' => 'mock-model',
                'error' => null,
            ]);
        $this->app->instance(GeminiService::class, $mockGemini);

        $genRes = $this->actingAs($this->teacher)->postJson(route('quizzes.ai-generator.generate', $this->quiz->id), [
            'document' => $pdfFile,
            'question_type' => 'multiple_choice',
            'question_count' => 2,
            'difficulty' => 'sulit',
        ]);
        @unlink($pdfPath);

        $genRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 2,
                'redirect_url' => route('quizzes.ai-generator.preview', $this->quiz->id),
            ]);

        // Belum boleh tersimpan langsung ke tabel questions sebelum konfirmasi guru
        $this->assertEquals(0, Question::where('quiz_id', $this->quiz->id)->count());

        // Halaman preview menampilkan soal yang di-generate
        $previewRes = $this->actingAs($this->teacher)->get(route('quizzes.ai-generator.preview', $this->quiz->id));
        $previewRes->assertStatus(200);
        $previewRes->assertSee('Apa fungsi utama pola MVC di Laravel?');
        $previewRes->assertSee('Komponen mana yang menangani query database?');

        // Guru mengedit soal #1 (ubah teks & ubah kunci benar jadi C), dan menghapus soal #2 sebelum simpan
        $storeRes = $this->actingAs($this->teacher)->post(route('quizzes.ai-generator.store', $this->quiz->id), [
            'questions' => [
                0 => [
                    'delete' => '0',
                    'type' => 'multiple_choice',
                    'points' => 25,
                    'question_text' => 'Mengapa Laravel menerapkan arsitektur MVC?',
                    'options' => [
                        'A' => 'Opsi salah satu',
                        'B' => 'Opsi salah dua',
                        'C' => 'Memisahkan logika bisnis, data, dan tampilan secara rapi',
                        'D' => 'Opsi salah empat',
                    ],
                    'correct_answer' => 'C',
                    'explanation' => 'Pembahasan hasil suntingan guru.',
                ],
                1 => [
                    'delete' => '1',
                    'type' => 'multiple_choice',
                    'points' => 10,
                    'question_text' => 'Soal ini dihapus guru',
                ],
            ],
        ]);

        $storeRes->assertRedirect(route('quizzes.show', $this->quiz->id));
        $storeRes->assertSessionHas('success');

        // Hanya 1 soal yang tersimpan ke bank soal (soal #2 terhapus), dengan data hasil edit
        $savedQuestions = Question::with('options')->where('quiz_id', $this->quiz->id)->get();
        $this->assertCount(1, $savedQuestions);

        $q = $savedQuestions->first();
        $this->assertEquals('multiple_choice', $q->type);
        $this->assertEquals(25, $q->points);
        $this->assertEquals('Mengapa Laravel menerapkan arsitektur MVC?', $q->question_text);
        $this->assertEquals('Pembahasan hasil suntingan guru.', $q->explanation);
        $this->assertCount(4, $q->options);

        $correctOption = $q->options->firstWhere('is_correct', true);
        $this->assertNotNull($correctOption);
        $this->assertEquals('Memisahkan logika bisnis, data, dan tampilan secara rapi', $correctOption->option_text);

        // Siswa dapat mengerjakan ujian yang memakai soal hasil AI ini dan dinilai otomatis
        $this->actingAs($this->student)->post(route('kuis.submit', $this->quiz->id), [
            'answers' => [
                $q->id => $correctOption->id,
            ],
        ])->assertRedirect(route('kelas.show', $this->course->id));

        $submission = Submission::where('quiz_id', $this->quiz->id)
            ->where('user_id', $this->student->id)
            ->first();
        $this->assertNotNull($submission);
        $this->assertEquals(100, $submission->score);
    }

    public function test_generate_essay_via_pptx_and_regenerate_and_save(): void
    {
        $pptxPath = $this->createSamplePptx([
            'Keamanan Informasi: CIA Triad terdiri dari Confidentiality, Integrity, dan Availability.',
        ]);
        $pptxFile = new UploadedFile($pptxPath, 'cia_triad.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', null, true);

        $mockGemini = Mockery::mock(GeminiService::class);
        $mockGemini->shouldReceive('generateJson')
            ->twice()
            ->andReturn(
                [
                    'success' => true,
                    'content' => json_encode([
                        'questions' => [
                            [
                                'question_text' => 'Jelaskan konsep Confidentiality pada CIA Triad!',
                                'ideal_answer' => 'Confidentiality menjaga kerahasiaan data dari pihak tidak berwenang.',
                                'rubric' => 'Menyebutkan pencegahan akses tidak sah.',
                                'difficulty' => 'sedang',
                            ],
                        ],
                    ]),
                    'model' => 'mock-model',
                    'error' => null,
                ],
                [
                    'success' => true,
                    'content' => json_encode([
                        'questions' => [
                            [
                                'question_text' => 'Bandingkan Integrity dan Availability dalam keamanan informasi!',
                                'ideal_answer' => 'Integrity menjamin keutuhan data, Availability menjamin layanan tetap tersedia.',
                                'rubric' => 'Definisi Integrity (10 poin), definisi Availability (10 poin).',
                                'difficulty' => 'campuran',
                            ],
                        ],
                    ]),
                    'model' => 'mock-model',
                    'error' => null,
                ]
            );
        $this->app->instance(GeminiService::class, $mockGemini);

        // 1. Generate awal via PPTX
        $this->actingAs($this->teacher)->postJson(route('quizzes.ai-generator.generate', $this->quiz->id), [
            'document' => $pptxFile,
            'question_type' => 'essay',
            'question_count' => 1,
            'difficulty' => 'campuran',
        ])->assertStatus(200);
        @unlink($pptxPath);

        // 2. Generate Ulang tanpa perlu upload file lagi
        $this->actingAs($this->teacher)->postJson(route('quizzes.ai-generator.regenerate', $this->quiz->id))
            ->assertStatus(200)
            ->assertJson(['success' => true, 'count' => 1]);

        $previewRes = $this->actingAs($this->teacher)->get(route('quizzes.ai-generator.preview', $this->quiz->id));
        $previewRes->assertStatus(200);
        $previewRes->assertSee('Bandingkan Integrity dan Availability dalam keamanan informasi!');

        // 3. Simpan ke Bank Soal
        $this->actingAs($this->teacher)->post(route('quizzes.ai-generator.store', $this->quiz->id), [
            'questions' => [
                0 => [
                    'delete' => '0',
                    'type' => 'essay',
                    'points' => 30,
                    'question_text' => 'Bandingkan Integrity dan Availability dalam keamanan informasi!',
                    'ideal_answer' => 'Integrity menjamin keutuhan data, Availability menjamin akses layanan.',
                    'rubric' => 'Kelengkapan perbandingan (30 poin).',
                ],
            ],
        ])->assertRedirect(route('quizzes.show', $this->quiz->id));

        $essay = Question::where('quiz_id', $this->quiz->id)->first();
        $this->assertNotNull($essay);
        $this->assertEquals('essay', $essay->type);
        $this->assertEquals(30, $essay->points);
        $this->assertStringContainsString('Kunci Jawaban Ideal:', $essay->explanation);
        $this->assertStringContainsString('Pedoman Penilaian:', $essay->explanation);

        // 4. Pastikan fitur buat soal manual tetap berjalan berdampingan
        $this->actingAs($this->teacher)->post(route('questions.store', $this->quiz->id), [
            'type' => 'essay',
            'question_text' => 'Soal manual tambahan guru.',
            'points' => 15,
            'explanation' => 'Pedoman manual.',
        ])->assertRedirect();

        $this->assertEquals(2, Question::where('quiz_id', $this->quiz->id)->count());
    }

    private function createSampleDocx(string $text): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText($text);

        $path = tempnam(sys_get_temp_dir(), 'test_docx_') . '.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return $path;
    }

    private function createSamplePptx(array $slides): string
    {
        $path = tempnam(sys_get_temp_dir(), 'test_pptx_') . '.pptx';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"></Types>');

        foreach ($slides as $i => $slideText) {
            $num = $i + 1;
            $escaped = htmlspecialchars($slideText, ENT_XML1);
            $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'
                . '<p:cSld><p:spTree><p:sp><p:txBody><a:p><a:r><a:t>' . $escaped . '</a:t></a:r></a:p></p:txBody></p:sp></p:spTree></p:cSld>'
                . '</p:sld>';
            $zip->addFromString("ppt/slides/slide{$num}.xml", $xml);
        }

        $zip->close();

        return $path;
    }

    private function createSamplePdf(string $text): string
    {
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $stream = "BT /F1 12 Tf 72 720 Td ({$escaped}) Tj ET";
        $len = strlen($stream);

        $objects = [
            1 => "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            2 => "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            3 => "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> /MediaBox [0 0 612 792] /Contents 5 0 R >>\nendobj\n",
            4 => "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            5 => "5 0 obj\n<< /Length {$len} >>\nstream\n{$stream}\nendstream\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= $body;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $num => $off) {
            if ($num === 0) {
                continue;
            }
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        $path = tempnam(sys_get_temp_dir(), 'test_pdf_') . '.pdf';
        file_put_contents($path, $pdf);

        return $path;
    }
}

