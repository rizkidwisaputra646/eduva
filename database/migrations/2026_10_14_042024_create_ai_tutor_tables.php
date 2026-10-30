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
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->default('Percakapan Baru');
            $table->string('study_mode')->default('tanya_jawab'); // tanya_jawab, penjelasan_sederhana, ringkasan, latihan_soal, persiapan_ujian
            $table->string('difficulty')->default('menengah'); // dasar, menengah, lanjutan
            $table->timestamps();
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system'])->default('user');
            $table->longText('content');
            $table->json('sources')->nullable(); // references e.g. [{"title": "...", "type": "materi"}]
            $table->timestamps();
        });

        Schema::create('ai_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->text('question');
            $table->text('student_answer');
            $table->boolean('is_correct')->default(false);
            $table->integer('score')->default(0); // 0 - 100
            $table->text('feedback')->nullable();
            $table->string('difficulty')->default('menengah');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_evaluations');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
