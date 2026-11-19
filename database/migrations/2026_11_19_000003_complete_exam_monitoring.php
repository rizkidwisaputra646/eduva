<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $t) {
            $t->uuid('client_instance_id')->nullable();
            $t->unsignedBigInteger('client_sequence')->default(0);
            $t->string('completion_reason')->nullable();
            $t->timestamp('auto_submitted_at')->nullable();
            $t->boolean('fullscreen_active')->default(false);
            $t->boolean('connection_interrupted')->default(false);
            $t->string('risk_level')->default('normal');
            $t->json('draft_files')->nullable();
        });
        Schema::table('exam_logs', function (Blueprint $t) {
            $t->string('severity')->default('info');
            $t->timestamp('client_timestamp')->nullable();
            $t->json('event_metadata')->nullable();
            $t->uuid('incident_id')->nullable()->index();
        });
        Schema::create('exam_client_instances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $t->uuid('instance_id');
            $t->timestamp('last_seen_at');
            $t->timestamp('conflict_logged_at')->nullable();
            $t->unique(['exam_session_id', 'instance_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_client_instances');
        Schema::table('exam_logs', function (Blueprint $t) {
            $t->dropIndex(['incident_id']);
            $t->dropColumn(['severity', 'client_timestamp', 'event_metadata', 'incident_id']);
        });
        Schema::table('exam_sessions', fn (Blueprint $t) => $t->dropColumn(['client_instance_id', 'client_sequence', 'completion_reason', 'auto_submitted_at', 'fullscreen_active', 'connection_interrupted', 'risk_level', 'draft_files']));
    }
};
