<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_snapshot',
        'quiz_id',
        'user_id',
        'started_at',
        'expires_at',
        'submitted_at',
        'status',
    ];

    protected $casts = [
        'draft_files' => 'array',
        'auto_submitted_at' => 'datetime',
        'fullscreen_active' => 'boolean',
        'connection_interrupted' => 'boolean',
        'policy_snapshot' => 'array',
        'draft_answers' => 'array',
        'focus_lost_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Hitung sisa detik pengerjaan berdasarkan waktu server sekarang.
     */
    public function getRemainingSecondsAttribute(): int
    {
        if (! $this->expires_at) {
            return -1; // Tanpa batas waktu (unlimited)
        }

        $now = now();
        if ($now->greaterThanOrEqualTo($this->expires_at)) {
            return 0;
        }

        return $now->diffInSeconds($this->expires_at, false);
    }

    /**
     * Cek apakah sesi sudah kedaluwarsa secara waktu server.
     */
    public function isExpired(int $gracePeriodSeconds = 60): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return now()->greaterThan($this->expires_at->copy()->addSeconds($gracePeriodSeconds));
    }
}
