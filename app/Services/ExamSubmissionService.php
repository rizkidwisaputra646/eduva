<?php

namespace App\Services;

use App\Models\ExamSession;
use App\Models\Submission;
use Illuminate\Support\Facades\DB;

class ExamSubmissionService
{
    // All finish paths share the same locked attempt, grading and idempotency guard.
    public function finish(ExamSession $session, string $reason = 'submitted', ?array $answers = null, ?array $files = null): Submission
    {
        return DB::transaction(function () use ($session, $reason, $answers, $files) {
            $s = ExamSession::lockForUpdate()->findOrFail($session->id);
            $existing = Submission::where('quiz_id', $s->quiz_id)->where('user_id', $s->user_id)->first();
            if ($s->submitted_at && $existing) {
                return $existing;
            }
            $raw = $answers ?? $s->draft_answers ?? [];
            $paths = $files ?? $s->draft_files ?? [];
            $earned = 0;
            $max = 0;
            $essay = false;
            $graded = [];
            foreach ($s->quiz->questions()->with('options')->get() as $q) {
                $max += (float) $q->points;
                if ($q->type === 'multiple_choice') {
                    $option = $q->options->firstWhere('id', (int) ($raw[$q->id] ?? 0));
                    $correct = (bool) $option?->is_correct;
                    $points = $correct ? (float) $q->points : 0;
                    $earned += $points;
                    $graded[$q->id] = ['question_id' => $q->id, 'type' => $q->type, 'selected_option_id' => $option?->id, 'selected_option_text' => $option?->option_text, 'is_correct' => $correct, 'points_earned' => $points, 'max_points' => (float) $q->points];
                } else {
                    $essay = true;
                    $graded[$q->id] = ['question_id' => $q->id, 'type' => $q->type, 'text_answer' => $raw[$q->id] ?? null, 'file_path' => $paths[$q->id] ?? null, 'points_earned' => null, 'max_points' => (float) $q->points];
                }
            }
            $submission = Submission::firstOrCreate(['quiz_id' => $s->quiz_id, 'user_id' => $s->user_id], ['answers' => $graded, 'score' => $max > 0 ? round($earned / $max * 100) : ($essay ? null : 100), 'file_path' => collect($paths)->first()]);
            $s->forceFill(['status' => $reason === 'timeout' ? 'time_out' : 'submitted', 'completion_reason' => $reason, 'submitted_at' => now(), 'auto_submitted_at' => in_array($reason, ['timeout', 'policy']) ? now() : null])->save();

            return $submission;
        });
    }
}
