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
        // 1. Tahun Ajaran (Academic Years)
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "2024/2025"
            $table->string('semester')->default('Ganjil'); // "Ganjil" / "Genap"
            $table->boolean('is_active')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active'); // active, archived, upcoming
            $table->timestamps();
        });

        // 2. Data Kelas / Rombel (School Classes)
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('name'); // e.g. "12 IPA A", "10 IPS B"
            $table->string('level')->nullable(); // "10", "11", "12"
            $table->string('department')->nullable(); // "IPA", "IPS", "Umum"
            $table->foreignId('homeroom_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active'); // active, archived
            $table->timestamps();

            $table->index(['academic_year_id', 'status']);
        });

        // 3. Riwayat Penempatan Siswa (Student Class Histories)
        Schema::create('student_class_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('status')->default('active'); // active, moved, graduated
            $table->dateTime('assigned_at')->useCurrent();
            $table->dateTime('moved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['school_class_id', 'status']);
        });

        // 4. Penugasan Guru ke Kelas (Teacher Class Assignments)
        Schema::create('teacher_class_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('role_title')->default('Pengajar');
            $table->timestamps();

            $table->index(['teacher_id', 'academic_year_id']);
            $table->index(['school_class_id', 'academic_year_id']);
        });

        // 5. Hubungkan courses dan attendances ke school_classes (opsional / nullable)
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('school_class_id')->nullable()->after('teacher_id')->constrained('school_classes')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->after('school_class_id')->constrained('academic_years')->nullOnDelete();
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('school_class_id')->nullable()->after('course_id')->constrained('school_classes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['school_class_id']);
            $table->dropColumn('school_class_id');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['school_class_id']);
            $table->dropColumn(['school_class_id', 'academic_year_id']);
        });

        Schema::dropIfExists('teacher_class_assignments');
        Schema::dropIfExists('student_class_histories');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('academic_years');
    }
};
