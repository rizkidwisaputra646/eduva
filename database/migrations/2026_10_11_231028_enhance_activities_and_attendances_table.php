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
        Schema::table('materials', function (Blueprint $table) {
            // type: file, page, video, label
            $table->string('type')->default('file')->after('topic');
            $table->string('video_url')->nullable()->after('file_path');
            $table->integer('order')->default(0)->after('video_url');
            $table->boolean('is_visible')->default(true)->after('order');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('deadline');
            $table->boolean('is_visible')->default(true)->after('order');
            $table->dateTime('start_time')->nullable()->after('deadline');
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->string('topic')->nullable()->after('user_id');
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('topic');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa'])->default('hadir');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['attendance_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
        Schema::dropIfExists('attendances');

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('topic');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['order', 'is_visible', 'start_time']);
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['type', 'video_url', 'order', 'is_visible']);
        });
    }
};
