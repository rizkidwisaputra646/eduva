<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'user_id', 'topic', 'content'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
