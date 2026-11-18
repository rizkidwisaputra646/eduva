<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLogController extends Controller
{
    private function checkAdmin(): void
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses terbatas untuk Administrator.');
        }
    }

    public function index(Request $request)
    {
        $this->checkAdmin();

        $query = ActivityLog::with('user');

        // Filter Peran Pengguna
        $role = $request->query('role');
        if ($role && in_array($role, ['admin', 'guru', 'siswa', 'system'])) {
            $query->where('user_role', $role);
        }

        // Filter Kategori Aksi
        $actionCategory = $request->query('category');
        if ($actionCategory) {
            match($actionCategory) {
                'auth' => $query->where('action', 'like', 'auth.%'),
                'user' => $query->where('action', 'like', 'user.%'),
                'course' => $query->where('action', 'like', 'course.%'),
                'system' => $query->where('action', 'like', 'system.%'),
                default => null,
            };
        }

        // Pencarian Teks
        $search = $request->query('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%");
            });
        }

        $logs = $query->latest()->paginate(25)->withQueryString();

        $stats = [
            'total' => ActivityLog::count(),
            'auth' => ActivityLog::where('action', 'like', 'auth.%')->count(),
            'user' => ActivityLog::where('action', 'like', 'user.%')->count(),
            'course' => ActivityLog::where('action', 'like', 'course.%')->count(),
        ];

        return view('admin.logs.index', compact('logs', 'stats', 'role', 'actionCategory', 'search'));
    }
}

