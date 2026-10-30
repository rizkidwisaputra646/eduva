<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function store(\Illuminate\Http\Request $request, \App\Models\Course $course)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $course->announcements()->create([
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'content' => $request->content,
        ]);

        return back()->with('success', 'Pengumuman berhasil diposting!');
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Announcement $announcement)
    {
        if (\Illuminate\Support\Facades\Auth::user()->role !== 'admin' && $announcement->user_id !== \Illuminate\Support\Facades\Auth::id()) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah pengumuman ini.');
        }

        $request->validate([
            'content' => 'required|string',
        ]);

        $announcement->update([
            'content' => $request->content,
        ]);

        return back()->with('success', 'Pengumuman berhasil diperbarui!');
    }

    public function destroy(\App\Models\Announcement $announcement)
    {
        if (\Illuminate\Support\Facades\Auth::user()->role !== 'admin' && $announcement->user_id !== \Illuminate\Support\Facades\Auth::id()) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus pengumuman ini.');
        }

        $announcement->delete();

        return back()->with('success', 'Pengumuman berhasil dihapus!');
    }
}
