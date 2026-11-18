<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseAccordionStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_outline_renders_single_toggle_button_and_scoped_accordion_config(): void
    {
        $teacher = User::factory()->create([
            'role' => 'guru',
            'identity_number' => '198501012010011001',
            'is_active' => true,
        ]);

        $student = User::factory()->create([
            'role' => 'siswa',
            'identity_number' => '202401001',
            'is_active' => true,
        ]);

        $course = Course::create([
            'title' => 'Arsitektur Komputer',
            'description' => 'CPU, Memori, Bus',
            'teacher_id' => $teacher->id,
            'invitation_code' => 'ARK101',
            'is_active' => true,
        ]);
        $course->students()->attach($student->id);

        Material::create([
            'course_id' => $course->id,
            'topic' => 'Pertemuan 1',
            'type' => 'page',
            'title' => 'Pengenalan CPU',
            'content' => 'Materi CPU.',
            'order' => 1,
            'is_visible' => true,
        ]);

        Material::create([
            'course_id' => $course->id,
            'topic' => 'Pertemuan 2',
            'type' => 'page',
            'title' => 'Hirarki Memori',
            'content' => 'Materi Memori.',
            'order' => 2,
            'is_visible' => true,
        ]);

        $response = $this->actingAs($student)->get(route('kelas.show', $course->id));

        $response->assertStatus(200);
        // Pastikan hanya ada satu tombol toggle all (bukan dua tombol terpisah)
        $response->assertSee('@click="toggleAll()"', false);
        $response->assertDontSee('toggle-all-topics', false);

        // Pastikan konfigurasi scope akun + kelas + ID pertemuan stabil ter-render
        $response->assertSee('topicAccordionManager(', false);
        $response->assertSee('userId: ' . $student->id, false);
        $response->assertSee('courseId: ' . $course->id, false);
        $response->assertSee('data-topic-id="Pertemuan 1"', false);
        $response->assertSee('data-topic-id="Pertemuan 2"', false);
    }
}

