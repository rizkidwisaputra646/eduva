<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ExamSession;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizDurationAndSessionTest extends TestCase
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
            'title' => 'Pemrograman Web Lanjut',
            'description' => 'Mata pelajaran koding web',
            'invitation_code' => 'KOD123',
        ]);
        $this->student->enrolledCourses()->attach($this->course->id);
    }

    public function test_teacher_can_create_quiz_with_hours_and_minutes_duration()
    {
        $response = $this->actingAs($this->teacher)->post(route('quizzes.store', $this->course->id), [
            'topic' => 'Pertemuan 01',
            'title' => 'Ujian Tengah Semester Pemrograman',
            'description' => 'Kerjakan dengan teliti',
            'type' => 'quiz',
            'duration_hours' => '1',
            'duration_minutes' => '30',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('quizzes', [
            'course_id' => $this->course->id,
            'title' => 'Ujian Tengah Semester Pemrograman',
            'duration' => 90, // 1h 30m = 90 minutes
        ]);

        $quiz = Quiz::where('title', 'Ujian Tengah Semester Pemrograman')->first();
        $this->assertEquals('1 Jam 30 Menit', $quiz->formatted_duration);
    }

    public function test_teacher_can_update_quiz_duration_when_no_active_sessions()
    {
        $quiz = Quiz::create([
            'course_id' => $this->course->id,
            'topic' => 'Pertemuan 01',
            'title' => 'Kuis Logika Dasar',
            'type' => 'quiz',
            'duration' => 60,
        ]);

        $response = $this->actingAs($this->teacher)->put(route('quizzes.update', $quiz->id), [
            'topic' => 'Pertemuan 01',
            'title' => 'Kuis Logika Dasar (Revisi Durasi)',
            'type' => 'quiz',
            'duration_hours' => '0',
            'duration_minutes' => '45',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'duration' => 45,
        ]);
    }

    public function test_teacher_cannot_change_duration_if_student_session_is_in_progress()
    {
        $quiz = Quiz::create([
            'course_id' => $this->course->id,
            'topic' => 'Pertemuan 01',
            'title' => 'Kuis Algoritma',
            'type' => 'quiz',
            'duration' => 60,
        ]);

        // Student is actively taking the quiz
        ExamSession::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(60),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->teacher)->put(route('quizzes.update', $quiz->id), [
            'topic' => 'Pertemuan 01',
            'title' => 'Kuis Algoritma',
            'type' => 'quiz',
            'duration_hours' => '2',
            'duration_minutes' => '0',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(60, $quiz->fresh()->duration);
    }

    public function test_student_can_start_exam_session_and_receives_server_timer()
    {
        $quiz = Quiz::create([
            'course_id' => $this->course->id,
            'topic' => 'Pertemuan 01',
            'title' => 'Kuis CSS Grid',
            'type' => 'quiz',
            'duration' => 45,
        ]);

        $response = $this->actingAs($this->student)->postJson(route('kuis.start', $quiz->id));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'duration_minutes' => 45,
        ]);

        $session = ExamSession::where('quiz_id', $quiz->id)
            ->where('user_id', $this->student->id)
            ->first();

        $this->assertNotNull($session);
        $this->assertEquals('in_progress', $session->status);
        $this->assertNotNull($session->started_at);
        $this->assertNotNull($session->expires_at);

        // Verify remaining seconds is approximately 45 * 60 = 2700
        $this->assertGreaterThan(2690, $session->remaining_seconds);
        $this->assertLessThanOrEqual(2700, $session->remaining_seconds);
    }

    public function test_student_page_reloads_with_active_server_timer()
    {
        $quiz = Quiz::create([
            'course_id' => $this->course->id,
            'topic' => 'Pertemuan 01',
            'title' => 'Kuis JavaScript DOM',
            'type' => 'quiz',
            'duration' => 30,
        ]);

        // Student started 10 minutes ago
        $startedAt = now()->subMinutes(10);
        $expiresAt = $startedAt->copy()->addMinutes(30);

        ExamSession::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
            'started_at' => $startedAt,
            'expires_at' => $expiresAt,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->student)->get(route('kuis.kerjakan', $quiz->id));

        $response->assertStatus(200);
        $response->assertViewHas('isStarted', true);
        $response->assertViewHas('isTimedOut', false);
        
        // Remaining time should be approx 20 minutes (1200 seconds)
        $remaining = $response->viewData('remainingSeconds');
        $this->assertGreaterThan(1180, $remaining);
        $this->assertLessThanOrEqual(1200, $remaining);
    }

    public function test_auto_submit_on_timeout_persists_answers_and_marks_session_timed_out()
    {
        $quiz = Quiz::create([
            'course_id' => $this->course->id,
            'topic' => 'Pertemuan 01',
            'title' => 'Post-Test Basis Data',
            'type' => 'quiz',
            'duration' => 30,
        ]);

        $mcq = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Apa perintah untuk membaca data SQL?',
            'type' => 'multiple_choice',
            'points' => 100,
        ]);
        $opt = Option::create(['question_id' => $mcq->id, 'option_text' => 'SELECT', 'is_correct' => true]);

        // Session was created and is now expired
        ExamSession::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->student->id,
            'started_at' => now()->subMinutes(31),
            'expires_at' => now()->subMinute(),
            'status' => 'in_progress',
        ]);

        // Submit triggered automatically by timer timeout
        $response = $this->actingAs($this->student)->post(route('kuis.submit', $quiz->id), [
            'is_timeout' => '1',
            'answers' => [
                $mcq->id => $opt->id,
            ],
        ]);

        $response->assertRedirect(route('kelas.show', $quiz->course_id));
        $response->assertSessionHas('success');

        // Check ExamSession status is time_out
        $session = ExamSession::where('quiz_id', $quiz->id)->where('user_id', $this->student->id)->first();
        $this->assertEquals('time_out', $session->status);
        $this->assertNotNull($session->submitted_at);

        // Check answers are saved
        $submission = Submission::where('quiz_id', $quiz->id)->where('user_id', $this->student->id)->first();
        $this->assertNotNull($submission);
        $this->assertEquals(100, $submission->score);
    }
}

