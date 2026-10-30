<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function store(Request $request, Course $course)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'type' => 'required|in:file,page,video,label',
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'video_url' => 'nullable|string|max:500',
            'document' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,txt,zip,rar|max:15360', // max 15MB
        ]);

        $filePath = null;
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('materials', $filename, 'public');
        }

        $course->materials()->create([
            'topic' => $request->topic,
            'type' => $request->type,
            'title' => $request->title,
            'content' => $request->content ?? '',
            'file_path' => $filePath,
            'video_url' => $request->video_url,
            'is_visible' => true,
        ]);

        return back()->with('success', 'Aktivitas pembelajaran berhasil ditambahkan!');
    }

    public function update(Request $request, \App\Models\Material $material)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'type' => 'nullable|in:file,page,video,label',
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'video_url' => 'nullable|string|max:500',
            'document' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,txt,zip,rar|max:15360',
        ]);

        $filePath = $material->file_path;
        if ($request->hasFile('document')) {
            // Delete old file if exists
            if ($filePath && \Illuminate\Support\Facades\Storage::disk('public')->exists($filePath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($filePath);
            }
            
            $file = $request->file('document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('materials', $filename, 'public');
        }

        $material->update([
            'topic' => $request->topic,
            'type' => $request->type ?? $material->type,
            'title' => $request->title,
            'content' => $request->content ?? '',
            'file_path' => $filePath,
            'video_url' => $request->video_url ?? $material->video_url,
        ]);

        return back()->with('success', 'Aktivitas berhasil diperbarui!');
    }

    public function toggleVisibility(\App\Models\Material $material)
    {
        $material->update([
            'is_visible' => !$material->is_visible,
        ]);

        return back()->with('success', 'Status visibilitas materi berhasil diubah!');
    }

    public function destroy(\App\Models\Material $material)
    {
        if ($material->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($material->file_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($material->file_path);
        }
        
        $material->delete();
        return back()->with('success', 'Materi berhasil dihapus!');
    }
}
