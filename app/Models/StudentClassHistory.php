<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentClassHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'school_class_id',
        'academic_year_id',
        'status',
        'assigned_at',
        'moved_at',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'moved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
