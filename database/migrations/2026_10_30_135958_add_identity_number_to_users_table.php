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
        Schema::table('users', function (Blueprint $table) {
            $table->string('identity_number')->nullable()->unique()->after('id')->comment('NIM untuk Siswa, NIP untuk Guru, ID untuk Admin');
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->string('email')->nullable()->change();
        });

        // Migrasi data pengguna lama agar dapat login menggunakan NIM/NIP/Username
        $users = \Illuminate\Support\Facades\DB::table('users')->whereNull('identity_number')->get();
        foreach ($users as $user) {
            if ($user->email === 'admin@eduva.com' || $user->role === 'admin') {
                $identifier = 'admin';
            } elseif ($user->email === 'guru@eduva.com') {
                $identifier = '198501012010011001';
            } elseif ($user->email === 'siswa@eduva.com') {
                $identifier = '202401001';
            } else {
                $prefix = $user->role === 'guru' ? '19850' : '20240';
                $identifier = $prefix . str_pad($user->id, 4, '0', STR_PAD_LEFT);
            }

            // Pastikan unik
            $count = \Illuminate\Support\Facades\DB::table('users')->where('identity_number', $identifier)->count();
            if ($count > 0) {
                $identifier .= '_' . $user->id;
            }

            \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update([
                'identity_number' => $identifier,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['identity_number', 'must_change_password']);
        });
    }
};
