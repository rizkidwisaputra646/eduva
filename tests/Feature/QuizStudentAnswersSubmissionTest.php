<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizStudentAnswersSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $student;
    protected Course $course;
    protected Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'guru']);
        $this->student = User::factory()->create(['role' => 'siswa']);
        $this->course = Course::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Pemrograman Web 2',
            'description' => 'Mata pelajaran pemrograman web lanjut',
            'invitation_code' => 'WEB202',
        ]);
        $this->student->enrolledCourses()->attach($this->course->id);

        $this->quiz = Quiz::create([
            'course_id' => $this->course->id,
            'topic' => 'Pertemuan 01',
            'title' => 'Ujian Tengah Semester',
            'description' => 'Kerjakan dengan teliti',
            'type' => 'quiz',
            'detect_tab_switch' => true,
        ]);
    }

    public function test_student_answers_are_recorded_and_calculated_on_quiz_submission()
    {
        // 1. Setup Questions
        $mcq = Question::create([
            'quiz_id' => $this->quiz->id,
            'question_text' => 'Apa kepanjangan dari PHP?',
            'type' => 'multiple_choice',
            'points' => 50,
        ]);

        $optA = Option::create(['question_id' => $mcq->id, 'option_text' => 'Personal Home Page', 'is_correct' => false]);
        $optB = Option::create(['question_id' => $mcq->id, 'option_text' => 'PHP: Hypertext Preprocessor', 'is_correct' => true]);

        $essay = Question::create([
            'quiz_id' => $this->quiz->id,
            'question_text' => 'Jelaskan konsep MVC dalam Laravel!',
            'type' => 'essay',
            'points' => 50,
            'explanation' => 'MVC memisahkan Model, View, dan Controller.',
        ]);

        // 2. Student Submits Quiz with answers
        $response = $this->actingAs($this->student)->post(route('kuis.submit', $this->quiz->id), [
            'answers' => [
                $mcq->id => $optB->id,
                $essay->id => 'Model mengelola data, View menampilkan UI, Controller memproses logika.',
            ],
        ]);

        $response->assertRedirect(route('kelas.show', $this->quiz->course_id));
        $response->assertSessionHas('success');

        // 3. Verify Submission is saved in database with structured answers
        $submission = Submission::where('quiz_id', $this->quiz->id)
            ->where('user_id', $this->student->id)
            ->first();

        $this->assertNotNull($submission);
        $this->assertIsArray($submission->answers);
        $this->assertArrayHasKey($mcq->id, $submission->answers);
        $this->assertArrayHasKey($essay->id, $submission->answers);

        // Check MCQ answer record
        $mcqAns = $submission->answers[$mcq->id];
        $this->assertEquals('multiple_choice', $mcqAns['type']);
        $this->assertEquals($optB->id, $mcqAns['selected_option_id']);
        $this->assertTrue($mcqAns['is_correct']);
        $this->assertEquals(50, $mcqAns['points_earned']);

        // Check Essay answer record
        $essayAns = $submission->answers[$essay->id];
        $this->assertEquals('essay', $essayAns['type']);
        $this->assertStringContainsString('Model mengelola data', $essayAns['text_answer']);
    }

    public function test_teacher_can_view_student_exam_sheet_and_grade_it()
    {
        $mcq = Question::create([
            'quiz_id' => $this->quiz->id,
            'question_text' => 'Berapa hasil 5 + 5?',
            'type' => 'multiple_choice',
            'points' => 10,
        ]);
        $opt = Option::create(['question_id' => $mcq->id, 'option_text' => '10', 'is_correct' => true]);

        // Student creates submission
        $submission = Submission::create([
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->student->id,
            'score' => 100,
            'answers' => [
                $mcq->id => [
                    'question_id' => $mcq->id,
                    'type' => 'multiple_choice',
                    'selected_option_id' => $opt->id,
                    'selected_option_text' => '10',
                    'is_correct' => true,
                    'points_earned' => 10,
                    'max_points' => 10,
                ],
            ],
        ]);

        // Teacher opens submissions page
        $response = $this->actingAs($this->teacher)->get(route('assignments.submissions', $this->quiz->id));
        $response->assertStatus(200);
        $response->assertSee($this->student->name);
        $response->assertSee('Lihat Lembar Jawaban Siswa');
        $response->assertSee('Berapa hasil 5 + 5?');
        $response->assertSee('Jawaban Siswa (Kunci Benar)');

        // Teacher updates grade
        $gradeResponse = $this->actingAs($this->teacher)->put(route('submissions.grade', $submission->id), [
            'score' => 95,
            'feedback' => 'Sangat bagus dan teliti.',
        ]);

        $gradeResponse->assertRedirect();
        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'score' => 95,
            'feedback' => 'Sangat bagus dan teliti.',
        ]);
    }
}
