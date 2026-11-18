<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminSecurityController extends Controller
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

        $query = User::query();

        // Filter status keamanan
        $filter = $request->query('filter', 'all');
        if ($filter === 'must_change') {
            $query->where('must_change_password', true);
        } elseif ($filter === 'inactive') {
            $query->where('is_active', false);
        } elseif ($filter === 'guru') {
            $query->where('role', 'guru');
        } elseif ($filter === 'siswa') {
            $query->where('role', 'siswa');
        }

        // Pencarian
        $search = $request->query('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('identity_number', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Statistik Keamanan
        $securityStats = [
            'total_users' => User::count(),
            'default_password_count' => User::where('must_change_password', true)->count(),
            'inactive_count' => User::where('is_active', false)->count(),
            'active_count' => User::where('is_active', true)->count(),
        ];

        // Log Keamanan Terkini
        $securityLogs = ActivityLog::whereIn('action', [
            'auth.failed',
            'auth.blocked',
            'auth.password_reset',
            'auth.password_change',
            'user.reset_password',
            'user.toggle_status',
        ])->latest()->take(6)->get();

        return view('admin.security.index', compact('users', 'securityStats', 'securityLogs', 'filter', 'search'));
    }

    public function resetPassword(User $user)
    {
        $this->checkAdmin();

        $defaultPassword = SystemSetting::get('default_password', 'eduva123@');

        $user->update([
            'password' => Hash::make($defaultPassword),
            'must_change_password' => true,
        ]);

        ActivityLog::record(
            'user.reset_password',
            "Admin mereset password akun {$user->name} ({$user->identity_number}) dari Pusat Keamanan Akun."
        );

        return back()->with('success', "Password akun {$user->name} ({$user->identity_number}) berhasil direset menjadi: {$defaultPassword}");
    }

    public function toggleStatus(User $user)
    {
        $this->checkAdmin();

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat mengubah status akun Anda sendiri.');
        }

        $newStatus = !$user->is_active;
        $user->update(['is_active' => $newStatus]);

        $statusText = $newStatus ? 'diaktifkan kembali' : 'dinonaktifkan';

        ActivityLog::record(
            'user.toggle_status',
            "Admin mengubah status akun {$user->name} ({$user->identity_number}) menjadi " . ($newStatus ? 'Aktif' : 'Nonaktif')
        );

        return back()->with('success', "Akun {$user->name} ({$user->identity_number}) berhasil {$statusText}.");
    }
}

