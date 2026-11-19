<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'topic',
        'title',
        'description',
        'type', // 'quiz' or 'assignment'
        'detect_tab_switch',
        'duration',
        'start_time',
        'deadline',
        'order',
        'is_visible',
    ];

    protected $casts = [
        'monitoring_policy' => 'array',
        'start_time' => 'datetime',
        'deadline' => 'datetime',
        'duration' => 'integer',
        'is_visible' => 'boolean',
        'detect_tab_switch' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function examLogs()
    {
        return $this->hasMany(ExamLog::class);
    }

    public function examSessions()
    {
        return $this->hasMany(ExamSession::class);
    }

    public function getFormattedDurationAttribute(): string
    {
        if (! $this->duration || $this->duration <= 0) {
            return 'Tanpa Batas Durasi';
        }

        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours} Jam {$minutes} Menit";
        } elseif ($hours > 0) {
            return "{$hours} Jam";
        } else {
            return "{$minutes} Menit";
        }
    }
}
