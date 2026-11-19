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

class AdminStudentPlacementController extends Controller
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

        $academicYears = AcademicYear::orderBy('is_active', 'desc')->orderBy('name', 'desc')->get();
        $activeYear = AcademicYear::getActive();

        $selectedYearId = $request->query('academic_year_id', $activeYear?->id);

        $query = User::where('role', 'siswa')
            ->with([
                'activeClassHistory' => function ($q) use ($selectedYearId) {
                    if ($selectedYearId) {
                        $q->where('academic_year_id', $selectedYearId);
                    }
                    $q->with('schoolClass');
                },
                'classHistories.schoolClass',
                'classHistories.academicYear',
            ]);

        // Filter Penempatan (Placed vs Unplaced)
        if ($request->query('placement_status') === 'placed') {
            $query->whereHas('classHistories', function ($q) use ($selectedYearId) {
                $q->where('status', 'active');
                if ($selectedYearId) {
                    $q->where('academic_year_id', $selectedYearId);
                }
            });
        } elseif ($request->query('placement_status') === 'unplaced') {
            $query->whereDoesntHave('classHistories', function ($q) use ($selectedYearId) {
                $q->where('status', 'active');
                if ($selectedYearId) {
                    $q->where('academic_year_id', $selectedYearId);
                }
            });
        }

        // Filter Kelas Spesifik
        if ($request->filled('class_id')) {
            $classId = $request->query('class_id');
            $query->whereHas('classHistories', function ($q) use ($classId, $selectedYearId) {
                $q->where('school_class_id', $classId)->where('status', 'active');
                if ($selectedYearId) {
                    $q->where('academic_year_id', $selectedYearId);
                }
            });
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('identity_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('name')->paginate(15)->withQueryString();

        $classes = SchoolClass::where('status', 'active')
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->orderBy('name')
            ->get();

        $counts = [
            'total_students' => User::where('role', 'siswa')->count(),
            'placed' => StudentClassHistory::where('status', 'active')
                ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
                ->distinct('user_id')->count('user_id'),
            'unplaced' => User::where('role', 'siswa')->count() - StudentClassHistory::where('status', 'active')
                ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
                ->distinct('user_id')->count('user_id'),
        ];

        return view('admin.academic.students.index', compact('students', 'academicYears', 'selectedYearId', 'classes', 'counts'));
    }
}
