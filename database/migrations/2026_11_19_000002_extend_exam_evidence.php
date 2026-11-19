<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', fn (Blueprint $t) => $t->json('monitoring_policy')->nullable());
        Schema::table('exam_sessions', function (Blueprint $t) {
            $t->json('policy_snapshot')->nullable();
            $t->json('draft_answers')->nullable();
            $t->unsignedInteger('violation_count')->default(0);
            $t->timestamp('focus_lost_at')->nullable();
        });
        Schema::table('exam_logs', function (Blueprint $t) {
            $t->foreignId('exam_session_id')->nullable()->constrained()->cascadeOnDelete();
            $t->uuid('event_uuid')->nullable()->unique();
            $t->unsignedBigInteger('duration_ms')->nullable();
            $t->boolean('counted')->default(false);
            $t->string('review_status')->default('pending');
            $t->index(['quiz_id', 'user_id', 'id']);
        });
        Schema::create('exam_monitoring_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('exam_log_id')->constrained()->cascadeOnDelete();
            $t->foreignId('reviewed_by')->constrained('users');
            $t->string('status');
            $t->text('note');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_monitoring_reviews');
        Schema::table('exam_logs', function (Blueprint $t) {
            $t->dropForeign(['exam_session_id']);
            $t->dropUnique(['event_uuid']);
            $t->dropIndex(['quiz_id', 'user_id', 'id']);
            $t->dropColumn(['exam_session_id', 'event_uuid', 'duration_ms', 'counted', 'review_status']);
        });
        Schema::table('exam_sessions', fn (Blueprint $t) => $t->dropColumn(['policy_snapshot', 'draft_answers', 'violation_count', 'focus_lost_at']));
        Schema::table('quizzes', fn (Blueprint $t) => $t->dropColumn('monitoring_policy'));
    }
};
