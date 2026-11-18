<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'action',
        'description',
        'ip_address',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an activity log cleanly and safely
     */
    public static function record(string $action, string $description, ?User $user = null): ?self
    {
        try {
            $currentUser = $user ?? Auth::user();

            return self::create([
                'user_id' => $currentUser?->id,
                'user_name' => $currentUser?->name ?? 'Sistem / Anonim',
                'user_role' => $currentUser?->role ?? 'system',
                'action' => $action,
                'description' => $description,
                'ip_address' => Request::ip(),
                'user_agent' => substr((string) Request::userAgent(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            // Silently catch logging issues so core transactions never fail
            return null;
        }
    }
}

