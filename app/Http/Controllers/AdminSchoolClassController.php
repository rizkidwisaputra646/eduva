<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\SchoolClass;
use App\Models\StudentClassHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminSchoolClassController extends Controller
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

        $query = SchoolClass::with(['academicYear', 'homeroomTeacher'])
            ->withCount(['activeStudentHistories as students_count']);

        // Filter Tahun Ajaran
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->query('academic_year_id'));
        }

        // Filter Tingkat
        if ($request->filled('level')) {
            $query->where('level', $request->query('level'));
        }

        // Filter Jurusan
        if ($request->filled('department')) {
            $query->where('department', $request->query('department'));
        }

        // Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('level', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%")
                  ->orWhereHas('homeroomTeacher', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $classes = $query->latest()->paginate(12)->withQueryString();

        $academicYears = AcademicYear::orderBy('is_active', 'desc')->orderBy('name', 'desc')->get();
        $teachers = User::where('role', 'guru')->where('is_active', true)->orderBy('name')->get();
        $activeYear = AcademicYear::getActive();

        $stats = [
            'total_classes' => SchoolClass::count(),
            'active_classes' => SchoolClass::where('status', 'active')->count(),
            'total_students_placed' => StudentClassHistory::where('status', 'active')->distinct('user_id')->count('user_id'),
        ];

        return view('admin.academic.classes.index', compact('classes', 'academicYears', 'teachers', 'activeYear', 'stats'));
    }

    public function store(Request $request)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'academic_year_id' => 'required|exists:academic_years,id',
            'level' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:50',
            'homeroom_teacher_id' => 'nullable|exists:users,id',
        ]);

        $class = SchoolClass::create([
            'name' => $validated['name'],
            'academic_year_id' => $validated['academic_year_id'],
            'level' => $validated['level'] ?? null,
            'department' => $validated['department'] ?? null,
            'homeroom_teacher_id' => $validated['homeroom_teacher_id'] ?? null,
            'status' => 'active',
        ]);

        ActivityLog::record(
            'class.create',
            "Admin " . Auth::user()->name . " membuat kelas rombel baru: {$class->name}"
        );

        return back()->with('success', "Kelas \"{$class->name}\" berhasil dibuat.");
    }

    public function show(SchoolClass $schoolClass, Request $request)
    {
        $this->checkAdmin();

        $schoolClass->load([
            'academicYear',
            'homeroomTeacher',
            'courses.teacher',
        ]);

        // Daftar Siswa Aktif di Kelas Ini
        $historiesQuery = $schoolClass->studentHistories()
            ->where('status', 'active')
            ->with(['user']);

        if ($request->filled('student_search')) {
            $sSearch = $request->query('student_search');
            $historiesQuery->whereHas('user', function ($uq) use ($sSearch) {
                $uq->where('name', 'like', "%{$sSearch}%")
                   ->orWhere('identity_number', 'like', "%{$sSearch}%")
                   ->orWhere('email', 'like', "%{$sSearch}%");
            });
        }

        $activeHistories = $historiesQuery->latest('assigned_at')->paginate(15)->withQueryString();

        // Riwayat Siswa yang pernah keluar/pindah dari kelas ini
        $pastHistories = $schoolClass->studentHistories()
            ->where('status', '!=', 'active')
            ->with('user')
            ->latest('moved_at')
            ->take(10)
            ->get();

        // Cari Siswa role 'siswa' yang BELUM ditempatkan di kelas manapun pada tahun ajaran ini
        $placedStudentIdsInYear = StudentClassHistory::where('academic_year_id', $schoolClass->academic_year_id)
            ->where('status', 'active')
            ->pluck('user_id');

        $availableStudents = User::where('role', 'siswa')
            ->where('is_active', true)
            ->whereNotIn('id', $placedStudentIdsInYear)
            ->orderBy('name')
            ->get(['id', 'name', 'identity_number', 'email']);

        // Kelas lain untuk opsi pindah kelas
        $otherClasses = SchoolClass::where('id', '!=', $schoolClass->id)
            ->where('academic_year_id', $schoolClass->academic_year_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $teachers = User::where('role', 'guru')->where('is_active', true)->orderBy('name')->get();
        $academicYears = AcademicYear::orderBy('is_active', 'desc')->get();

        return view('admin.academic.classes.show', compact(
            'schoolClass',
            'activeHistories',
            'pastHistories',
            'availableStudents',
            'otherClasses',
            'teachers',
            'academicYears'
        ));
    }

    public function update(Request $request, SchoolClass $schoolClass)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'level' => 'nullable|string|max:20',
            'department' => 'nullable|string|max:50',
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:active,archived',
        ]);

        $schoolClass->update(array_filter([
            'name' => $validated['name'],
            'academic_year_id' => $validated['academic_year_id'] ?? null,
            'level' => $validated['level'] ?? null,
            'department' => $validated['department'] ?? null,
            'homeroom_teacher_id' => array_key_exists('homeroom_teacher_id', $validated) ? $validated['homeroom_teacher_id'] : null,
            'status' => $validated['status'] ?? null,
        ], fn ($val) => !is_null($val)));

        ActivityLog::record(
            'class.update',
            "Admin " . Auth::user()->name . " memperbarui informasi kelas: {$schoolClass->name}"
        );

        return back()->with('success', "Informasi kelas \"{$schoolClass->name}\" berhasil diperbarui.");
    }

    public function archive(SchoolClass $schoolClass)
    {
        $this->checkAdmin();

        $newStatus = $schoolClass->status === 'archived' ? 'active' : 'archived';
        $schoolClass->update(['status' => $newStatus]);

        ActivityLog::record(
            'class.archive',
            "Admin " . Auth::user()->name . " mengubah status kelas {$schoolClass->name} menjadi {$newStatus}"
        );

        return back()->with('success', "Status kelas \"{$schoolClass->name}\" berhasil diubah menjadi {$newStatus}.");
    }

    public function destroy(SchoolClass $schoolClass)
    {
        $this->checkAdmin();

        if ($schoolClass->studentHistories()->where('status', 'active')->exists()) {
            return back()->with('error', 'Kelas tidak dapat dihapus karena masih memiliki siswa aktif. Pindahkan atau keluarkan siswa terlebih dahulu.');
        }

        $name = $schoolClass->name;
        $schoolClass->delete();

        ActivityLog::record(
            'class.delete',
            "Admin " . Auth::user()->name . " menghapus kelas rombel: {$name}"
        );

        return redirect()->route('admin.academic.classes.index')->with('success', "Kelas \"{$name}\" berhasil dihapus.");
    }

    /**
     * Tambahkan satu atau beberapa siswa ke kelas ini.
     */
    public function addStudents(Request $request, SchoolClass $schoolClass)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:users,id',
        ]);

        $addedCount = 0;
        DB::transaction(function () use ($schoolClass, $validated, &$addedCount) {
            foreach ($validated['student_ids'] as $studentId) {
                $user = User::find($studentId);
                if (!$user || $user->role !== 'siswa') {
                    continue;
                }

                // Cek apakah siswa sudah punya kelas aktif pada tahun ajaran ini
                $existing = StudentClassHistory::where('user_id', $user->id)
                    ->where('academic_year_id', $schoolClass->academic_year_id)
                    ->where('status', 'active')
                    ->first();

                if ($existing) {
                    continue;
                }

                StudentClassHistory::create([
                    'user_id' => $user->id,
                    'school_class_id' => $schoolClass->id,
                    'academic_year_id' => $schoolClass->academic_year_id,
                    'status' => 'active',
                    'assigned_at' => now(),
                    'notes' => 'Penempatan siswa oleh Administrator',
                ]);
                $addedCount++;
            }

            ActivityLog::record(
                'class.add_students',
                "Admin " . Auth::user()->name . " menambahkan {$addedCount} siswa ke kelas {$schoolClass->name}"
            );
        });

        if ($addedCount === 0) {
            return back()->with('error', 'Tidak ada siswa yang ditambahkan (mungkin sudah memiliki penempatan aktif di tahun ajaran ini).');
        }

        return back()->with('success', "Berhasil menambahkan {$addedCount} siswa ke kelas {$schoolClass->name}.");
    }

    /**
     * Keluarkan siswa dari kelas (tanpa menghapus akun users).
     */
    public function removeStudent(Request $request, SchoolClass $schoolClass, User $user)
    {
        $this->checkAdmin();

        $history = StudentClassHistory::where('user_id', $user->id)
            ->where('school_class_id', $schoolClass->id)
            ->where('status', 'active')
            ->first();

        if (!$history) {
            return back()->with('error', 'Siswa tidak ditemukan dalam penempatan aktif kelas ini.');
        }

        $history->update([
            'status' => 'moved',
            'moved_at' => now(),
            'notes' => 'Dikeluarkan dari kelas oleh Administrator: ' . ($request->input('reason') ?? 'Tanpa alasan spesifik'),
        ]);

        ActivityLog::record(
            'class.remove_student',
            "Admin " . Auth::user()->name . " mengeluarkan siswa {$user->name} dari kelas {$schoolClass->name}"
        );

        return back()->with('success', "Siswa {$user->name} berhasil dikeluarkan dari kelas {$schoolClass->name}.");
    }

    /**
     * Pindahkan siswa ke kelas lain (mencatat riwayat perpindahan aman).
     */
    public function moveStudent(Request $request, SchoolClass $schoolClass, User $user)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'target_class_id' => 'required|exists:school_classes,id|different:' . $schoolClass->id,
            'notes' => 'nullable|string|max:255',
        ]);

        $targetClass = SchoolClass::findOrFail($validated['target_class_id']);

        DB::transaction(function () use ($schoolClass, $targetClass, $user, $validated) {
            // 1. Nonaktifkan penempatan kelas lama
            $currentHistory = StudentClassHistory::where('user_id', $user->id)
                ->where('school_class_id', $schoolClass->id)
                ->where('status', 'active')
                ->first();

            $reason = $validated['notes'] ? ": {$validated['notes']}" : "";

            if ($currentHistory) {
                $currentHistory->update([
                    'status' => 'moved',
                    'moved_at' => now(),
                    'notes' => "Dipindahkan ke {$targetClass->name}{$reason}",
                ]);
            }

            // 2. Buat penempatan kelas baru
            StudentClassHistory::create([
                'user_id' => $user->id,
                'school_class_id' => $targetClass->id,
                'academic_year_id' => $targetClass->academic_year_id,
                'status' => 'active',
                'assigned_at' => now(),
                'notes' => "Pindahan dari {$schoolClass->name}{$reason}",
            ]);

            ActivityLog::record(
                'class.move_student',
                "Admin " . Auth::user()->name . " memindahkan siswa {$user->name} dari {$schoolClass->name} ke {$targetClass->name}"
            );
        });

        return back()->with('success', "Siswa {$user->name} berhasil dipindahkan ke kelas {$targetClass->name}.");
    }
}
