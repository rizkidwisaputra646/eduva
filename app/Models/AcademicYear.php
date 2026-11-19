<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'semester',
        'is_active',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function studentHistories(): HasMany
    {
        return $this->hasMany(StudentClassHistory::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherClassAssignment::class);
    }

    public static function getActive(): ?self
    {
        return self::where('is_active', true)->first();
    }
}
