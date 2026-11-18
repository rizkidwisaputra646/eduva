<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementAndAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_and_teacher_can_login_using_nim_or_nip(): void
    {
        $student = User::factory()->create([
            'role' => 'siswa',
            'identity_number' => '202401099',
            'password' => Hash::make('secret123'),
        ]);

        $teacher = User::factory()->create([
            'role' => 'guru',
            'identity_number' => '198501012010011999',
            'password' => Hash::make('teacher123'),
        ]);

        // Student login with NIM
        $response = $this->post('/login', [
            'identity_number' => '202401099',
            'password' => 'secret123',
        ]);
        $this->assertAuthenticatedAs($student);
        $response->assertRedirect(route('dashboard', absolute: false));

        // Logout
        $this->post('/logout');
        $this->assertGuest();

        // Teacher login with NIP
        $responseTeacher = $this->post('/login', [
            'identity_number' => '198501012010011999',
            'password' => 'teacher123',
        ]);
        $this->assertAuthenticatedAs($teacher);
        $responseTeacher->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_admin_can_login_using_admin_username(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'identity_number' => 'admin',
            'password' => Hash::make('admin123'),
        ]);

        $response = $this->post('/login', [
            'identity_number' => 'admin',
            'password' => 'admin123',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_admin_can_create_user_with_automatic_default_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'identity_number' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Budi Santoso',
            'identity_number' => '202409999',
            'role' => 'siswa',
            'email' => 'budi.santoso@eduva.test',
        ]);

        $response->assertSessionHas('success');

        $user = User::where('identity_number', '202409999')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Budi Santoso', $user->name);
        $this->assertEquals('siswa', $user->role);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('eduva123@', $user->password));
    }

    public function test_admin_can_reset_user_password_to_default(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'identity_number' => 'admin',
        ]);

        $student = User::factory()->create([
            'role' => 'siswa',
            'identity_number' => '202401001',
            'password' => Hash::make('alreadyChangedPassword123!'),
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->post("/admin/users/{$student->id}/reset-password");

        $response->assertSessionHas('success');

        $student->refresh();
        $this->assertTrue(Hash::check('eduva123@', $student->password));
        $this->assertTrue($student->must_change_password);
    }

    public function test_non_admin_cannot_access_admin_or_reset_passwords(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);
        $targetUser = User::factory()->create(['role' => 'siswa']);

        $resIndex = $this->actingAs($student)->get('/admin');
        $resIndex->assertStatus(403);

        $resStore = $this->actingAs($student)->post('/admin/users', [
            'name' => 'Hacker',
            'identity_number' => '99999999',
            'role' => 'siswa',
        ]);
        $resStore->assertStatus(403);

        $resReset = $this->actingAs($student)->post("/admin/users/{$targetUser->id}/reset-password");
        $resReset->assertStatus(403);
    }

    public function test_user_can_update_password_and_clears_must_change_flag(): void
    {
        $student = User::factory()->create([
            'role' => 'siswa',
            'password' => Hash::make('eduva123@'),
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($student)->put('/password', [
            'current_password' => 'eduva123@',
            'password' => 'newSecretPass#2026',
            'password_confirmation' => 'newSecretPass#2026',
        ]);

        $response->assertSessionHas('status', 'password-updated');

        $student->refresh();
        $this->assertTrue(Hash::check('newSecretPass#2026', $student->password));
        $this->assertFalse($student->must_change_password);
    }

    public function test_student_can_leave_course_and_past_submissions_remain_intact(): void
    {
        $teacher = User::factory()->create(['role' => 'guru']);
        $student = User::factory()->create(['role' => 'siswa']);

        $course = Course::create([
            'title' => 'Pemrograman Web II',
            'description' => 'Mata kuliah web',
            'teacher_id' => $teacher->id,
            'invitation_code' => 'WEB202',
        ]);

        // Enroll student
        $course->students()->attach($student->id);
        $this->assertTrue($course->students()->where('course_user.user_id', $student->id)->exists());

        // Create a quiz and student submission
        $quiz = Quiz::create([
            'course_id' => $course->id,
            'title' => 'Kuis HTML',
        ]);

        $submission = Submission::create([
            'course_id' => $course->id,
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 88.5,
            'status' => 'graded',
        ]);

        // Student leaves course
        $response = $this->actingAs($student)->post("/kelas/{$course->id}/leave");
        $response->assertRedirect(route('kelas.index'));
        $response->assertSessionHas('success');

        // Verify student is no longer in course
        $this->assertFalse($course->students()->where('course_user.user_id', $student->id)->exists());

        // Verify past submission still exists in database for teacher reporting
        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'score' => 88.5,
        ]);

        // Verify student cannot re-access course directly via URL
        $accessResponse = $this->actingAs($student)->get("/kelas/{$course->id}");
        $accessResponse->assertStatus(403);
    }

    public function test_teacher_cannot_leave_course_via_leave_route(): void
    {
        $teacher = User::factory()->create(['role' => 'guru']);
        $course = Course::create([
            'title' => 'Pemrograman Web II',
            'description' => 'Mata kuliah web',
            'teacher_id' => $teacher->id,
            'invitation_code' => 'WEB999',
        ]);

        $response = $this->actingAs($teacher)->post("/kelas/{$course->id}/leave");
        $response->assertStatus(403);
    }
}
