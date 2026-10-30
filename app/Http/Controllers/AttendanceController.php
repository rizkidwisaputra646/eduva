<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function store(Request $request, Course $course)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        $course->attendances()->create([
            'topic' => $request->topic,
            'title' => $request->title,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'is_active' => true,
            'is_visible' => true,
        ]);

        return back()->with('success', 'Sesi absensi berhasil dibuat!');
    }

    public function update(Request $request, Attendance $attendance)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        $attendance->update([
            'topic' => $request->topic,
            'title' => $request->title,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return back()->with('success', 'Sesi absensi berhasil diperbarui!');
    }

    public function toggleStatus(Attendance $attendance)
    {
        $attendance->update([
            'is_active' => !$attendance->is_active,
        ]);

        $status = $attendance->is_active ? 'dibuka' : 'ditutup';
        return back()->with('success', 'Sesi absensi berhasil ' . $status . '!');
    }

    public function toggleVisibility(Attendance $attendance)
    {
        $attendance->update([
            'is_visible' => !$attendance->is_visible,
        ]);

        return back()->with('success', 'Status visibilitas absensi berhasil diubah!');
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();
        return back()->with('success', 'Sesi absensi berhasil dihapus!');
    }

    public function submit(Request $request, Attendance $attendance)
    {
        $user = Auth::user();

        // Check if session is active and within time range
        $now = now();
        if (!$attendance->is_active || $now < $attendance->start_time || $now > $attendance->end_time) {
            return back()->with('error', 'Sesi presensi sedang tidak dibuka atau sudah berakhir.');
        }

        $request->validate([
            'status' => 'required|in:hadir,izin,sakit',
            'note' => 'nullable|string|max:255',
        ]);

        AttendanceLog::updateOrCreate(
            ['attendance_id' => $attendance->id, 'user_id' => $user->id],
            [
                'status' => $request->status,
                'note' => $request->note,
            ]
        );

        return back()->with('success', 'Presensi Anda berhasil dicatat!');
    }

    public function report(Attendance $attendance)
    {
        $attendance->load(['course.students', 'logs.user']);
        return view('attendance.report', compact('attendance'));
    }
}
