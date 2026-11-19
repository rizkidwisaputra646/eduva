<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_client_instances', fn (Blueprint $t) => $t->unsignedBigInteger('last_sequence')->default(0));
    }

    public function down(): void
    {
        Schema::table('exam_client_instances', fn (Blueprint $t) => $t->dropColumn('last_sequence'));
    }
};
