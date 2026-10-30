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
        'start_time',
        'deadline',
        'order',
        'is_visible',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'deadline' => 'datetime',
        'is_visible' => 'boolean',
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
}
