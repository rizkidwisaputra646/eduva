<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'school_class_id',
        'topic',
        'title',
        'description',
        'start_time',
        'end_time',
        'is_active',
        'order',
        'is_visible',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function logs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function studentLog($userId)
    {
        return $this->logs()->where('user_id', $userId)->first();
    }
}
