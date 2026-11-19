<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Course;
use App\Models\Meeting;
use App\Models\Quiz;
use App\Models\SchoolClass;
use App\Models\StudentClassHistory;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AcademicManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher;
    private User $student;
    private AcademicYear $academicYear;
    private SchoolClass $schoolClass;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

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

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        $this->schoolClass = SchoolClass::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => '12 IPA A',
            'level' => '12',
            'department' => 'MIPA',
            'homeroom_teacher_id' => $this->teacher->id,
            'status' => 'active',
        ]);

        $this->course = Course::create([
            'teacher_id' => $this->teacher->id,
            'school_class_id' => $this->schoolClass->id,
            'academic_year_id' => $this->academicYear->id,
            'title' => 'Fisika Kuantum XII',
            'description' => 'Mata pelajaran fisika tingkat lanjut',
            'invitation_code' => 'FIS12A',
            'color' => '#438FCE',
            'status' => 'active',
        ]);

        // Place student into class
        StudentClassHistory::create([
            'user_id' => $this->student->id,
            'school_class_id' => $this->schoolClass->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        // Enroll student in course
        $this->course->students()->attach($this->student->id);
    }

    public function test_admin_can_manage_academic_years(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.academic.years.index'));
        $response->assertStatus(200);
        $response->assertSee('2026/2027');

        // Store new academic year
        $createRes = $this->actingAs($this->admin)->post(route('admin.academic.years.store'), [
            'name' => '2027/2028',
            'semester' => 'genap',
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
        ]);
        $createRes->assertRedirect();
        $this->assertDatabaseHas('academic_years', ['name' => '2027/2028', 'semester' => 'Genap']);

        $newYear = AcademicYear::where('name', '2027/2028')->first();

        // Set active
        $this->actingAs($this->admin)->patch(route('admin.academic.years.set-active', $newYear->id));
        $this->assertTrue($newYear->fresh()->is_active);
        $this->assertFalse($this->academicYear->fresh()->is_active);

        // Archive
        $this->actingAs($this->admin)->patch(route('admin.academic.years.archive', $newYear->id));
        $this->assertEquals('archived', $newYear->fresh()->status);
    }

    public function test_admin_can_manage_school_classes(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.academic.classes.index'));
        $response->assertStatus(200);
        $response->assertSee('12 IPA A');

        // Create new class
        $createRes = $this->actingAs($this->admin)->post(route('admin.academic.classes.store'), [
            'academic_year_id' => $this->academicYear->id,
            'name' => '10 IPS B',
            'level' => '10',
            'department' => 'IPS',
            'homeroom_teacher_id' => $this->teacher->id,
        ]);
        $createRes->assertRedirect();
        $this->assertDatabaseHas('school_classes', ['name' => '10 IPS B']);

        $newClass = SchoolClass::where('name', '10 IPS B')->first();

        // Detail class
        $detailRes = $this->actingAs($this->admin)->get(route('admin.academic.classes.show', $this->schoolClass->id));
        $detailRes->assertStatus(200);
        $detailRes->assertSee($this->student->name);
        $detailRes->assertSee($this->student->nim);

        // Update class
        $this->actingAs($this->admin)->put(route('admin.academic.classes.update', $newClass->id), [
            'name' => '10 IPS Unggulan',
            'level' => '10',
            'department' => 'IPS',
        ]);
        $this->assertDatabaseHas('school_classes', ['name' => '10 IPS Unggulan']);
    }

    public function test_admin_can_assign_move_and_remove_students_with_history(): void
    {
        $targetClass = SchoolClass::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => '12 IPA B',
            'level' => '12',
            'department' => 'MIPA',
            'status' => 'active',
        ]);

        $newStudent = User::factory()->create([
            'role' => 'siswa',
            'identity_number' => '202401099',
            'is_active' => true,
        ]);

        // 1. Assign student to class
        $assignRes = $this->actingAs($this->admin)->post(route('admin.academic.classes.students.add', $targetClass->id), [
            'student_ids' => [$newStudent->id],
        ]);
        $assignRes->assertRedirect();
        $this->assertDatabaseHas('student_class_histories', [
            'user_id' => $newStudent->id,
            'school_class_id' => $targetClass->id,
            'status' => 'active',
        ]);

        // 2. Move student to another class
        $moveRes = $this->actingAs($this->admin)->post(route('admin.academic.classes.students.move', [
            'schoolClass' => $this->schoolClass->id,
            'user' => $this->student->id,
        ]), [
            'target_class_id' => $targetClass->id,
            'notes' => 'Pindah peminatan kelas',
        ]);
        $moveRes->assertRedirect();

        // Old history should be 'moved' with notes
        $this->assertDatabaseHas('student_class_histories', [
            'user_id' => $this->student->id,
            'school_class_id' => $this->schoolClass->id,
            'status' => 'moved',
        ]);

        // New history should be 'active'
        $this->assertDatabaseHas('student_class_histories', [
            'user_id' => $this->student->id,
            'school_class_id' => $targetClass->id,
            'status' => 'active',
        ]);

        // 3. Remove student from target class
        $removeRes = $this->actingAs($this->admin)->delete(route('admin.academic.classes.students.remove', [
            'schoolClass' => $targetClass->id,
            'user' => $newStudent->id,
        ]));
        $removeRes->assertRedirect();

        // Account is still intact in users table!
        $this->assertDatabaseHas('users', ['id' => $newStudent->id]);
        // But history is marked moved/inactive
        $this->assertDatabaseHas('student_class_histories', [
            'user_id' => $newStudent->id,
            'school_class_id' => $targetClass->id,
            'status' => 'moved',
        ]);
    }

    public function test_admin_monitoring_and_student_placement_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.academic.monitoring.index'));
        $response->assertStatus(200);
        $response->assertSee('Monitoring Akademik');

        $studentsRes = $this->actingAs($this->admin)->get(route('admin.academic.students.index'));
        $studentsRes->assertStatus(200);
        $studentsRes->assertSee($this->student->name);
    }

    public function test_teacher_attendance_integrated_flow(): void
    {
        // 1. Attendance index
        $indexRes = $this->actingAs($this->teacher)->get(route('teacher.academic.attendance.index'));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('Pengelolaan Absensi Siswa');

        // 2. Attendance create form
        $createRes = $this->actingAs($this->teacher)->get(route('teacher.academic.attendance.create', [
            'course_id' => $this->course->id,
        ]));
        $createRes->assertStatus(200);
        $createRes->assertSee('Buka Sesi Presensi Baru');

        // 3. Store attendance session & student logs
        $storeRes = $this->actingAs($this->teacher)->post(route('teacher.academic.attendance.store'), [
            'course_id' => $this->course->id,
            'topic' => 'Pertemuan 01',
            'title' => 'Pengantar Kuantum',
            'start_time' => now()->startOfDay()->addHours(8)->toDateTimeString(),
            'end_time' => now()->startOfDay()->addHours(10)->toDateTimeString(),
            'auto_populate' => 1,
            'initial_status' => 'hadir',
        ]);
        $storeRes->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'course_id' => $this->course->id,
            'title' => 'Pengantar Kuantum',
        ]);

        $attendance = Attendance::where('course_id', $this->course->id)->first();
        $this->assertDatabaseHas('attendance_logs', [
            'attendance_id' => $attendance->id,
            'user_id' => $this->student->id,
            'status' => 'hadir',
        ]);

        // 4. Edit & Update attendance
        $editRes = $this->actingAs($this->teacher)->get(route('teacher.academic.attendance.edit', $attendance->id));
        $editRes->assertStatus(200);
        $editRes->assertSee($this->student->name);

        $updateRes = $this->actingAs($this->teacher)->put(route('teacher.academic.attendance.update', $attendance->id), [
            'attendances' => [
                $this->student->id => [
                    'status' => 'izin',
                    'note' => 'Izin keluarga',
                ],
            ],
        ]);
        $updateRes->assertRedirect();
        $this->assertDatabaseHas('attendance_logs', [
            'attendance_id' => $attendance->id,
            'user_id' => $this->student->id,
            'status' => 'izin',
        ]);

        // 5. Attendance recap matrix
        $recapRes = $this->actingAs($this->teacher)->get(route('teacher.academic.attendance.recap', [
            'course_id' => $this->course->id,
        ]));
        $recapRes->assertStatus(200);
        $recapRes->assertSee('Rekapitulasi Kehadiran Siswa');
        $recapRes->assertSee($this->student->name);

        // 6. Attendance export XLSX
        $exportXlsx = $this->actingAs($this->teacher)->get(route('teacher.academic.attendance.export', [
            'course_id' => $this->course->id,
            'format' => 'xlsx',
        ]));
        $exportXlsx->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $exportXlsx->headers->get('Content-Type') ?? '');

        // 7. Attendance print view
        $exportPrint = $this->actingAs($this->teacher)->get(route('teacher.academic.attendance.export', [
            'course_id' => $this->course->id,
            'format' => 'print',
        ]));
        $exportPrint->assertStatus(200);
        $exportPrint->assertSee('REKAPITULASI PRESENSI KEHADIRAN SISWA');
    }

    public function test_teacher_grade_recap_and_weight_calculation(): void
    {
        // Create 1 assignment quiz and 1 exam quiz
        $assignment = Quiz::create([
            'course_id' => $this->course->id,
            'title' => 'Tugas 1 - Gerak Foton',
            'type' => 'assignment',
            'due_date' => now()->addDays(2),
            'published_at' => now(),
        ]);

        $exam = Quiz::create([
            'course_id' => $this->course->id,
            'title' => 'UTS Fisika Kuantum',
            'type' => 'quiz',
            'published_at' => now(),
        ]);

        // Student submits assignment (score: 90) and exam (score: 80)
        Submission::create([
            'quiz_id' => $assignment->id,
            'user_id' => $this->student->id,
            'score' => 90,
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $examSubmission = Submission::create([
            'quiz_id' => $exam->id,
            'user_id' => $this->student->id,
            'score' => 80,
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        // 1. Grade recap index (default weight 40% assignment + 60% exam = 0.4*90 + 0.6*80 = 36 + 48 = 84 -> Predikat B)
        $recapRes = $this->actingAs($this->teacher)->get(route('teacher.academic.grades.index', [
            'course_id' => $this->course->id,
            'weight_assignment' => 40,
            'weight_exam' => 60,
        ]));
        $recapRes->assertStatus(200);
        $recapRes->assertSee('84.0');
        $recapRes->assertSee($this->student->name);

        // 2. Teacher updates score directly
        $updateScoreRes = $this->actingAs($this->teacher)->postJson(route('teacher.academic.grades.update-score'), [
            'submission_id' => $examSubmission->id,
            'score' => 95,
            'feedback' => 'Penjelasan bagian relativitas sangat jelas',
        ]);
        $updateScoreRes->assertJson(['success' => true]);
        $this->assertEquals(95, $examSubmission->fresh()->score);
        $this->assertEquals('Penjelasan bagian relativitas sangat jelas', $examSubmission->fresh()->feedback);

        // 3. Export XLSX
        $exportXlsx = $this->actingAs($this->teacher)->get(route('teacher.academic.grades.export', [
            'course_id' => $this->course->id,
            'format' => 'xlsx',
        ]));
        $exportXlsx->assertStatus(200);

        // 4. Print preview
        $exportPrint = $this->actingAs($this->teacher)->get(route('teacher.academic.grades.export', [
            'course_id' => $this->course->id,
            'format' => 'print',
        ]));
        $exportPrint->assertStatus(200);
        $exportPrint->assertSee('DAFTAR REKAPITULASI NILAI AKADEMIK SISWA');
    }

    public function test_teacher_assignment_and_exam_recaps(): void
    {
        $assignment = Quiz::create([
            'course_id' => $this->course->id,
            'title' => 'Tugas Mandiri 1',
            'type' => 'assignment',
            'deadline' => now()->addDays(2),
        ]);

        Submission::create([
            'quiz_id' => $assignment->id,
            'user_id' => $this->student->id,
            'score' => 85,
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        // Assignment recap
        $assignRes = $this->actingAs($this->teacher)->get(route('teacher.academic.assignments.index', [
            'course_id' => $this->course->id,
        ]));
        $assignRes->assertStatus(200);
        $assignRes->assertSee('Rekapitulasi Tugas Pembelajaran');
        $assignRes->assertSee('Tugas Mandiri 1');

        // Exam recap
        $exam = Quiz::create([
            'course_id' => $this->course->id,
            'title' => 'Ujian Akhir Semester',
            'type' => 'quiz',
            'deadline' => now()->addDays(3),
        ]);

        Submission::create([
            'quiz_id' => $exam->id,
            'user_id' => $this->student->id,
            'score' => 92,
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $examRes = $this->actingAs($this->teacher)->get(route('teacher.academic.exams.index', [
            'course_id' => $this->course->id,
            'quiz_id' => $exam->id,
        ]));
        $examRes->assertStatus(200);
        $examRes->assertSee('Rekapitulasi Ujian');
        $examRes->assertSee('92');
    }

    public function test_teacher_academic_student_360_profile_report(): void
    {
        $reportRes = $this->actingAs($this->teacher)->get(route('teacher.academic.reports.index', [
            'course_id' => $this->course->id,
        ]));
        $reportRes->assertStatus(200);
        $reportRes->assertSee('Laporan Akademik Siswa');
        $reportRes->assertSee($this->student->name);

        // Student 360 profile detail
        $detailRes = $this->actingAs($this->teacher)->get(route('teacher.academic.reports.student', [
            'user' => $this->student->id,
            'course_id' => $this->course->id,
        ]));
        $detailRes->assertStatus(200);
        $detailRes->assertSee('Laporan Perkembangan Akademik Siswa');
        $detailRes->assertSee($this->student->nim);
    }

    public function test_rbac_security_restrictions_for_academic_modules(): void
    {
        // 1. Siswa cannot access admin academic routes (403)
        $res1 = $this->actingAs($this->student)->get(route('admin.academic.classes.index'));
        $res1->assertStatus(403);

        $res2 = $this->actingAs($this->student)->get(route('admin.academic.years.index'));
        $res2->assertStatus(403);

        // 2. Siswa cannot access teacher academic routes (403)
        $res3 = $this->actingAs($this->student)->get(route('teacher.academic.attendance.index'));
        $res3->assertStatus(403);

        $res4 = $this->actingAs($this->student)->get(route('teacher.academic.grades.index'));
        $res4->assertStatus(403);

        // 3. Guru cannot access admin academic routes (403)
        $res5 = $this->actingAs($this->teacher)->get(route('admin.academic.classes.index'));
        $res5->assertStatus(403);

        // 4. Admin accessing /kelas/{course} is redirected to /admin/courses/{course}
        $res6 = $this->actingAs($this->admin)->get(route('kelas.show', $this->course->id));
        $res6->assertRedirect(route('admin.courses.show', $this->course->id));
    }
}
