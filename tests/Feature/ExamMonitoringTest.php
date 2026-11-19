<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ExamLog;
use App\Models\ExamSession;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExamMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_correlated_events_and_return_duration_and_deduplication(): void
    {
        $this->travelTo(now()->startOfSecond());
        $teacher = User::factory()->create(['role' => 'guru']);
        $student = User::factory()->create(['role' => 'siswa']);
        $course = Course::create(['title' => 'Test', 'description' => 'Test', 'teacher_id' => $teacher->id]);
        $course->students()->attach($student);
        $quiz = Quiz::create(['course_id' => $course->id, 'title' => 'Test', 'type' => 'quiz', 'detect_tab_switch' => true]);
        $session = ExamSession::create(['quiz_id' => $quiz->id, 'user_id' => $student->id, 'started_at' => now(), 'status' => 'in_progress']);
        $url = '/kuis/'.$quiz->id.'/monitoring/events';
        $event = ['event_uuid' => (string) Str::uuid(), 'event_type' => 'WINDOW_BLUR'];
        $this->actingAs($student)->postJson($url, $event)->assertOk()->assertJsonPath('count', 1);
        $this->postJson($url, $event)->assertOk()->assertJsonPath('count', 1);
        $this->postJson($url, ['event_uuid' => (string) Str::uuid(), 'event_type' => 'PAGE_HIDDEN'])->assertOk()->assertJsonPath('count', 1);
        $this->travel(9)->seconds();
        $this->postJson($url, ['event_uuid' => (string) Str::uuid(), 'event_type' => 'FOCUS_RESTORED'])->assertOk()->assertJsonPath('duration_ms', 9000);
        $this->postJson('/kuis/'.$quiz->id.'/heartbeat', ['focused' => true, 'answers' => ['12' => 'Jawaban esai']])->assertOk();
        $this->getJson('/kuis/'.$quiz->id.'/monitoring/state')->assertOk()->assertJsonPath('answers.12', 'Jawaban esai');
        $this->postJson($url, ['event_uuid' => (string) Str::uuid(), 'event_type' => 'WINDOW_BLUR'])->assertOk()->assertJsonPath('count', 2);
        $session->update(['submitted_at' => now()]);
        $this->postJson($url, ['event_uuid' => (string) Str::uuid(), 'event_type' => 'WINDOW_BLUR'])->assertStatus(409);
        $this->getJson('/guru/ujian/'.$quiz->id.'/monitoring/data')->assertForbidden();
        $this->actingAs($teacher)->getJson('/guru/ujian/'.$quiz->id.'/monitoring/data')->assertOk();
        $log = ExamLog::where('quiz_id', $quiz->id)->firstOrFail();
        $this->postJson('/guru/ujian/'.$quiz->id.'/monitoring/review/'.$log->id, ['status' => 'dismissed', 'note' => 'Gangguan perangkat terkonfirmasi'])->assertOk();
        $this->assertDatabaseHas('exam_logs', ['id' => $log->id, 'review_status' => 'dismissed']);
        $this->assertDatabaseHas('exam_monitoring_reviews', ['exam_log_id' => $log->id, 'reviewed_by' => $teacher->id]);
    }
}
