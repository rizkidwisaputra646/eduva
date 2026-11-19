<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ExamLog;
use App\Models\ExamSession;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use App\Services\ExamMonitoringService;
use App\Services\ExamSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExamSecurityTest extends TestCase
{
    use RefreshDatabase;

    private $teacher;

    private $student;

    private $quiz;

    private $session;

    private $base;

    private $instance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->startOfSecond());
        $this->teacher = User::factory()->create(['role' => 'guru']);
        $this->student = User::factory()->create(['role' => 'siswa']);
        $c = Course::create(['title' => 'Course', 'description' => 'Course', 'teacher_id' => $this->teacher->id]);
        $c->students()->attach($this->student);
        $this->quiz = Quiz::create(['course_id' => $c->id, 'title' => 'Exam', 'type' => 'quiz', 'detect_tab_switch' => true, 'is_visible' => true, 'duration' => 30]);
        $this->session = ExamSession::create(['quiz_id' => $this->quiz->id, 'user_id' => $this->student->id, 'started_at' => now(), 'expires_at' => now()->addMinutes(30), 'policy_snapshot' => ExamMonitoringService::policy($this->quiz)]);
        $this->base = '/kuis/'.$this->quiz->id;
        $this->instance = (string) Str::uuid();
        $this->actingAs($this->student);
    }

    private function event(string $type, array $extra = [])
    {
        return $this->postJson($this->base.'/monitoring/events', array_merge(['event_uuid' => (string) Str::uuid(), 'event_type' => $type, 'instance_id' => $this->instance], $extra));
    }

    public function test_concurrent_client_is_rejected_and_logged_without_erasing_answers(): void
    {
        $this->postJson($this->base.'/monitoring/claim', ['instance_id' => $this->instance])->assertOk();
        $other = (string) Str::uuid();
        $this->postJson($this->base.'/monitoring/claim', ['instance_id' => $other])->assertStatus(423);
        $this->assertDatabaseHas('exam_logs', ['event_type' => 'SESSION_CONFLICT']);
        $this->postJson($this->base.'/heartbeat', ['instance_id' => $other, 'focused' => true])->assertStatus(423);
        $this->travel(21)->seconds();
        $this->postJson($this->base.'/monitoring/claim', ['instance_id' => $other])->assertOk();
        $this->event('PAGE_HIDDEN')->assertStatus(423);
    }

    public function test_auto_submit_at_threshold_grades_saved_answers_exactly_once(): void
    {
        $q = Question::create(['quiz_id' => $this->quiz->id, 'question_text' => 'Question', 'type' => 'multiple_choice', 'points' => 100]);
        $o = Option::create(['question_id' => $q->id, 'option_text' => 'Yes', 'is_correct' => true]);
        $p = $this->session->policy_snapshot;
        $p['auto_submit'] = true;
        $p['threshold'] = 1;
        $this->session->forceFill(['policy_snapshot' => $p, 'draft_answers' => [$q->id => (string) $o->id]])->save();
        $uuid = (string) Str::uuid();
        $this->event('PAGE_HIDDEN', ['event_uuid' => $uuid])->assertOk()->assertJsonPath('finished', true);
        $this->event('PAGE_HIDDEN', ['event_uuid' => $uuid])->assertOk()->assertJsonPath('duplicate', true);
        $this->assertSame(1, Submission::count());
        $this->assertEquals(100, Submission::first()->score);
        $this->assertSame('policy', $this->session->fresh()->completion_reason);
        $this->event('WINDOW_BLUR')->assertStatus(409);
        $this->post($this->base.'/submit', ['answers' => []])->assertRedirect();
        $this->assertEquals(100, Submission::first()->score);
    }

    public function test_threshold_without_explicit_automation_does_not_submit(): void
    {
        $p = $this->session->policy_snapshot;
        $p['threshold'] = 1;
        $this->session->forceFill(['policy_snapshot' => $p])->save();
        $this->event('WINDOW_BLUR')->assertOk()->assertJsonPath('critical', true)->assertJsonPath('finished', false);
        $this->assertSame(0, Submission::count());
    }

    public function test_heartbeat_sequences_prevent_stale_answer_overwrite_and_network_loss_is_not_a_violation(): void
    {
        $this->postJson($this->base.'/heartbeat', ['focused' => true, 'sequence' => 2, 'answers' => ['1' => 'new']])->assertOk();
        $this->postJson($this->base.'/heartbeat', ['focused' => false, 'sequence' => 1, 'answers' => ['1' => 'old']])->assertOk()->assertJsonPath('stale', true);
        $this->assertSame('new', $this->session->fresh()->draft_answers[1]);
        $this->travel(25)->seconds();
        $this->postJson($this->base.'/heartbeat', ['focused' => true, 'sequence' => 3])->assertOk();
        $this->assertEquals(0, $this->session->fresh()->violation_count);
        $this->assertDatabaseHas('exam_logs', ['event_type' => 'CONNECTION_INTERRUPTION']);
    }

    public function test_policy_snapshot_and_draft_survive_refresh_and_policy_edit(): void
    {
        $this->postJson($this->base.'/heartbeat', ['focused' => true, 'answers' => ['1' => 'draft']])->assertOk();
        $original = $this->session->expires_at->toISOString();
        $this->quiz->forceFill(['monitoring_policy' => ['enabled' => false, 'threshold' => 99]])->save();
        $this->getJson($this->base.'/monitoring/state')->assertJsonPath('policy.threshold', 3)->assertJsonPath('answers.1', 'draft');
        $this->postJson($this->base.'/start')->assertOk();
        $this->assertSame($original, $this->session->fresh()->expires_at->toISOString());
    }

    public function test_expired_session_uses_server_draft_and_maintenance_is_idempotent(): void
    {
        $this->session->forceFill(['expires_at' => now()->subSecond(), 'draft_answers' => ['1' => 'saved']])->save();
        $this->artisan('exam:maintain')->assertExitCode(0);
        $this->artisan('exam:maintain')->assertExitCode(0);
        $this->assertSame(1, Submission::count());
        $this->assertSame('timeout', $this->session->fresh()->completion_reason);
        $this->event('PAGE_HIDDEN')->assertStatus(409);
    }

    public function test_permissions_and_teacher_end_and_review_preserve_evidence(): void
    {
        $this->event('WINDOW_BLUR')->assertOk();
        $log = ExamLog::first();
        $other = User::factory()->create(['role' => 'guru']);
        $url = '/guru/ujian/'.$this->quiz->id.'/monitoring';
        $this->actingAs($other)->getJson($url.'/data')->assertForbidden();
        $this->postJson($url.'/review/'.$log->id, ['status' => 'dismissed', 'note' => 'No'])->assertForbidden();
        $this->actingAs($this->teacher)->get($url)->assertOk();
        $this->postJson($url.'/review/'.$log->id, ['status' => 'dismissed', 'note' => 'Verified device issue'])->assertOk();
        $this->assertDatabaseHas('exam_logs', ['id' => $log->id, 'event_type' => 'WINDOW_BLUR']);
        $this->postJson($url.'/end/'.$this->session->id, ['note' => 'Supervised termination', 'confirmed' => true])->assertOk();
        $this->assertSame('teacher', $this->session->fresh()->completion_reason);
        $this->assertSame(1, Submission::count());
    }

    public function test_student_cannot_read_or_write_another_students_attempt(): void
    {
        $other = User::factory()->create(['role' => 'siswa']);
        $this->actingAs($other)->getJson($this->base.'/monitoring/state')->assertForbidden();
        $this->event('PAGE_HIDDEN')->assertForbidden();
        $this->getJson('/guru/ujian/'.$this->quiz->id.'/monitoring/data')->assertForbidden();
    }

    public function test_fullscreen_exit_counts_only_after_fullscreen_enter(): void
    {
        $p = $this->session->policy_snapshot;
        $p['fullscreen'] = true;
        $this->session->forceFill(['policy_snapshot' => $p])->save();
        $this->event('FULLSCREEN_EXIT')->assertJsonPath('count', 0);
        $this->event('FULLSCREEN_ENTER')->assertOk();
        $this->event('FULLSCREEN_EXIT')->assertJsonPath('count', 1);
        $this->event('FULLSCREEN_EXIT')->assertJsonPath('count', 1);
    }

    public function test_reports_include_notes_and_correct_counts_in_all_formats(): void
    {
        $this->event('WINDOW_BLUR')->assertOk();
        $this->event('PAGE_HIDDEN')->assertOk();
        $url = '/guru/ujian/'.$this->quiz->id.'/monitoring';
        $this->actingAs($this->teacher)->getJson($url.'/data')->assertJsonPath('metrics.incidents', 1);
        $this->get($url.'/export?format=csv')->assertOk()->assertDownload();
        $this->get($url.'/export?format=xlsx')->assertOk()->assertDownload();
        $this->get($url.'/export?format=print')->assertOk()->assertSee('Raw event');
    }

    public function test_seb_hash_gate_rejects_missing_and_accepts_valid_request_hash(): void
    {
        $key = str_repeat('a', 64);
        config(['exam.seb_config_keys' => [$key]]);
        $p = $this->session->policy_snapshot;
        $p['require_seb'] = true;
        $this->session->forceFill(['policy_snapshot' => $p])->save();
        $url = url($this->base.'/monitoring/state');
        $this->getJson($url)->assertForbidden();
        $this->withHeader('X-SafeExamBrowser-ConfigKeyHash', hash('sha256', $url.$key))->getJson($url)->assertOk();
    }

    public function test_upload_is_saved_for_reconnect_and_teacher_accommodation_is_audited(): void
    {
        Storage::fake('public');
        $q = Question::create(['quiz_id' => $this->quiz->id, 'question_text' => 'Essay', 'type' => 'essay', 'points' => 100]);
        $this->post($this->base.'/monitoring/upload', ['instance_id' => $this->instance, 'question_id' => $q->id, 'file' => UploadedFile::fake()->create('answer.txt', 1, 'text/plain')])->assertOk();
        $this->assertArrayHasKey($q->id, $this->session->fresh()->draft_files);
        $this->actingAs($this->teacher)->postJson('/guru/ujian/'.$this->quiz->id.'/monitoring/accommodation/'.$this->session->id, ['note' => 'Approved accessibility exception'])->assertOk();
        $this->assertFalse($this->session->fresh()->policy_snapshot['enabled']);
        $this->assertDatabaseHas('exam_logs', ['event_type' => 'ACCOMMODATION']);
    }

    public function test_deferred_monitoring_and_disabled_heartbeat_preserve_autosave(): void
    {
        $p = $this->session->policy_snapshot;
        $p['live_monitoring'] = false;
        $p['heartbeat'] = false;
        $this->session->forceFill(['policy_snapshot' => $p])->save();
        $this->event('WINDOW_BLUR')->assertOk();
        $this->postJson($this->base.'/heartbeat', ['focused' => true, 'answers' => ['1' => 'saved']])->assertOk();
        $this->travel(25)->seconds();
        $this->postJson($this->base.'/heartbeat', ['focused' => true])->assertOk();
        $this->assertDatabaseMissing('exam_logs', ['event_type' => 'CONNECTION_INTERRUPTION']);
        $url = '/guru/ujian/'.$this->quiz->id.'/monitoring';
        $this->actingAs($this->teacher)->getJson($url.'/data')->assertOk()->assertJsonCount(0, 'events')->assertJsonPath('sessions.0.risk', 'deferred');
        app(ExamSubmissionService::class)->finish($this->session, 'teacher');
        $this->getJson($url.'/data')->assertJsonCount(1, 'events');
    }

    public function test_management_permissions_and_retention_and_sse_gate(): void
    {
        $this->putJson('/quizzes/'.$this->quiz->id, ['title' => 'tampered'])->assertForbidden();
        $this->getJson('/quizzes/'.$this->quiz->id)->assertForbidden();
        $url = '/guru/ujian/'.$this->quiz->id.'/monitoring/stream';
        $this->getJson($url)->assertForbidden();
        $this->actingAs($this->teacher)->getJson($url)->assertNotFound();
        $log = app(ExamMonitoringService::class)->log($this->session, 'PAGE_RELOAD');
        $log->forceFill(['created_at' => now()->subDays(181)])->save();
        $this->artisan('exam:maintain --prune')->assertExitCode(0);
        $this->assertDatabaseMissing('exam_logs', ['id' => $log->id]);
        $this->assertDatabaseHas('exam_sessions', ['id' => $this->session->id]);
    }
}
