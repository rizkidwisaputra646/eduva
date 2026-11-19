<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminAcademicYearController extends Controller
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

        $query = AcademicYear::withCount(['classes', 'studentHistories']);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('semester', 'like', "%{$search}%");
            });
        }

        $years = $query->orderBy('is_active', 'desc')
            ->orderBy('name', 'desc')
            ->paginate(10)
            ->withQueryString();

        $activeYear = AcademicYear::getActive();

        return view('admin.academic.years.index', compact('years', 'activeYear'));
    }

    public function store(Request $request)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'semester' => 'required|string|in:Ganjil,Genap,ganjil,genap',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
        ]);

        $isActive = $request->boolean('is_active');
        $semester = ucfirst(strtolower($validated['semester']));

        DB::transaction(function () use ($validated, $isActive, $semester) {
            if ($isActive) {
                AcademicYear::query()->update(['is_active' => false]);
            }

            $year = AcademicYear::create([
                'name' => $validated['name'],
                'semester' => $semester,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'is_active' => $isActive,
                'status' => 'active',
            ]);

            ActivityLog::record(
                'academic_year.create',
                "Admin " . Auth::user()->name . " membuat tahun ajaran: {$year->name} ({$year->semester})"
            );
        });

        return back()->with('success', 'Tahun ajaran baru berhasil ditambahkan.');
    }

    public function update(Request $request, AcademicYear $year)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'semester' => 'nullable|string|in:Ganjil,Genap,ganjil,genap',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:active,archived,upcoming',
        ]);

        if (isset($validated['semester'])) {
            $validated['semester'] = ucfirst(strtolower($validated['semester']));
        }

        $year->update($validated);

        ActivityLog::record(
            'academic_year.update',
            "Admin " . Auth::user()->name . " memperbarui tahun ajaran: {$year->name} ({$year->semester})"
        );

        return back()->with('success', 'Data tahun ajaran berhasil diperbarui.');
    }

    public function setActive(AcademicYear $year)
    {
        $this->checkAdmin();

        DB::transaction(function () use ($year) {
            AcademicYear::query()->update(['is_active' => false]);
            $year->update([
                'is_active' => true,
                'status' => 'active',
            ]);

            ActivityLog::record(
                'academic_year.set_active',
                "Admin " . Auth::user()->name . " mengaktifkan tahun ajaran: {$year->name} ({$year->semester})"
            );
        });

        return back()->with('success', "Tahun ajaran {$year->name} ({$year->semester}) telah ditetapkan sebagai tahun ajaran aktif.");
    }

    public function archive(AcademicYear $year)
    {
        $this->checkAdmin();

        $newStatus = $year->status === 'archived' ? 'active' : 'archived';
        if ($newStatus === 'archived' && $year->is_active) {
            $year->is_active = false;
        }

        $year->status = $newStatus;
        $year->save();

        ActivityLog::record(
            'academic_year.archive',
            "Admin " . Auth::user()->name . " mengubah status tahun ajaran {$year->name} menjadi {$newStatus}"
        );

        return back()->with('success', 'Status arsip tahun ajaran berhasil diubah.');
    }

    public function destroy(AcademicYear $year)
    {
        $this->checkAdmin();

        if ($year->is_active) {
            return back()->with('error', 'Tahun ajaran yang sedang aktif tidak dapat dihapus.');
        }

        if ($year->classes()->exists() || $year->studentHistories()->exists()) {
            return back()->with('error', 'Tahun ajaran memiliki relasi kelas atau riwayat siswa. Gunakan opsi Arsipkan untuk menjaga integritas data.');
        }

        $name = $year->name . ' (' . $year->semester . ')';
        $year->delete();

        ActivityLog::record(
            'academic_year.delete',
            "Admin " . Auth::user()->name . " menghapus tahun ajaran: {$name}"
        );

        return back()->with('success', "Tahun ajaran {$name} berhasil dihapus.");
    }
}
