<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $t) {
            $t->timestamp('last_heartbeat_at')->nullable();
            $t->string('focus_status')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', fn (Blueprint $t) => $t->dropColumn(['last_heartbeat_at', 'focus_status']));
    }
};
