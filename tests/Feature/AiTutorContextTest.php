<?php

namespace Tests\Feature;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AiTutorContextTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private User $otherStudent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'role' => 'guru',
            'identity_number' => '198501012010011001',
            'is_active' => true,
        ]);

        $this->student = User::factory()->create([
            'role' => 'siswa',
            'identity_number' => '202401001',
            'is_active' => true,
        ]);

        $this->otherStudent = User::factory()->create([
            'role' => 'siswa',
            'identity_number' => '202401002',
            'is_active' => true,
        ]);
    }

    public function test_student_with_no_enrolled_courses_does_not_see_other_courses(): void
    {
        $unjoinedCourse = Course::create([
            'title' => 'Kelas Rahasia Fisika Kuantum',
            'description' => 'Tidak diikuti siswa ini',
            'teacher_id' => $this->teacher->id,
            'invitation_code' => 'FIS123',
            'is_active' => true,
        ]);

        Material::create([
            'course_id' => $unjoinedCourse->id,
            'topic' => '01. Pengantar Kuantum',
            'type' => 'page',
            'title' => 'Materi Rahasia Kuantum',
            'content' => 'Konten rahasia fisika kuantum.',
            'is_visible' => true,
        ]);

        $response = $this->actingAs($this->student)->get(route('ai-tutor.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Kelas Rahasia Fisika Kuantum');
        $response->assertDontSee('Materi Rahasia Kuantum');
    }

    public function test_student_with_single_course_auto_selects_it_and_loads_only_visible_meetings(): void
    {
        $course = Course::create([
            'title' => 'Pemrograman Web 2',
            'description' => 'Belajar Laravel',
            'teacher_id' => $this->teacher->id,
            'invitation_code' => 'WEB101',
            'is_active' => true,
        ]);
        $course->students()->attach($this->student->id);

        $otherCourse = Course::create([
            'title' => 'Kelas Tidak Diikuti',
            'description' => 'Bukan milik siswa',
            'teacher_id' => $this->teacher->id,
            'invitation_code' => 'OTH999',
            'is_active' => true,
        ]);

        $visibleMat = Material::create([
            'course_id' => $course->id,
            'topic' => 'Pertemuan 1',
            'type' => 'page',
            'title' => 'Pengenalan Routing Laravel',
            'content' => 'Routing menghubungkan URL dengan controller.',
            'is_visible' => true,
        ]);

        Material::create([
            'course_id' => $course->id,
            'topic' => 'Pertemuan 2',
            'type' => 'page',
            'title' => 'Materi Draft Tersembunyi',
            'content' => 'Belum dipublikasikan.',
            'is_visible' => false,
        ]);

        Material::create([
            'course_id' => $otherCourse->id,
            'topic' => 'Pertemuan 1',
            'type' => 'page',
            'title' => 'Materi Kelas Lain',
            'content' => 'Isi kelas lain.',
            'is_visible' => true,
        ]);

        $response = $this->actingAs($this->student)->get(route('ai-tutor.index', ['new' => 1]));

        $response->assertStatus(200);
        $response->assertViewHas('initialCourseId', $course->id);
        $response->assertViewHas('courses', fn ($courses) => $courses->count() === 1 && $courses->first()->id === $course->id);
        $response->assertViewHas('initialMeetings', function ($meetings) use ($visibleMat) {
            return $meetings->count() === 1 && $meetings->first()['id'] === $visibleMat->id;
        });
        $response->assertDontSee('Kelas Tidak Diikuti');
        $response->assertDontSee('Materi Draft Tersembunyi');
    }

    public function test_student_with_multiple_courses_fetches_meetings_dynamically_per_course_including_empty_course(): void
    {
        $courseA = Course::create([
            'title' => 'Basis Data',
            'description' => 'SQL & Relasi',
            'teacher_id' => $this->teacher->id,
            'invitation_code' => 'DBD001',
            'is_active' => true,
        ]);

        $courseB = Course::create([
            'title' => 'Struktur Data',
            'description' => 'Kelas baru belum ada pertemuan',
            'teacher_id' => $this->teacher->id,
            'invitation_code' => 'STD002',
            'is_active' => true,
        ]);

        $courseA->students()->attach($this->student->id);
        $courseB->students()->attach($this->student->id);

        $matA1 = Material::create([
            'course_id' => $courseA->id,
            'topic' => '01. Normalisasi',
            'type' => 'page',
            'title' => 'Bentuk Normal 1NF - 3NF',
            'content' => 'Penjelasan normalisasi tabel.',
            'is_visible' => true,
        ]);

        // 1. Halaman utama tidak langsung memilih satu kelas jika siswa punya banyak kelas
        $indexRes = $this->actingAs($this->student)->get(route('ai-tutor.index', ['new' => 1]));
        $indexRes->assertStatus(200);
        $indexRes->assertViewHas('initialCourseId', null);
        $indexRes->assertViewHas('courses', fn ($c) => $c->count() === 2);

        // 2. Fetch pertemuan untuk Course A
        $meetingsARes = $this->actingAs($this->student)->getJson(route('ai-tutor.courses.meetings', $courseA->id));
        $meetingsARes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'course' => ['id' => $courseA->id, 'title' => 'Basis Data'],
            ])
            ->assertJsonCount(1, 'meetings')
            ->assertJsonFragment([
                'id' => $matA1->id,
                'display_label' => '01. Normalisasi — Bentuk Normal 1NF - 3NF',
            ]);

        // 3. Ganti ke Course B yang belum memiliki pertemuan (empty state)
        $meetingsBRes = $this->actingAs($this->student)->getJson(route('ai-tutor.courses.meetings', $courseB->id));
        $meetingsBRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'course' => ['id' => $courseB->id, 'title' => 'Struktur Data'],
                'meetings' => [],
            ]);
    }

    public function test_auto_context_from_meeting_page_overrides_old_conversation_and_blocks_tampering(): void
    {
        $courseEnrolled = Course::create([
            'title' => 'Algoritma & Pemrograman',
            'description' => 'Dasar logika',
            'teacher_id' => $this->teacher->id,
            'invitation_code' => 'ALG001',
            'is_active' => true,
        ]);
        $courseEnrolled->students()->attach($this->student->id);

        $courseForeign = Course::create([
            'title' => 'Kecerdasan Buatan Rahasia',
            'description' => 'Bukan kelas siswa',
            'teacher_id' => $this->teacher->id,
            'invitation_code' => 'KBR999',
            'is_active' => true,
        ]);

        $matEnrolled = Material::create([
            'course_id' => $courseEnrolled->id,
            'topic' => 'Pertemuan 2',
            'type' => 'page',
            'title' => 'Perulangan For & While',
            'content' => 'Konsep looping.',
            'is_visible' => true,
        ]);

        $matForeign = Material::create([
            'course_id' => $courseForeign->id,
            'topic' => 'Pertemuan 1',
            'type' => 'page',
            'title' => 'Jaringan Syaraf Tiruan Rahasia',
            'content' => 'Konten rahasia.',
            'is_visible' => true,
        ]);

        // Buat percakapan lama terlebih dahulu
        AiConversation::create([
            'user_id' => $this->student->id,
            'title' => 'Percakapan Lama',
            'study_mode' => 'tanya_jawab',
            'difficulty' => 'menengah',
        ]);

        // A. Buka dari halaman pertemuan yang valid -> langsung aktif konteks kelas & pertemuan tersebut
        $validRes = $this->actingAs($this->student)->get(route('ai-tutor.index', [
            'course_id' => $courseEnrolled->id,
            'meeting_id' => $matEnrolled->id,
        ]));
        $validRes->assertStatus(200);
        $validRes->assertViewHas('isAutoContext', true);
        $validRes->assertViewHas('initialCourseId', $courseEnrolled->id);
        $validRes->assertViewHas('initialMeetingId', $matEnrolled->id);
        $validRes->assertViewHas('activeConversation', null);

        // B. Manipulasi URL mencoba membuka pertemuan dari kelas yang tidak diikuti
        $tamperRes = $this->actingAs($this->student)->get(route('ai-tutor.index', [
            'course_id' => $courseForeign->id,
            'meeting_id' => $matForeign->id,
        ]));
        $tamperRes->assertStatus(200);
        $tamperRes->assertViewHas('isAutoContext', false);
        $tamperRes->assertViewHas('initialCourseId', null);
        $tamperRes->assertViewHas('initialMeetingId', null);
        $tamperRes->assertViewHas('contextError', fn ($err) => !empty($err));
        $tamperRes->assertDontSee('Kecerdasan Buatan Rahasia');
        $tamperRes->assertDontSee('Jaringan Syaraf Tiruan Rahasia');
    }

    public function test_backend_security_blocks_unauthorized_course_or_mismatched_meeting_requests(): void
    {
        $enrolledCourse = Course::create([
            'title' => 'Jaringan Komputer',
            'description' => 'TCP/IP',
            'teacher_id' => $this->teacher->id,
            'invitation_code' => 'JAR001',
            'is_active' => true,
        ]);
        $secondEnrolledCourse = Course::create([
            'title' => 'Sistem Operasi',
            'description' => 'Kernel & Thread',
            'teacher_id' => $this->teacher->id,
            'invitation_code' => 'SOP002',
            'is_active' => true,
        ]);
        $unenrolledCourse = Course::create([
            'title' => 'Keamanan Siber Eksklusif',
            'description' => 'Tidak diikuti',
            'teacher_id' => $this->teacher->id,
            'invitation_code' => 'SEC999',
            'is_active' => true,
        ]);

        $enrolledCourse->students()->attach($this->student->id);
        $secondEnrolledCourse->students()->attach($this->student->id);

        $matSecondCourse = Material::create([
            'course_id' => $secondEnrolledCourse->id,
            'topic' => 'Pertemuan 1',
            'type' => 'page',
            'title' => 'Manajemen Proses',
            'content' => 'Proses dan thread.',
            'is_visible' => true,
        ]);

        $matUnenrolled = Material::create([
            'course_id' => $unenrolledCourse->id,
            'topic' => 'Pertemuan 1',
            'type' => 'page',
            'title' => 'Penetration Testing',
            'content' => 'Materi rahasia.',
            'is_visible' => true,
        ]);

        $hiddenMat = Material::create([
            'course_id' => $enrolledCourse->id,
            'topic' => 'Pertemuan 2',
            'type' => 'page',
            'title' => 'Materi Tersembunyi',
            'content' => 'Draft guru.',
            'is_visible' => false,
        ]);

        // 1. Endpoint pertemuan kelas yang bukan miliknya -> 403
        $this->actingAs($this->student)
            ->getJson(route('ai-tutor.courses.meetings', $unenrolledCourse->id))
            ->assertStatus(403)
            ->assertJson(['success' => false]);

        // 2. Chat ke kelas yang bukan miliknya -> 403
        $this->actingAs($this->student)
            ->postJson(route('ai-tutor.chat'), [
                'message' => 'Jelaskan materi ini',
                'course_id' => $unenrolledCourse->id,
            ])
            ->assertStatus(403)
            ->assertJson(['success' => false]);

        // 3. Chat dengan meeting_id dari kelas lain yang bukan miliknya -> 403
        $this->actingAs($this->student)
            ->postJson(route('ai-tutor.chat'), [
                'message' => 'Jelaskan materi ini',
                'meeting_id' => $matUnenrolled->id,
            ])
            ->assertStatus(403)
            ->assertJson(['success' => false]);

        // 4. Chat dengan course_id != meeting.course_id (meski kedua kelas diikuti) -> 422
        $this->actingAs($this->student)
            ->postJson(route('ai-tutor.chat'), [
                'message' => 'Jelaskan materi ini',
                'course_id' => $enrolledCourse->id,
                'meeting_id' => $matSecondCourse->id,
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        // 5. Chat dengan materi yang masih disembunyikan guru -> 403
        $this->actingAs($this->student)
            ->postJson(route('ai-tutor.chat'), [
                'message' => 'Jelaskan materi tersembunyi',
                'course_id' => $enrolledCourse->id,
                'meeting_id' => $hiddenMat->id,
            ])
            ->assertStatus(403)
            ->assertJson(['success' => false]);

        // 6. Siswa lain tidak bisa menyisipkan pesan ke percakapan milik student pertama -> 403
        $foreignConv = AiConversation::create([
            'user_id' => $this->otherStudent->id,
            'title' => 'Chat Siswa Lain',
        ]);
        $this->actingAs($this->student)
            ->postJson(route('ai-tutor.chat'), [
                'message' => 'Halo',
                'conversation_id' => $foreignConv->id,
            ])
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_ai_chat_sends_only_active_meeting_material_and_handles_missing_text_content(): void
    {
        $course1 = Course::create([
            'title' => 'Pemrograman Web 2',
            'description' => 'Laravel & Blade',
            'teacher_id' => $this->teacher->id,
            'invitation_code' => 'WEB200',
            'is_active' => true,
        ]);
        $course2 = Course::create([
            'title' => 'Matematika Diskrit',
            'description' => 'Graf & Himpunan',
            'teacher_id' => $this->teacher->id,
            'invitation_code' => 'MAT200',
            'is_active' => true,
        ]);

        $course1->students()->attach($this->student->id);
        $course2->students()->attach($this->student->id);

        $activeMaterial = Material::create([
            'course_id' => $course1->id,
            'topic' => 'Pertemuan 3',
            'type' => 'page',
            'title' => 'Eloquent ORM',
            'content' => 'UNIQUE_TOKEN_ELOQUENT: Eloquent memetakan tabel database ke Model PHP.',
            'is_visible' => true,
        ]);

        Material::create([
            'course_id' => $course1->id,
            'topic' => 'Pertemuan 4',
            'type' => 'page',
            'title' => 'Middleware Laravel',
            'content' => 'SECRET_OTHER_MEETING_TOKEN: Middleware memfilter HTTP request.',
            'is_visible' => true,
        ]);

        Material::create([
            'course_id' => $course2->id,
            'topic' => 'Pertemuan 1',
            'type' => 'page',
            'title' => 'Teori Graf',
            'content' => 'SECRET_OTHER_COURSE_TOKEN: Graf terdiri atas simpul dan sisi.',
            'is_visible' => true,
        ]);

        $emptyContentMaterial = Material::create([
            'course_id' => $course1->id,
            'topic' => 'Pertemuan 5',
            'type' => 'file',
            'title' => 'Slide PDF Tanpa Teks',
            'content' => '',
            'file_path' => 'materials/sample.pdf',
            'is_visible' => true,
        ]);

        $capturedPrompts = [];
        $mockGemini = Mockery::mock(GeminiService::class);
        $mockGemini->shouldReceive('chat')
            ->twice()
            ->andReturnUsing(function (string $systemInstruction, array $messages) use (&$capturedPrompts) {
                $capturedPrompts[] = [
                    'system' => $systemInstruction,
                    'messages' => $messages,
                ];
                return [
                    'success' => true,
                    'content' => 'Ini adalah penjelasan sederhana berdasarkan materi aktif.',
                    'model' => 'mock-model',
                    'error' => null,
                ];
            });
        $this->app->instance(GeminiService::class, $mockGemini);

        // 1. Kirim pesan dengan konteks kelas + pertemuan aktif
        $chatRes = $this->actingAs($this->student)->postJson(route('ai-tutor.chat'), [
            'message' => 'Jelaskan konsep Eloquent dengan bahasa sederhana.',
            'course_id' => $course1->id,
            'meeting_id' => $activeMaterial->id,
            'study_mode' => 'penjelasan_sederhana',
            'difficulty' => 'dasar',
        ]);

        $chatRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'course_id' => $course1->id,
                'meeting_id' => $activeMaterial->id,
            ]);

        $convId = $chatRes->json('conversation_id');
        $this->assertDatabaseHas('ai_conversations', [
            'id' => $convId,
            'user_id' => $this->student->id,
            'course_id' => $course1->id,
            'material_id' => $activeMaterial->id,
        ]);

        // Pastikan hanya materi pertemuan aktif yang dikirim ke AI (bukan pertemuan lain / kelas lain)
        $firstSystemPrompt = $capturedPrompts[0]['system'];
        $this->assertStringContainsString('Pemrograman Web 2', $firstSystemPrompt);
        $this->assertStringContainsString('UNIQUE_TOKEN_ELOQUENT', $firstSystemPrompt);
        $this->assertStringNotContainsString('SECRET_OTHER_MEETING_TOKEN', $firstSystemPrompt);
        $this->assertStringNotContainsString('SECRET_OTHER_COURSE_TOKEN', $firstSystemPrompt);

        // 2. Uji materi tanpa teks konten -> instruksi AI melarang mengarang & mewajibkan jujur jika belum tersedia
        $emptyChatRes = $this->actingAs($this->student)->postJson(route('ai-tutor.chat'), [
            'message' => 'Apa isi dokumen PDF tersebut?',
            'course_id' => $course1->id,
            'meeting_id' => $emptyContentMaterial->id,
        ]);

        $emptyChatRes->assertStatus(200)->assertJson(['success' => true]);
        $secondSystemPrompt = $capturedPrompts[1]['system'];
        $this->assertStringContainsString('BELUM TERSEDIA dalam bentuk teks', $secondSystemPrompt);
        $this->assertStringContainsString('JANGAN mengarang isinya', $secondSystemPrompt);
    }

    public function test_teacher_and_admin_cannot_access_ai_tutor(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'identity_number' => '197001011990011001',
            'is_active' => true,
        ]);

        // 1. Guru diarahkan ke dashboard
        $teacherRes = $this->actingAs($this->teacher)->get(route('ai-tutor.index'));
        $teacherRes->assertRedirect(route('dashboard'));
        $teacherRes->assertSessionHas('error');

        // Guru tidak bisa mengirim pesan chat
        $teacherChatRes = $this->actingAs($this->teacher)->postJson(route('ai-tutor.chat'), [
            'message' => 'Halo tutor',
        ]);
        $teacherChatRes->assertStatus(403);

        // Guru tidak bisa mengambil daftar meetings AI
        $teacherMeetingRes = $this->actingAs($this->teacher)->getJson(route('ai-tutor.courses.meetings', 1));
        $teacherMeetingRes->assertStatus(403);

        // 2. Admin diarahkan ke admin dashboard
        $adminRes = $this->actingAs($admin)->get(route('ai-tutor.index'));
        $adminRes->assertRedirect(route('admin.dashboard'));

        $adminChatRes = $this->actingAs($admin)->postJson(route('ai-tutor.chat'), [
            'message' => 'Halo tutor',
        ]);
        $adminChatRes->assertStatus(403);
    }
}

