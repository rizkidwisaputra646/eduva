<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
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

        // Filter Role
        $role = $request->query('role');
        if ($role && in_array($role, ['guru', 'siswa', 'admin'])) {
            $query->where('role', $role);
        }

        // Filter Status Aktif
        $status = $request->query('status');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        // Filter Status Password
        $passwordStatus = $request->query('password_status');
        if ($passwordStatus === 'default') {
            $query->where('must_change_password', true);
        } elseif ($passwordStatus === 'custom') {
            $query->where('must_change_password', false);
        }

        // Pencarian (Nama, NIM/NIP, Email)
        $search = $request->query('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('identity_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Counter untuk Tabs
        $counts = [
            'all' => User::count(),
            'guru' => User::where('role', 'guru')->count(),
            'siswa' => User::where('role', 'siswa')->count(),
            'inactive' => User::where('is_active', false)->count(),
            'default_password' => User::where('must_change_password', true)->count(),
        ];

        return view('admin.users.index', compact('users', 'counts', 'role', 'status', 'search'));
    }

    public function store(Request $request)
    {
        $this->checkAdmin();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'identity_number' => ['required', 'string', 'max:50', 'unique:'.User::class.',identity_number'],
            'role' => ['required', 'in:guru,siswa'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class.',email'],
        ], [
            'identity_number.unique' => 'Nomor Identitas (NIM/NIP) ini sudah terdaftar.',
            'email.unique' => 'Alamat email ini sudah terdaftar.',
            'role.in' => 'Peran akun harus Guru atau Siswa.',
        ]);

        $defaultPassword = SystemSetting::get('default_password', 'eduva123@');

        $user = User::create([
            'name' => $request->name,
            'identity_number' => $request->identity_number,
            'email' => $request->email,
            'password' => Hash::make($defaultPassword),
            'role' => $request->role,
            'must_change_password' => true,
            'is_active' => true,
        ]);

        ActivityLog::record(
            'user.create',
            "Admin menambahkan akun {$user->role} baru: {$user->name} ({$user->identity_number})"
        );

        return redirect()->route('admin.users.index')->with('success', "Akun {$user->name} ({$user->identity_number}) berhasil dibuat dengan password default: {$defaultPassword}");
    }

    public function update(Request $request, User $user)
    {
        $this->checkAdmin();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'identity_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique(User::class, 'identity_number')->ignore($user->id),
            ],
            'role' => ['required', 'in:guru,siswa,admin'],
            'email' => [
                'nullable',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($user->id),
            ],
            'is_active' => ['required', 'boolean'],
        ]);

        // Cegah admin menonaktifkan akunnya sendiri
        if ($user->id === Auth::id() && !$request->boolean('is_active')) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->update([
            'name' => $request->name,
            'identity_number' => $request->identity_number,
            'role' => $request->role,
            'email' => $request->email,
            'is_active' => $request->boolean('is_active'),
        ]);

        ActivityLog::record(
            'user.update',
            "Admin memperbarui data akun {$user->name} ({$user->identity_number})"
        );

        return back()->with('success', "Data akun {$user->name} berhasil diperbarui.");
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
            "Admin mereset password akun {$user->name} ({$user->identity_number}) ke password default"
        );

        return back()->with('success', "Password akun {$user->name} ({$user->identity_number}) berhasil direset menjadi: {$defaultPassword}");
    }

    public function destroy(User $user)
    {
        $this->checkAdmin();

        if (Auth::id() === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $name = $user->name;
        $ident = $user->identity_number;

        $user->delete();

        ActivityLog::record(
            'user.delete',
            "Admin menghapus akun pengguna: {$name} ({$ident})"
        );

        return redirect()->route('admin.users.index')->with('success', "Pengguna {$name} ({$ident}) berhasil dihapus dari sistem.");
    }
}
