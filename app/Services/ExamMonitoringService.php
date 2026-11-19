<?php

namespace App\Services;

use App\Models\ExamLog;
use App\Models\ExamSession;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamMonitoringService
{
    public static function policy(Quiz $quiz): array
    {
        return array_replace(['enabled' => (bool) $quiz->detect_tab_switch, 'fullscreen' => false, 'fullscreen_fallback' => 'allow', 'interactions' => true, 'restrict_clipboard' => false, 'threshold' => 3, 'auto_submit' => false, 'block_concurrent' => true, 'tab_switch' => true, 'window_focus' => true, 'monitor_copy' => true, 'monitor_paste' => true, 'monitor_cut' => true, 'monitor_contextmenu' => true, 'monitor_shortcuts' => true, 'restrict_copy' => false, 'restrict_paste' => false, 'restrict_cut' => false, 'restrict_contextmenu' => false, 'warnings' => true, 'heartbeat' => true, 'live_monitoring' => true, 'require_seb' => false, 'preset' => 'custom'], $quiz->monitoring_policy ?? []);
    }

    public function eligible(Quiz $quiz): void
    {
        $u = auth()->user();
        abort_unless($u->role === 'siswa' && $quiz->type === 'quiz' && $quiz->course->students()->where('users.id', $u->id)->exists(), 403);
        abort_if(! $quiz->is_visible || ($quiz->start_time && now()->lt($quiz->start_time)) || ($quiz->deadline && now()->gt($quiz->deadline)), 403, 'Ujian belum tersedia atau sudah ditutup.');
    }

    public function session(Quiz $quiz): ExamSession
    {
        $u = auth()->user();
        abort_unless($u->role === 'siswa' && $quiz->type === 'quiz' && $quiz->course->students()->where('users.id', $u->id)->exists(), 403);
        $s = ExamSession::where('quiz_id', $quiz->id)->where('user_id', $u->id)->firstOrFail();
        $snapshot = $s->policy_snapshot === null ? self::policy($quiz) : array_replace(self::policy(new Quiz(['detect_tab_switch' => $quiz->detect_tab_switch])), $s->policy_snapshot);
        if ($snapshot !== $s->policy_snapshot) {
            $s->forceFill(['policy_snapshot' => $snapshot])->save();
        }
        if (($s->policy_snapshot['require_seb'] ?? false)) {
            app(ExamLockdownService::class)->verify(request(), $s->policy_snapshot);
        }

        return $s;
    }

    public function active(ExamSession $s): void
    {
        abort_if($s->submitted_at || $s->status !== 'in_progress' || ($s->expires_at && now()->gt($s->expires_at)), 409, 'Sesi ujian telah berakhir.');
    }

    public function validateClient(ExamSession $s, ?string $instance): void
    {
        if ($s->client_instance_id && ($s->policy_snapshot['block_concurrent'] ?? true)) {
            abort_unless($instance && hash_equals($s->client_instance_id, $instance), 423, 'Sesi aktif di jendela lain.');
        }
    }

    public function log(ExamSession $s, string $type, array $extra = []): ExamLog
    {
        $l = new ExamLog(['quiz_id' => $s->quiz_id, 'user_id' => $s->user_id, 'event_type' => $type, 'details' => $type]);
        $l->forceFill(array_merge(['exam_session_id' => $s->id, 'event_uuid' => (string) Str::uuid()], $extra))->save();

        return $l;
    }

    public function claim(Quiz $quiz, string $instance): array
    {
        $session = $this->session($quiz);

        return DB::transaction(function () use ($session, $instance) {
            $s = ExamSession::lockForUpdate()->findOrFail($session->id);
            $this->active($s);
            $row = DB::table('exam_client_instances')->where('exam_session_id', $s->id)->where('instance_id', $instance)->first();
            if (! $row) {
                DB::table('exam_client_instances')->insert(['exam_session_id' => $s->id, 'instance_id' => $instance, 'last_seen_at' => now()]);
            }
            if ($s->client_instance_id && $s->client_instance_id !== $instance && $s->last_heartbeat_at && $s->last_heartbeat_at->diffInSeconds(now()) <= config('exam.offline_seconds') && ($s->policy_snapshot['block_concurrent'] ?? true)) {
                if (! $row?->conflict_logged_at) {
                    $this->log($s, 'SESSION_CONFLICT', ['severity' => 'warning']);
                    DB::table('exam_client_instances')->where('exam_session_id', $s->id)->where('instance_id', $instance)->update(['conflict_logged_at' => now()]);
                }

                return ['conflict' => true];
            }
            if ($s->client_instance_id !== $instance) {
                $s->client_sequence = 0;
            }
            $s->client_instance_id = $instance;
            $s->last_heartbeat_at = now();
            $s->save();

            return ['conflict' => false, 'session_id' => $s->id];
        });
    }

    public function event(Quiz $quiz, array $d): array
    {
        $session = $this->session($quiz);

        return DB::transaction(function () use ($session, $d) {
            $s = ExamSession::lockForUpdate()->findOrFail($session->id);
            $p = $s->policy_snapshot ?? self::policy($s->quiz);
            $duplicate = ExamLog::where('event_uuid', $d['event_uuid'])->first();
            if ($duplicate) {
                abort_unless($duplicate->exam_session_id === $s->id, 409);

                return ['count' => $s->violation_count, 'duplicate' => true, 'finished' => (bool) $s->submitted_at];
            }
            $this->active($s);
            $this->validateClient($s, $d['instance_id'] ?? null);
            abort_unless($p['enabled'], 409);
            $type = $d['event_type'];
            $counted = false;
            $duration = null;
            $incident = null;
            if (in_array($type, ['WINDOW_BLUR', 'PAGE_HIDDEN'])) {
                if (($type === 'WINDOW_BLUR' && ! $p['window_focus']) || ($type === 'PAGE_HIDDEN' && ! $p['tab_switch'])) {
                    return ['count' => $s->violation_count, 'ignored' => true];
                }
                if (! $s->focus_lost_at) {
                    $s->focus_lost_at = now();
                    $counted = true;
                    $incident = (string) Str::uuid();
                } else {
                    $incident = ExamLog::where('exam_session_id', $s->id)->whereIn('event_type', ['WINDOW_BLUR', 'PAGE_HIDDEN'])->whereNotNull('incident_id')->latest('id')->value('incident_id');
                }
                $s->focus_status = 'unfocused';
            } elseif ($type === 'FOCUS_RESTORED') {
                if ($s->focus_lost_at) {
                    $duration = (int) max(0, $s->focus_lost_at->diffInMilliseconds(now()));
                    $incident = ExamLog::where('exam_session_id', $s->id)->whereIn('event_type', ['WINDOW_BLUR', 'PAGE_HIDDEN'])->whereNotNull('incident_id')->latest('id')->value('incident_id');
                }
                $s->focus_lost_at = null;
                $s->focus_status = 'focused';
            } elseif ($type === 'FULLSCREEN_EXIT') {
                $counted = $p['fullscreen'] && $s->fullscreen_active;
                $s->fullscreen_active = false;
            } elseif ($type === 'FULLSCREEN_ENTER') {
                $s->fullscreen_active = true;
            } elseif (in_array($type, ['COPY', 'PASTE', 'CUT', 'CONTEXTMENU', 'SHORTCUT_ATTEMPT'])) {
                $key = $type === 'SHORTCUT_ATTEMPT' ? 'monitor_shortcuts' : 'monitor_'.strtolower($type);
                abort_unless($p['interactions'] && $p[$key], 422);
            }
            if ($counted) {
                $s->violation_count++;
                $incident ??= (string) Str::uuid();
            }
            $critical = $s->violation_count >= $p['threshold'];
            $s->risk_level = $critical ? 'critical' : ($s->violation_count ? 'warning' : 'normal');
            $s->save();
            $this->log($s, $type, ['event_uuid' => $d['event_uuid'], 'counted' => $counted, 'duration_ms' => $duration, 'incident_id' => $incident, 'severity' => $counted ? ($critical ? 'critical' : 'warning') : 'info', 'client_timestamp' => $d['client_timestamp'] ?? null]);
            if ($counted && $critical && $p['auto_submit']) {
                app(ExamSubmissionService::class)->finish($s, 'policy');
                $this->log($s, 'POLICY_AUTO_SUBMIT', ['severity' => 'critical', 'event_metadata' => ['threshold' => $p['threshold'], 'trigger' => $d['event_uuid']]]);
            }

            return ['count' => $s->violation_count, 'critical' => $critical, 'duration_ms' => $duration, 'finished' => (bool) $s->fresh()->submitted_at];
        });
    }

    public function heartbeat(Quiz $quiz, array $d): array
    {
        $session = $this->session($quiz);

        return DB::transaction(function () use ($session, $d) {
            $s = ExamSession::lockForUpdate()->findOrFail($session->id);
            if ($s->submitted_at) {
                return ['finished' => true];
            }
            if ($s->expires_at && now()->gte($s->expires_at)) {
                app(ExamSubmissionService::class)->finish($s, 'timeout');

                return ['finished' => true];
            }
            $this->active($s);
            $this->validateClient($s, $d['instance_id'] ?? null);
            $previous = isset($d['instance_id']) ? DB::table('exam_client_instances')->where('exam_session_id', $s->id)->where('instance_id', $d['instance_id'])->value('last_sequence') : $s->client_sequence;
            if (isset($d['sequence']) && $d['sequence'] <= ($previous ?? 0)) {
                return ['remaining_seconds' => $s->remaining_seconds, 'count' => $s->violation_count, 'stale' => true];
            }
            if (($s->policy_snapshot['heartbeat'] ?? true) && $s->last_heartbeat_at && $s->last_heartbeat_at->diffInSeconds(now()) > config('exam.offline_seconds')) {
                $this->log($s, 'CONNECTION_INTERRUPTION', ['duration_ms' => (int) $s->last_heartbeat_at->diffInMilliseconds(now())]);
                $this->log($s, 'CONNECTION_RESTORED');
            }
            $s->forceFill(['last_heartbeat_at' => now(), 'focus_status' => $d['focused'] ? 'focused' : 'unfocused', 'draft_answers' => $d['answers'] ?? $s->draft_answers, 'client_sequence' => $d['sequence'] ?? $s->client_sequence])->save();
            DB::table('exam_client_instances')->where('exam_session_id', $s->id)->where('instance_id', $d['instance_id'] ?? '')->update(['last_seen_at' => now(), 'last_sequence' => $d['sequence'] ?? 0]);

            return ['remaining_seconds' => $s->remaining_seconds, 'count' => $s->violation_count, 'finished' => false, 'policy' => $s->policy_snapshot, 'teacher_warning' => DB::table('exam_monitoring_reviews')->join('exam_logs', 'exam_logs.id', '=', 'exam_monitoring_reviews.exam_log_id')->where('exam_logs.exam_session_id', $s->id)->where('exam_monitoring_reviews.status', 'warning')->orderByDesc('exam_monitoring_reviews.id')->value('note')];
        });
    }
}
