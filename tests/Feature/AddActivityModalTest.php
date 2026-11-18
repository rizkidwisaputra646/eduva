<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddActivityModalTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $otherTeacher;
    protected User $student;
    protected Course $course;
    protected Course $otherCourse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'role' => 'guru',
            'identity_number' => '198801012015011001',
            'is_active' => true,
        ]);

        $this->otherTeacher = User::factory()->create([
            'role' => 'guru',
            'identity_number' => '198902022016011002',
            'is_active' => true,
        ]);

        $this->student = User::factory()->create([
            'role' => 'siswa',
            'identity_number' => '202501001',
            'is_active' => true,
        ]);

        $this->course = Course::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Basis Data Terdistribusi',
            'description' => 'Mata kuliah perancangan basis data',
            'invitation_code' => 'BDT101',
            'is_active' => true,
        ]);
        $this->course->students()->attach($this->student->id);

        $this->otherCourse = Course::create([
            'teacher_id' => $this->otherTeacher->id,
            'title' => 'Fisika Komputasi',
            'description' => 'Kelas guru lain',
            'invitation_code' => 'FSK202',
            'is_active' => true,
        ]);
    }

    public function test_modal_displays_only_real_course_meetings_without_dummy_data(): void
    {
        Material::create([
            'course_id' => $this->course->id,
            'topic' => '01. Pengantar dan Konsep Dasar',
            'type' => 'page',
            'title' => 'Silabus Awal',
            'content' => 'Materi awal.',
            'order' => 1,
            'is_visible' => true,
        ]);

        Attendance::create([
            'course_id' => $this->course->id,
            'topic' => 'Pertemuan 02 — Membuat Es Batu',
            'title' => 'Presensi P2',
            'start_time' => now(),
            'end_time' => now()->addHours(2),
            'is_active' => true,
            'is_visible' => true,
        ]);

        // Pertemuan milik kelas lain tidak boleh muncul
        Material::create([
            'course_id' => $this->otherCourse->id,
            'topic' => 'Pertemuan 99 — Kelas Lain Rahasia',
            'type' => 'page',
            'title' => 'Rahasia',
            'content' => 'Konten kelas lain.',
            'order' => 1,
            'is_visible' => true,
        ]);

        $response = $this->actingAs($this->teacher)->get(route('kelas.show', $this->course->id));

        $response->assertStatus(200);
        $response->assertSee('Tambah Aktivitas');
        $response->assertSee('Pilih jenis aktivitas pembelajaran untuk ditambahkan ke pertemuan ini.');
        $response->assertSee('Tuliskan instruksi atau informasi tambahan untuk siswa...');
        $response->assertSee('Buat Sesi Presensi');

        $meetings = $response->viewData('courseMeetings');
        $labels = collect($meetings)->pluck('display_label')->all();
        $this->assertContains('Pertemuan 01 — Pengantar dan Konsep Dasar', $labels);
        $this->assertContains('Pertemuan 02 — Membuat Es Batu', $labels);
        $this->assertCount(2, $meetings);
        $this->assertEquals('Basis Data Terdistribusi', $meetings[0]['course_title']);

        // Dummy lama maupun pertemuan kelas lain tidak boleh ada
        $response->assertDontSee('01. Pengantar Kuliah & Konsep Dasar');
        $response->assertDontSee('02. Pembahasan Materi Lanjutan');
        $response->assertDontSee('Pertemuan 99');
    }

    public function test_empty_course_has_no_dummy_meetings_in_dropdown(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('kelas.show', $this->course->id));

        $response->assertStatus(200);
        $response->assertDontSee('01. Pengantar Kuliah & Konsep Dasar');
        $response->assertDontSee('02. Pembahasan Materi Lanjutan');
        $this->assertEmpty($response->viewData('courseMeetings'));
    }

    public function test_teacher_can_create_attendance_and_material_with_meeting_id_or_topic_and_validates_end_time(): void
    {
        // 1. Gagal jika waktu selesai presensi <= waktu mulai
        $invalidResponse = $this->actingAs($this->teacher)->post(route('attendances.store', $this->course->id), [
            'meeting_id' => 'Pertemuan 01 — Pengantar',
            'title' => 'Presensi Pertemuan 01',
            'description' => 'Isi daftar hadir tepat waktu.',
            'start_time' => '2026-11-11T10:00',
            'end_time' => '2026-11-11T09:00',
        ]);
        $invalidResponse->assertSessionHasErrors('end_time');

        // 2. Berhasil membuat presensi dengan meeting_id dan waktu valid
        $validAttResponse = $this->actingAs($this->teacher)->post(route('attendances.store', $this->course->id), [
            'meeting_id' => 'Pertemuan 01 — Pengantar',
            'title' => 'Presensi Pertemuan 01',
            'description' => 'Isi daftar hadir tepat waktu.',
            'start_time' => '2026-11-11T08:00',
            'end_time' => '2026-11-11T10:00',
        ]);
        $validAttResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendances', [
            'course_id' => $this->course->id,
            'topic' => 'Pertemuan 01 — Pengantar',
            'title' => 'Presensi Pertemuan 01',
        ]);

        // 3. Berhasil membuat materi
        $materialResponse = $this->actingAs($this->teacher)->post(route('materials.store', $this->course->id), [
            'topic' => 'Pertemuan 01 — Pengantar',
            'meeting_id' => 'Pertemuan 01 — Pengantar',
            'type' => 'page',
            'title' => 'Ringkasan Materi',
            'content' => 'Konten pembelajaran pertemuan 1.',
        ]);
        $materialResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('materials', [
            'course_id' => $this->course->id,
            'topic' => 'Pertemuan 01 — Pengantar',
            'title' => 'Ringkasan Materi',
            'type' => 'page',
        ]);
    }

    public function test_unauthorized_student_and_other_teacher_cannot_store_activities(): void
    {
        $this->actingAs($this->student)
            ->post(route('materials.store', $this->course->id), [
                'topic' => 'Pertemuan 01',
                'type' => 'label',
                'title' => 'Label Ilegal',
            ])
            ->assertStatus(403);

        $this->actingAs($this->otherTeacher)
            ->post(route('attendances.store', $this->course->id), [
                'topic' => 'Pertemuan 01',
                'title' => 'Absensi Ilegal',
                'start_time' => '2026-11-11T08:00',
                'end_time' => '2026-11-11T10:00',
            ])
            ->assertStatus(403);
    }

    public function test_teacher_can_delete_entire_meeting_and_unauthorized_users_are_blocked(): void
    {
        $targetTopic = 'Pertemuan 01 — Konsep Dasar';
        $keptTopic = 'Pertemuan 02 — Lanjutan';

        Material::create([
            'course_id' => $this->course->id,
            'topic' => $targetTopic,
            'type' => 'page',
            'title' => 'Materi 1',
            'content' => 'Isi 1',
            'is_visible' => true,
        ]);
        Quiz::create([
            'course_id' => $this->course->id,
            'topic' => $targetTopic,
            'title' => 'Kuis 1',
            'type' => 'quiz',
            'is_visible' => true,
        ]);
        Attendance::create([
            'course_id' => $this->course->id,
            'topic' => $targetTopic,
            'title' => 'Presensi 1',
            'start_time' => now(),
            'end_time' => now()->addHours(2),
            'is_active' => true,
            'is_visible' => true,
        ]);

        // Pertemuan lain di kelas yang sama harus tetap aman
        Material::create([
            'course_id' => $this->course->id,
            'topic' => $keptTopic,
            'type' => 'page',
            'title' => 'Materi 2',
            'content' => 'Isi 2',
            'is_visible' => true,
        ]);

        // Siswa & guru lain dilarang menghapus
        $this->actingAs($this->student)
            ->delete(route('kelas.meetings.destroy', $this->course->id), ['topic' => $targetTopic])
            ->assertStatus(403);

        $this->actingAs($this->otherTeacher)
            ->delete(route('kelas.meetings.destroy', $this->course->id), ['topic' => $targetTopic])
            ->assertStatus(403);

        // Pemilik kelas (guru) berhasil menghapus pertemuan beserta semua aktivitasnya
        $response = $this->actingAs($this->teacher)
            ->delete(route('kelas.meetings.destroy', $this->course->id), ['topic' => $targetTopic]);

        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('materials', ['course_id' => $this->course->id, 'topic' => $targetTopic]);
        $this->assertDatabaseMissing('quizzes', ['course_id' => $this->course->id, 'topic' => $targetTopic]);
        $this->assertDatabaseMissing('attendances', ['course_id' => $this->course->id, 'topic' => $targetTopic]);

        // Pertemuan lain tetap utuh
        $this->assertDatabaseHas('materials', ['course_id' => $this->course->id, 'topic' => $keptTopic, 'title' => 'Materi 2']);
    }
}
