<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamLog extends Model
{
    protected $casts = ['counted' => 'boolean', 'event_metadata' => 'array', 'client_timestamp' => 'datetime'];

    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'user_id',
        'event_type',
        'details',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
