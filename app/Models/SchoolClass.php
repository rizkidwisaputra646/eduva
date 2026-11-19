<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class SchoolClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'name',
        'level',
        'department',
        'homeroom_teacher_id',
        'status',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    public function studentHistories(): HasMany
    {
        return $this->hasMany(StudentClassHistory::class);
    }

    public function activeStudentHistories(): HasMany
    {
        return $this->hasMany(StudentClassHistory::class)->where('status', 'active');
    }

    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            StudentClassHistory::class,
            'school_class_id', // Foreign key on StudentClassHistory
            'id',              // Foreign key on User
            'id',              // Local key on SchoolClass
            'user_id'          // Local key on StudentClassHistory
        )->where('student_class_histories.status', 'active');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherClassAssignment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
