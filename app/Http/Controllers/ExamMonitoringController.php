<?php

namespace App\Http\Controllers;

use App\Models\ExamLog;
use App\Models\ExamSession;
use App\Models\Quiz;
use App\Models\User;
use App\Services\ExamMonitoringService;
use App\Services\ExamSubmissionService;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamMonitoringController extends Controller
{
    public function claim(Request $r, Quiz $quiz, ExamMonitoringService $service)
    {
        $d = $r->validate(['instance_id' => 'required|uuid']);
        $result = $service->claim($quiz, $d['instance_id']);

        return response()->json($result, $result['conflict'] ? 423 : 200);
    }

    public function upload(Request $r, Quiz $quiz, ExamMonitoringService $service)
    {
        $d = $r->validate(['instance_id' => 'required|uuid', 'question_id' => 'required|integer', 'file' => 'required|file|max:10240|mimes:pdf,doc,docx,zip,rar,png,jpg,jpeg,txt']);
        $s = $service->session($quiz);
        $service->active($s);
        $service->validateClient($s, $d['instance_id']);
        abort_unless($quiz->questions()->whereKey($d['question_id'])->where('type', 'essay')->exists(), 422);
        $path = $r->file('file')->store('submissions', 'public');
        DB::transaction(function () use ($s, $d, $path, $service) {
            $locked = ExamSession::lockForUpdate()->findOrFail($s->id);
            $service->active($locked);
            $service->validateClient($locked, $d['instance_id']);
            $files = $locked->draft_files ?? [];
            $files[$d['question_id']] = $path;
            $locked->forceFill(['draft_files' => $files])->save();
        });

        return response()->json(['saved' => true]);
    }

    public function end(Request $r, Quiz $quiz, ExamSession $session)
    {
        $this->authorizeQuiz($r, $quiz);
        abort_unless($session->quiz_id === $quiz->id, 404);
        $d = $r->validate(['note' => 'required|string|max:3000', 'confirmed' => 'required|accepted']);
        DB::transaction(function () use ($session, $d, $r) {
            app(ExamSubmissionService::class)->finish($session, 'teacher');
            app(ExamMonitoringService::class)->log($session, 'TEACHER_END', ['event_metadata' => ['teacher_id' => $r->user()->id, 'note' => $d['note']], 'severity' => 'warning']);
        });

        return response()->json(['saved' => true]);
    }

    private function authorizeQuiz(Request $r, Quiz $q): void
    {
        abort_unless($r->user()->role === 'admin' || ($r->user()->role === 'guru' && (int) $q->course->teacher_id === $r->user()->id), 403);
    }

    public function index(Request $r, Quiz $quiz)
    {
        $this->authorizeQuiz($r, $quiz);

        return view('kuis.monitoring', compact('quiz'));
    }

    public function stream(Request $r, Quiz $quiz)
    {
        $this->authorizeQuiz($r, $quiz);
        abort_unless(config('exam.sse_enabled'), 404);

        return response()->stream(function () use ($r, $quiz) {
            $end = microtime(true) + 20;
            do {
                echo 'event: monitoring'."\n".'data: '.json_encode($this->data($r, $quiz)->getData(true))."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }flush();
                if (connection_aborted()) {
                    break;
                }
                sleep(2);
            } while (microtime(true) < $end);
        }, 200, ['Content-Type' => 'text/event-stream', 'Cache-Control' => 'no-cache', 'X-Accel-Buffering' => 'no']);
    }

    public function event(Request $r, Quiz $quiz, ExamMonitoringService $s)
    {
        return response()->json($s->event($quiz, $r->validate(['instance_id' => 'nullable|uuid', 'client_timestamp' => 'nullable|date', 'event_uuid' => 'required|uuid', 'event_type' => 'required|in:WINDOW_BLUR,PAGE_HIDDEN,FOCUS_RESTORED,FULLSCREEN_EXIT,FULLSCREEN_ENTER,COPY,PASTE,CUT,CONTEXTMENU,SHORTCUT_ATTEMPT,PAGE_LEAVE,PAGE_RELOAD'])));
    }

    public function heartbeat(Request $r, Quiz $quiz, ExamMonitoringService $service)
    {
        $session = $service->session($quiz);
        $service->validateClient($session, $r->input('instance_id'));
        if ($session->submitted_at) {
            return response()->json(['finished' => true, 'count' => $session->violation_count]);
        }
        if ($session->expires_at && now()->gt($session->expires_at)) {
            app(ExamSubmissionService::class)->finish($session, 'timeout');

            return response()->json(['finished' => true, 'count' => $session->violation_count]);
        }
        $d = $r->validate(['instance_id' => 'nullable|uuid', 'sequence' => 'nullable|integer|min:1', 'focused' => 'required|boolean', 'answers' => 'sometimes|array|max:500', 'answers.*' => 'nullable|string|max:20000']);

        return response()->json($service->heartbeat($quiz, $d));
    }

    public function state(Request $r, Quiz $quiz, ExamMonitoringService $service)
    {
        $s = $service->session($quiz);

        return response()->json(['session_id' => $s->id, 'finished' => (bool) $s->submitted_at, 'reason' => $s->completion_reason, 'remaining_seconds' => $s->remaining_seconds, 'heartbeat_seconds' => config('exam.heartbeat_seconds'), 'finished' => (bool) $s->submitted_at, 'session_id' => $s->id, 'policy' => array_replace(ExamMonitoringService::policy($quiz), $s->policy_snapshot ?? []), 'files' => $s->draft_files ?? [], 'answers' => $s->draft_answers ?? [], 'count' => $s->violation_count]);
    }

    public function settings(Request $r, Quiz $quiz)
    {
        $this->authorizeQuiz($r, $quiz);
        $rules = ['enabled' => 'required|boolean', 'fullscreen' => 'required|boolean', 'interactions' => 'required|boolean', 'restrict_clipboard' => 'required|boolean', 'threshold' => 'required|integer|min:1|max:100', 'preset' => 'sometimes|in:standard,strict,custom', 'fullscreen_fallback' => 'sometimes|in:allow,block'];
        foreach (['auto_submit', 'block_concurrent', 'tab_switch', 'window_focus', 'monitor_copy', 'monitor_paste', 'monitor_cut', 'monitor_contextmenu', 'monitor_shortcuts', 'restrict_copy', 'restrict_paste', 'restrict_cut', 'restrict_contextmenu', 'warnings', 'heartbeat', 'live_monitoring', 'require_seb'] as $key) {
            $rules[$key] = 'sometimes|boolean';
        }
        $d = $r->validate($rules);
        if ($r->boolean('require_seb')) {
            abort_unless(count(config('exam.seb_config_keys')) > 0, 422, 'Administrator perlu mengatur SEB_CONFIG_KEYS terlebih dahulu.');
        }
        $d = array_replace(ExamMonitoringService::policy($quiz), $d);
        $quiz->forceFill(['monitoring_policy' => $d, 'detect_tab_switch' => $d['enabled']])->save();

        return back()->with('success', 'Kebijakan tersimpan untuk sesi baru.');
    }

    public function review(Request $r, Quiz $quiz, ExamLog $log)
    {
        $this->authorizeQuiz($r, $quiz);
        abort_unless($log->quiz_id === $quiz->id, 404);
        $d = $r->validate(['status' => 'required|in:reviewed,dismissed,warning', 'note' => 'required|string|max:3000']);
        DB::transaction(function () use ($r, $log, $d) {
            DB::table('exam_monitoring_reviews')->insert(['exam_log_id' => $log->id, 'reviewed_by' => $r->user()->id, 'status' => $d['status'], 'note' => $d['note'], 'created_at' => now(), 'updated_at' => now()]);
            $log->forceFill(['review_status' => $d['status']])->save();
        });

        return response()->json(['saved' => true]);
    }

    public function accommodation(Request $r, Quiz $quiz, ExamSession $session)
    {
        $this->authorizeQuiz($r, $quiz);
        abort_unless($session->quiz_id === $quiz->id, 404);
        $d = $r->validate(['note' => 'required|string|max:3000']);
        DB::transaction(function () use ($session, $d, $r) {
            $s = ExamSession::lockForUpdate()->findOrFail($session->id);
            abort_if($s->submitted_at, 409);
            $policy = array_replace(ExamMonitoringService::policy($s->quiz), $s->policy_snapshot ?? [], ['enabled' => false, 'fullscreen' => false, 'restrict_clipboard' => false, 'auto_submit' => false, 'restrict_copy' => false, 'restrict_paste' => false, 'restrict_cut' => false, 'restrict_contextmenu' => false]);
            $s->forceFill(['policy_snapshot' => $policy])->save();
            app(ExamMonitoringService::class)->log($s, 'ACCOMMODATION', ['event_metadata' => ['teacher_id' => $r->user()->id, 'note' => $d['note']]]);
        });

        return response()->json(['saved' => true]);
    }

    public function data(Request $r, Quiz $quiz)
    {
        $this->authorizeQuiz($r, $quiz);
        $sessions = ExamSession::where('quiz_id', $quiz->id)->get()->keyBy('user_id');
        $students = $quiz->course->students()->with('activeClass')->get()->keyBy('id');
        foreach (User::with('activeClass')->whereIn('id', $sessions->keys()->diff($students->keys()))->get() as $u) {
            $students->put($u->id, $u);
        }
        $totals = ExamLog::where('quiz_id', $quiz->id)->selectRaw("user_id, SUM(CASE WHEN counted = 1 AND event_type IN ('WINDOW_BLUR','PAGE_HIDDEN') THEN 1 ELSE 0 END) as focus_count, SUM(CASE WHEN event_type = 'FULLSCREEN_EXIT' THEN 1 ELSE 0 END) as fullscreen_count, SUM(CASE WHEN event_type = 'SHORTCUT_ATTEMPT' THEN 1 ELSE 0 END) as shortcut_count, SUM(CASE WHEN event_type IN ('COPY','PASTE','CUT') THEN 1 ELSE 0 END) as clipboard_count, SUM(CASE WHEN event_type = 'CONNECTION_INTERRUPTION' THEN 1 ELSE 0 END) as offline_count")->groupBy('user_id')->get()->keyBy('user_id');
        $rows = $students->values()->map(function ($u) use ($sessions, $totals) {
            $s = $sessions->get($u->id);
            $age = $s?->last_heartbeat_at?->diffInSeconds(now());
            $t = $totals->get($u->id);

            return ['id' => $u->id, 'session_id' => $s?->id, 'name' => $u->name, 'identity' => $u->identity_number, 'class' => $u->activeClass?->name ?? '-', 'status' => $s?->status ?? 'not_started', 'focus' => $s?->focus_status, 'count' => $s?->violation_count ?? 0, 'risk' => $s?->risk_level ?? 'normal', 'last_activity' => $s?->last_heartbeat_at?->toIso8601String(), 'started_at' => $s?->started_at?->toIso8601String(), 'remaining_seconds' => $s?->remaining_seconds, 'reason' => $s?->completion_reason, 'focus_count' => (int) $t?->focus_count, 'fullscreen_count' => (int) $t?->fullscreen_count, 'shortcut_count' => (int) $t?->shortcut_count, 'clipboard_count' => (int) $t?->clipboard_count, 'offline_count' => (int) $t?->offline_count, 'connection' => $s?->submitted_at ? 'finished' : ($age === null ? 'unknown' : ($age > config('exam.offline_seconds') ? 'offline' : ($age > config('exam.uncertain_seconds') ? 'uncertain' : 'online')))];
        });
        $deferred = $sessions->filter(fn ($s) => ! $s->submitted_at && ! ($s->policy_snapshot['live_monitoring'] ?? true))->pluck('id');
        $rows = $rows->map(function ($row) use ($sessions, $deferred) {
            $session = $sessions->get($row['id']);
            if ($session && ! ($session->policy_snapshot['heartbeat'] ?? true)) {
                $row['connection'] = 'disabled';
            }
            if ($session && $deferred->contains($session->id)) {
                foreach (['focus', 'last_activity'] as $key) {
                    $row[$key] = null;
                }
                foreach (['count', 'focus_count', 'fullscreen_count', 'shortcut_count', 'clipboard_count', 'offline_count'] as $key) {
                    $row[$key] = 0;
                }
                $row['risk'] = 'deferred';
                $row['connection'] = 'deferred';
            }

            return $row;
        });
        $query = ExamLog::where('quiz_id', $quiz->id)->where(fn ($q) => $q->whereNull('exam_session_id')->orWhereNotIn('exam_session_id', $deferred))->with('user:id,name')->when($r->filled('student'), fn ($q) => $q->where('user_id', $r->integer('student')));
        if ($r->filled('before')) {
            $query->where('id', '<', $r->integer('before'));
        }
        if ($r->filled('after')) {
            $query->where('id', '>', $r->integer('after'));
        }
        $events = $r->filled('after') ? $query->orderBy('id')->limit(100)->get() : $query->latest('id')->limit(100)->get();
        $metrics = ['enrolled' => $students->count(), 'active' => $rows->where('status', 'in_progress')->count(), 'not_started' => $rows->where('status', 'not_started')->count(), 'submitted' => $sessions->whereNotNull('submitted_at')->count(), 'online' => $rows->where('connection', 'online')->count(), 'offline' => $rows->where('connection', 'offline')->count(), 'focus_lost' => $rows->where('focus_count', '>', 0)->count(), 'critical' => $rows->where('risk', 'critical')->count(), 'incidents' => $rows->sum('count'), 'auto_submitted' => $sessions->whereNotNull('auto_submitted_at')->count()];
        $reviews = DB::table('exam_monitoring_reviews')->join('users', 'users.id', '=', 'exam_monitoring_reviews.reviewed_by')->whereIn('exam_log_id', $events->pluck('id'))->select('exam_monitoring_reviews.*', 'users.name as teacher_name')->get();

        return response()->json(['sessions' => $rows, 'events' => $events, 'reviews' => $reviews, 'metrics' => $metrics, 'server_time' => now()->toIso8601String()]);
    }

    public function export(Request $r, Quiz $quiz)
    {
        $this->authorizeQuiz($r, $quiz);
        $query = ExamLog::where('quiz_id', $quiz->id)->when($r->filled('student'), fn ($q) => $q->where('user_id', $r->integer('student')))->with('user')->orderBy('id');
        $logs = $query->get();
        $reviews = DB::table('exam_monitoring_reviews')->whereIn('exam_log_id', $logs->pluck('id'))->get()->groupBy('exam_log_id');
        $sessions = ExamSession::where('quiz_id', $quiz->id)->when($r->filled('student'), fn ($q) => $q->where('user_id', $r->integer('student')))->with('user')->get();
        if (in_array($r->query('format'), ['print', 'pdf'])) {
            return view('kuis.monitoring-report', compact('quiz', 'logs', 'reviews', 'sessions'));
        }
        $headers = ['Ujian', 'Kelas', 'Siswa', 'Identitas', 'Waktu server', 'Jenis kejadian', 'Insiden ID', 'Dihitung', 'Severity', 'Durasi ms', 'Review', 'Catatan guru'];
        $rows = $logs->map(fn ($l) => [$quiz->title, $quiz->course->title, $l->user->name, $l->user->identity_number, $l->created_at->toIso8601String(), $l->event_type, $l->incident_id, $l->counted ? 'Ya' : 'Tidak', $l->severity, $l->duration_ms, $l->review_status, collect($reviews->get($l->id, []))->map(fn ($v) => $v->status.': '.$v->note)->implode(' | ')])->all();
        if ($r->query('format') === 'xlsx') {
            $tmp = tempnam(sys_get_temp_dir(), 'exam_');
            abort_unless(app(ExcelService::class)->createXlsx($headers, $rows, $tmp), 500);

            return response()->download($tmp, 'monitoring-'.$quiz->id.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
        }

        return response()->streamDownload(function () use ($headers, $rows) {
            $f = fopen('php://output', 'w');
            fputcsv($f, $headers);
            foreach ($rows as $row) {
                fputcsv($f, array_map(fn ($v) => preg_match('/^[\s]*[=+@-]/', (string) $v) ? "'".$v : $v, $row));
            }fclose($f);
        }, 'monitoring-'.$quiz->id.'.csv');
    }
}
