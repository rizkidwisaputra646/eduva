<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ExamLog;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Services\WordQuestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class QuizAntiCheatAndWordImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $student;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'guru']);
        $this->student = User::factory()->create(['role' => 'siswa']);
        $this->course = Course::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Pemrograman Web 2',
            'description' => 'Mata pelajaran pemrograman web lanjut',
            'invitation_code' => 'WEB101',
        ]);
        $this->student->enrolledCourses()->attach($this->course->id);
    }

    public function test_teacher_can_create_quiz_with_anti_cheat_setting()
    {
        $response = $this->actingAs($this->teacher)->post(route('quizzes.store', $this->course->id), [
            'topic' => 'Pertemuan 01',
            'title' => 'Kuis Anti Cheat Aktif',
            'description' => 'Ujian dengan deteksi tab',
            'type' => 'quiz',
            'detect_tab_switch' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('quizzes', [
            'course_id' => $this->course->id,
            'title' => 'Kuis Anti Cheat Aktif',
            'detect_tab_switch' => true,
        ]);

        // Create another quiz without anti-cheat
        $this->actingAs($this->teacher)->post(route('quizzes.store', $this->course->id), [
            'topic' => 'Pertemuan 01',
            'title' => 'Kuis Reguler Bebas Tab',
            'description' => 'Ujian tanpa deteksi tab',
            'type' => 'quiz',
            'detect_tab_switch' => '0',
        ]);

        $this->assertDatabaseHas('quizzes', [
            'course_id' => $this->course->id,
            'title' => 'Kuis Reguler Bebas Tab',
            'detect_tab_switch' => false,
        ]);
    }

    public function test_teacher_can_update_quiz_anti_cheat_setting()
    {
        $quiz = Quiz::create([
            'course_id' => $this->course->id,
            'topic' => 'Pertemuan 02',
            'title' => 'Kuis Awal',
            'type' => 'quiz',
            'detect_tab_switch' => false,
            'is_visible' => true,
        ]);

        $response = $this->actingAs($this->teacher)->put(route('quizzes.update', $quiz->id), [
            'topic' => 'Pertemuan 02',
            'title' => 'Kuis Awal (Diperbarui)',
            'detect_tab_switch' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue($quiz->fresh()->detect_tab_switch);
    }

    public function test_student_tab_switch_is_logged_when_anti_cheat_is_enabled()
    {
        $quiz = Quiz::create([
            'course_id' => $this->course->id,
            'topic' => 'Pertemuan 03',
            'title' => 'Ujian Tengah Semester Terpantau',
            'type' => 'quiz',
            'detect_tab_switch' => true,
            'is_visible' => true,
        ]);

        // Student triggers tab switch log
        $this->actingAs($this->student)->postJson(route('kuis.start', $quiz))->assertOk();
        $response = $this->actingAs($this->student)->postJson(route('kuis.log-event', $quiz->id), [
            'event_type' => 'tab_switch',
            'details' => 'Berpindah tab atau mengecilkan peramban',
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'logged' => true,
            'total_violations' => 1,
        ]);

        $this->assertDatabaseHas('exam_logs', [
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
            'event_type' => 'tab_switch',
        ]);

        // Second tab switch
        $response2 = $this->actingAs($this->student)->postJson(route('kuis.log-event', $quiz->id), [
            'event_type' => 'tab_switch',
            'details' => 'Meninggalkan jendela peramban ujian',
        ]);

        $response2->assertOk();
        $response2->assertJson([
            'total_violations' => 2,
        ]);

        $this->assertEquals(2, ExamLog::where('quiz_id', $quiz->id)->where('user_id', $this->student->id)->count());
    }

    public function test_student_tab_switch_is_ignored_when_anti_cheat_is_disabled()
    {
        $quiz = Quiz::create([
            'course_id' => $this->course->id,
            'topic' => 'Pertemuan 03',
            'title' => 'Ujian Bebas Tab',
            'type' => 'quiz',
            'detect_tab_switch' => false,
            'is_visible' => true,
        ]);

        $this->actingAs($this->student)->postJson(route('kuis.start', $quiz))->assertOk();
        $response = $this->actingAs($this->student)->postJson(route('kuis.log-event', $quiz->id), [
            'event_type' => 'tab_switch',
            'details' => 'Berpindah tab',
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'disabled',
            'logged' => false,
        ]);

        $this->assertEquals(0, ExamLog::where('quiz_id', $quiz->id)->count());
    }

    public function test_teacher_can_download_docx_templates()
    {
        $mcqResponse = $this->actingAs($this->teacher)->get(route('quizzes.templates.download', 'multiple_choice'));
        $mcqResponse->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $mcqResponse->headers->get('content-type'));

        $essayResponse = $this->actingAs($this->teacher)->get(route('quizzes.templates.download', 'essay'));
        $essayResponse->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $essayResponse->headers->get('content-type'));
    }

    public function test_teacher_can_upload_and_preview_docx_questions()
    {
        $quiz = Quiz::create([
            'course_id' => $this->course->id,
            'topic' => 'Pertemuan 04',
            'title' => 'Ujian Import Word',
            'type' => 'quiz',
            'detect_tab_switch' => true,
            'is_visible' => true,
        ]);

        // Generate template file using the service
        $service = app(WordQuestionService::class);
        $docxPath = $service->generateTemplate('multiple_choice');

        $uploadedFile = new UploadedFile(
            $docxPath,
            'Template_Soal_Pilihan_Ganda_Eduva.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );

        $response = $this->actingAs($this->teacher)->post(route('quizzes.import.upload', $quiz->id), [
            'question_type' => 'multiple_choice',
            'document' => $uploadedFile,
        ]);

        $response->assertRedirect(route('quizzes.import.preview', $quiz->id));
        $this->assertTrue(session()->has('quiz_import_' . $quiz->id));

        // Visit preview page
        $previewResponse = $this->actingAs($this->teacher)->get(route('quizzes.import.preview', $quiz->id));
        $previewResponse->assertOk();
        $previewResponse->assertSee('Template_Soal_Pilihan_Ganda_Eduva.docx');
        $previewResponse->assertSee('Pilihan Ganda');
    }

    public function test_teacher_can_confirm_and_persist_imported_questions()
    {
        $quiz = Quiz::create([
            'course_id' => $this->course->id,
            'topic' => 'Pertemuan 04',
            'title' => 'Ujian Import Final',
            'type' => 'quiz',
            'detect_tab_switch' => true,
            'is_visible' => true,
        ]);

        $confirmData = [
            'questions' => [
                [
                    'type' => 'multiple_choice',
                    'question_text' => 'Apa kepanjangan dari HTTP?',
                    'points' => 10,
                    'options' => [
                        'A' => 'Hypertext Transfer Protocol',
                        'B' => 'Hyper Text Tool Protocol',
                        'C' => 'High Text Transfer Path',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'HTTP adalah protokol standar transfer web.',
                ],
                [
                    'type' => 'essay',
                    'question_text' => 'Jelaskan cara kerja routing pada web MVC!',
                    'points' => 20,
                    'explanation' => 'Rubrik: menjelaskan incoming HTTP request, mapping ke Controller@action, dan return response.',
                ]
            ]
        ];

        $response = $this->actingAs($this->teacher)->post(route('quizzes.import.confirm', $quiz->id), $confirmData);

        $response->assertRedirect(route('quizzes.show', $quiz->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('questions', [
            'quiz_id' => $quiz->id,
            'question_text' => 'Apa kepanjangan dari HTTP?',
            'type' => 'multiple_choice',
            'points' => 10,
            'explanation' => 'HTTP adalah protokol standar transfer web.',
        ]);

        $this->assertDatabaseHas('options', [
            'option_text' => 'Hypertext Transfer Protocol',
            'is_correct' => true,
        ]);

        $this->assertDatabaseHas('options', [
            'option_text' => 'Hyper Text Tool Protocol',
            'is_correct' => false,
        ]);

        $this->assertDatabaseHas('questions', [
            'quiz_id' => $quiz->id,
            'question_text' => 'Jelaskan cara kerja routing pada web MVC!',
            'type' => 'essay',
            'points' => 20,
        ]);
    }

    public function test_teacher_can_edit_multiple_choice_question()
    {
        $quiz = Quiz::create([
            'course_id' => $this->course->id,
            'topic' => 'Pertemuan 05',
            'title' => 'Kuis Edit Soal',
            'type' => 'quiz',
            'is_visible' => true,
        ]);

        $question = $quiz->questions()->create([
            'question_text' => 'Teks awal sebelum diedit?',
            'type' => 'multiple_choice',
            'points' => 10,
        ]);

        $question->options()->create(['option_text' => 'Opsi A', 'is_correct' => true]);
        $question->options()->create(['option_text' => 'Opsi B', 'is_correct' => false]);

        $response = $this->actingAs($this->teacher)->put(route('questions.update', $question->id), [
            'question_text' => 'Teks soal berhasil diedit dari web!',
            'points' => 15,
            'explanation' => 'Pembahasan materi web.',
            'options' => [
                0 => 'Opsi Pertama',
                1 => 'Opsi Kedua (Kunci)',
                2 => 'Opsi Ketiga',
            ],
            'correct_option' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'question_text' => 'Teks soal berhasil diedit dari web!',
            'points' => 15,
            'explanation' => 'Pembahasan materi web.',
        ]);

        $this->assertDatabaseHas('options', [
            'question_id' => $question->id,
            'option_text' => 'Opsi Kedua (Kunci)',
            'is_correct' => true,
        ]);
    }

    public function test_teacher_can_edit_essay_question()
    {
        $quiz = Quiz::create([
            'course_id' => $this->course->id,
            'topic' => 'Pertemuan 05',
            'title' => 'Kuis Edit Essay',
            'type' => 'quiz',
            'is_visible' => true,
        ]);

        $question = $quiz->questions()->create([
            'question_text' => 'Pertanyaan awal essay?',
            'type' => 'essay',
            'points' => 20,
        ]);

        $response = $this->actingAs($this->teacher)->put(route('questions.update', $question->id), [
            'question_text' => 'Pertanyaan essay telah diperbarui!',
            'points' => 30,
            'explanation' => 'Pedoman rubrik baru.',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'question_text' => 'Pertanyaan essay telah diperbarui!',
            'points' => 30,
            'explanation' => 'Pedoman rubrik baru.',
        ]);
    }
}

