<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $data = [];

        if ($user->role === 'guru') {
            $data['total_courses'] = Course::where('teacher_id', $user->id)->count();
            $data['total_materials'] = Material::whereHas('course', function($q) use ($user) {
                $q->where('teacher_id', $user->id);
            })->count();
            
            // Total unique students enrolled in teacher's courses
            $data['total_students'] = User::whereHas('enrolledCourses', function($q) use ($user) {
                $q->where('teacher_id', $user->id);
            })->count(); 
            
            // Recently added materials by this guru
            $data['recent_materials'] = Material::whereHas('course', function($q) use ($user) {
                $q->where('teacher_id', $user->id);
            })->latest()->take(5)->get();
        }

        return view('dashboard', $data);
    }
}
