<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('topic')->default('Umum')->after('course_id');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('topic')->default('Umum')->after('course_id');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('topic');
        });
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('topic');
        });
    }
};
