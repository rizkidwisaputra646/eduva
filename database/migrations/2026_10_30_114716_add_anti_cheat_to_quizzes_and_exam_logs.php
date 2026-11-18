<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'detect_tab_switch')) {
                $table->boolean('detect_tab_switch')->default(false)->after('type');
            }
        });

        Schema::table('exam_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_logs', 'quiz_id')) {
                $table->foreignId('quiz_id')->after('id')->constrained()->cascadeOnDelete();
            }
            if (!Schema::hasColumn('exam_logs', 'user_id')) {
                $table->foreignId('user_id')->after('quiz_id')->constrained()->cascadeOnDelete();
            }
            if (!Schema::hasColumn('exam_logs', 'event_type')) {
                $table->string('event_type')->default('tab_switch')->after('user_id');
            }
            if (!Schema::hasColumn('exam_logs', 'details')) {
                $table->text('details')->nullable()->after('event_type');
            }
        });

        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'explanation')) {
                $table->text('explanation')->nullable()->after('points');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'explanation')) {
                $table->dropColumn('explanation');
            }
        });

        Schema::table('exam_logs', function (Blueprint $table) {
            if (Schema::hasColumn('exam_logs', 'quiz_id')) {
                $table->dropForeign(['quiz_id']);
                $table->dropForeign(['user_id']);
                $table->dropColumn(['quiz_id', 'user_id', 'event_type', 'details']);
            }
        });

        Schema::table('quizzes', function (Blueprint $table) {
            if (Schema::hasColumn('quizzes', 'detect_tab_switch')) {
                $table->dropColumn('detect_tab_switch');
            }
        });
    }
};
