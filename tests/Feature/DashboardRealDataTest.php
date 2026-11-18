<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRealDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_dashboard_shows_real_metrics_and_ungraded_queue()
    {
        $teacher = User::factory()->create(['role' => 'guru']);
        $student = User::factory()->create(['role' => 'siswa']);

        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Biologi Sel',
            'description' => 'Mempelajari organel sel',
            'invitation_code' => 'BIO101',
        ]);
        $student->enrolledCourses()->attach($course->id);

        $quiz = Quiz::create([
            'course_id' => $course->id,
            'topic' => 'Pertemuan 01',
            'title' => 'Ujian Struktur Sel',
            'type' => 'quiz',
        ]);

        // Student submits but ungraded yet
        Submission::create([
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => null,
        ]);

        $response = $this->actingAs($teacher)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Biologi Sel');
        $response->assertSee('Pengumpulan Siswa Menunggu Penilaian');
        $response->assertSee($student->name);
        $response->assertSee('Buka Penilaian');
    }

    public function test_student_dashboard_shows_real_pending_tasks_and_enrolled_courses()
    {
        $teacher = User::factory()->create(['role' => 'guru']);
        $student = User::factory()->create(['role' => 'siswa']);

        $course = Course::create([
            'teacher_id' => $teacher->id,
            'title' => 'Fisika Kuantum',
            'description' => 'Materi teori kuantum',
            'invitation_code' => 'FIS202',
        ]);
        $student->enrolledCourses()->attach($course->id);

        $quiz = Quiz::create([
            'course_id' => $course->id,
            'topic' => 'Pertemuan 01',
            'title' => 'Tugas Efek Fotolistrik',
            'type' => 'assignment',
            'is_visible' => true,
        ]);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Fisika Kuantum');
        $response->assertSee('Tugas Efek Fotolistrik');
        $response->assertSee('Kumpulkan Tugas');
    }
}

