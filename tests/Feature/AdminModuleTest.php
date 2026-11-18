<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'identity_number' => 'admin',
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

        SystemSetting::set('app_name', 'LMS Eduva');
        SystemSetting::set('default_password', 'eduva123@');
    }

    public function test_admin_dashboard_metrics_and_non_admin_rejection(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Dashboard Administrator');

        // Admin sidebar tidak boleh menampilkan fitur siswa seperti 'AI Tutor' atau 'Daftar Kelas'
        $response->assertDontSee('Daftar Kelas');
        $response->assertDontSee('Kelas Saya');

        // Siswa dan Guru tidak boleh mengakses admin dashboard
        $studentRes = $this->actingAs($this->student)->get(route('admin.dashboard'));
        $studentRes->assertStatus(403);

        $teacherRes = $this->actingAs($this->teacher)->get(route('admin.dashboard'));
        $teacherRes->assertStatus(403);
    }

    public function test_admin_is_redirected_away_from_student_only_routes(): void
    {
        // Admin mengunjungi /ai-tutor dialihkan ke dashboard admin
        $aiRes = $this->actingAs($this->admin)->get(route('ai-tutor.index'));
        $aiRes->assertRedirect(route('admin.dashboard'));

        // Admin mengunjungi /kelas dialihkan ke monitoring kelas
        $classRes = $this->actingAs($this->admin)->get(route('kelas.index'));
        $classRes->assertRedirect(route('admin.courses.index'));
    }

    public function test_user_management_crud_and_status_toggle(): void
    {
        // 1. Tambah Pengguna Baru
        $storeRes = $this->actingAs($this->admin)->post(route('admin.store'), [
            'name' => 'Doni Siregar',
            'identity_number' => '202401999',
            'role' => 'siswa',
            'email' => 'doni@eduva.test',
        ]);
        $storeRes->assertRedirect(route('admin.users.index'));
        $storeRes->assertSessionHas('success');

        $newUser = User::where('identity_number', '202401999')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->is_active);
        $this->assertTrue($newUser->must_change_password);
        $this->assertTrue(Hash::check('eduva123@', $newUser->password));

        // 2. Edit Pengguna
        $updateRes = $this->actingAs($this->admin)->put(route('admin.users.update', $newUser->id), [
            'name' => 'Doni Siregar Updated',
            'identity_number' => '202401999',
            'role' => 'siswa',
            'email' => 'doni.updated@eduva.test',
            'is_active' => 1,
        ]);
        $updateRes->assertSessionHas('success');
        $newUser->refresh();
        $this->assertEquals('Doni Siregar Updated', $newUser->name);

        // 3. Nonaktifkan Akun
        $toggleRes = $this->actingAs($this->admin)->patch(route('admin.users.toggle-status', $newUser->id));
        $toggleRes->assertSessionHas('success');
        $newUser->refresh();
        $this->assertFalse($newUser->is_active);

        // 4. Pengguna yang dinonaktifkan tidak dapat login
        $this->post('/logout');
        $loginRes = $this->post('/login', [
            'identity_number' => '202401999',
            'password' => 'eduva123@',
        ]);
        $loginRes->assertSessionHasErrors('identity_number');
        $this->assertGuest();

        // 5. Aktifkan kembali akun
        $this->actingAs($this->admin)->patch(route('admin.users.toggle-status', $newUser->id));
        $newUser->refresh();
        $this->assertTrue($newUser->is_active);

        // 6. Login berhasil setelah diaktifkan
        $this->post('/logout');
        $loginResSuccess = $this->post('/login', [
            'identity_number' => '202401999',
            'password' => 'eduva123@',
        ]);
        $this->assertAuthenticatedAs($newUser);
    }

    public function test_admin_cannot_deactivate_or_delete_own_account(): void
    {
        $resToggle = $this->actingAs($this->admin)->patch(route('admin.users.toggle-status', $this->admin->id));
        $resToggle->assertSessionHas('error');
        $this->admin->refresh();
        $this->assertTrue($this->admin->is_active);

        $resDelete = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->admin->id));
        $resDelete->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_bulk_user_import_template_and_preview_validation(): void
    {
        // 1. Unduh Template Default (Excel .xlsx)
        $templateRes = $this->actingAs($this->admin)->get(route('admin.users.import.template'));
        $templateRes->assertStatus(200);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $templateRes->headers->get('content-type'));

        // Unduh Template Format CSV
        $templateCsvRes = $this->actingAs($this->admin)->get(route('admin.users.import.template', ['format' => 'csv']));
        $templateCsvRes->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $templateCsvRes->headers->get('content-type'));

        // 2. Upload file Excel (.xlsx) yang di-generate via ExcelService
        $excelService = new \App\Services\ExcelService();
        $tmpExcelPath = tempnam(sys_get_temp_dir(), 'test_users_') . '.xlsx';
        $excelService->createXlsx(
            ['nama', 'nomor_identitas', 'peran', 'email'],
            [
                ['Siswa Baru A', '202409001', 'siswa', 'siswa.a@sekolah.sch.id'],
                ['Siswa Baru B', '202409002', 'siswa', 'siswa.b@sekolah.sch.id'],
                ['Siswa Duplikat DB', '202401001', 'siswa', 'duplikat.db@sekolah.sch.id'], // Duplikat DB
                ['Siswa Duplikat File', '202409001', 'siswa', 'duplikat.file@sekolah.sch.id'], // Duplikat File
            ],
            $tmpExcelPath
        );

        $uploadedFile = new UploadedFile(
            $tmpExcelPath,
            'data_siswa_guru.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $previewRes = $this->actingAs($this->admin)->post(route('admin.users.import.preview'), [
            'file' => $uploadedFile,
        ]);
        $previewRes->assertRedirect(route('admin.users.import'));

        $previewData = session('admin_import_preview');
        $this->assertNotNull($previewData);
        $this->assertEquals('XLSX', $previewData['file_type']);
        $this->assertEquals(2, $previewData['valid_count']);
        $this->assertEquals(2, $previewData['invalid_count']);

        // 3. Konfirmasi Simpan Akun Valid
        $confirmRes = $this->actingAs($this->admin)->post(route('admin.users.import.confirm'));
        $confirmRes->assertRedirect(route('admin.users.index'));
        $confirmRes->assertSessionHas('success');

        // Pastikan akun valid tersimpan dengan password default
        $userA = User::where('identity_number', '202409001')->first();
        $this->assertNotNull($userA);
        $this->assertTrue(Hash::check('eduva123@', $userA->password));
        $this->assertTrue($userA->must_change_password);

        $userB = User::where('identity_number', '202409002')->first();
        $this->assertNotNull($userB);

        if (file_exists($tmpExcelPath)) {
            @unlink($tmpExcelPath);
        }
    }

    public function test_course_monitoring_detail_and_deactivation(): void
    {
        $course = Course::create([
            'title' => 'Matematika Diskrit',
            'description' => 'Teori graf dan logika',
            'teacher_id' => $this->teacher->id,
            'invitation_code' => 'MATDIS',
            'is_active' => true,
        ]);
        $course->students()->attach($this->student->id);

        // 1. Admin melihat monitoring kelas
        $indexRes = $this->actingAs($this->admin)->get(route('admin.courses.index'));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('Matematika Diskrit');

        // 2. Admin melihat detail anggota kelas
        $showRes = $this->actingAs($this->admin)->get(route('admin.courses.show', $course->id));
        $showRes->assertStatus(200);
        $showRes->assertSee($this->student->name);
        $showRes->assertSee($this->student->identity_number);

        // 3. Admin menonaktifkan kelas
        $toggleRes = $this->actingAs($this->admin)->patch(route('admin.courses.toggle-status', $course->id));
        $toggleRes->assertSessionHas('success');
        $course->refresh();
        $this->assertFalse($course->is_active);

        // 4. Siswa lain tidak bisa bergabung ke kelas yang dinonaktifkan
        $otherStudent = User::factory()->create(['role' => 'siswa', 'identity_number' => '202401888']);
        $joinRes = $this->actingAs($otherStudent)->post(route('kelas.join'), [
            'code' => 'MATDIS',
        ]);
        $joinRes->assertSessionHas('error');
        $this->assertFalse($course->students()->where('course_user.user_id', $otherStudent->id)->exists());
    }

    public function test_security_center_and_activity_logging(): void
    {
        // 1. Buka Security Center
        $secRes = $this->actingAs($this->admin)->get(route('admin.security'));
        $secRes->assertStatus(200);
        $secRes->assertSee('Pusat Keamanan');

        // 2. Reset password via security center
        $resetRes = $this->actingAs($this->admin)->post(route('admin.security.reset-password', $this->student->id));
        $resetRes->assertSessionHas('success');
        $this->student->refresh();
        $this->assertTrue($this->student->must_change_password);
        $this->assertTrue(Hash::check('eduva123@', $this->student->password));

        // 3. Verifikasi Log Aktivitas tercatat
        $logRes = $this->actingAs($this->admin)->get(route('admin.logs'));
        $logRes->assertStatus(200);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.reset_password',
        ]);
    }

    public function test_system_settings_update(): void
    {
        $settingsRes = $this->actingAs($this->admin)->get(route('admin.settings'));
        $settingsRes->assertStatus(200);
        $settingsRes->assertSee('Identitas Platform & Lembaga', false);

        $updateRes = $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'app_name' => 'Eduva High School LMS',
            'institution_name' => 'SMK Negeri 1 Surabaya',
            'academic_year' => '2026/2027',
            'academic_semester' => 'Genap',
            'default_password' => 'newSecretPass123@',
            'allow_student_leave_course' => 1,
        ]);

        $updateRes->assertSessionHas('success');

        $this->assertEquals('Eduva High School LMS', SystemSetting::get('app_name'));
        $this->assertEquals('SMK Negeri 1 Surabaya', SystemSetting::get('institution_name'));
        $this->assertEquals('newSecretPass123@', SystemSetting::get('default_password'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'system.settings_update',
        ]);
    }
}
