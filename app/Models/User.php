<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'identity_number',
        'email',
        'password',
        'role',
        'must_change_password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getIdentityLabelAttribute(): string
    {
        return match($this->role) {
            'guru' => 'NIP',
            'siswa' => 'NIM',
            default => 'ID/Username',
        };
    }

    public function getNimAttribute(): ?string
    {
        return $this->identity_number;
    }

    public function getNipAttribute(): ?string
    {
        return $this->identity_number;
    }

    public function enrolledCourses()
    {
        return $this->belongsToMany(Course::class, 'course_user', 'user_id', 'course_id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    public function examLogs()
    {
        return $this->hasMany(ExamLog::class);
    }

    public function classHistories()
    {
        return $this->hasMany(StudentClassHistory::class);
    }

    public function activeClassHistory()
    {
        return $this->hasOne(StudentClassHistory::class)->where('status', 'active');
    }

    public function activeClass()
    {
        return $this->hasOneThrough(
            SchoolClass::class,
            StudentClassHistory::class,
            'user_id',
            'id',
            'id',
            'school_class_id'
        )->where('student_class_histories.status', 'active');
    }

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherClassAssignment::class, 'teacher_id');
    }

    public function homeroomClasses()
    {
        return $this->hasMany(SchoolClass::class, 'homeroom_teacher_id');
    }
}
