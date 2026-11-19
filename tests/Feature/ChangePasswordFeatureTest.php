<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_view_change_password_page_and_update_password(): void
    {
        $student = User::factory()->create([
            'role' => 'siswa',
            'identity_number' => '202401001',
            'password' => Hash::make('password123'),
            'must_change_password' => true,
        ]);

        // 1. Student views change password page
        $response = $this->actingAs($student)->get(route('password.edit'));
        $response->assertStatus(200);
        $response->assertSee('Ganti Password Akun');
        $response->assertSee('202401001');
        $response->assertSee('Password Sementara');

        // 2. Student updates password
        $updateResponse = $this->actingAs($student)->put(route('password.update'), [
            'current_password' => 'password123',
            'password' => 'rahasiaBaru2026!',
            'password_confirmation' => 'rahasiaBaru2026!',
        ]);

        $updateResponse->assertSessionHasNoErrors();
        $updateResponse->assertRedirect();

        // Database verified
        $student->refresh();
        $this->assertTrue(Hash::check('rahasiaBaru2026!', $student->password));
        $this->assertFalse($student->must_change_password);

        // Activity log recorded
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'auth.password_change',
        ]);
    }

    public function test_teacher_can_view_change_password_page_and_update_password(): void
    {
        $teacher = User::factory()->create([
            'role' => 'guru',
            'identity_number' => '198501012010011001',
            'password' => Hash::make('guruLama123'),
            'must_change_password' => true,
        ]);

        // 1. Teacher views change password page
        $response = $this->actingAs($teacher)->get(route('password.edit'));
        $response->assertStatus(200);
        $response->assertSee('Ganti Password Akun');
        $response->assertSee('Tenaga Pengajar');
        $response->assertSee('198501012010011001');

        // 2. Teacher updates password
        $updateResponse = $this->actingAs($teacher)->put(route('password.update'), [
            'current_password' => 'guruLama123',
            'password' => 'guruBaruSangatAman2026!',
            'password_confirmation' => 'guruBaruSangatAman2026!',
        ]);

        $updateResponse->assertSessionHasNoErrors();
        $updateResponse->assertRedirect();

        // Database verified
        $teacher->refresh();
        $this->assertTrue(Hash::check('guruBaruSangatAman2026!', $teacher->password));
        $this->assertFalse($teacher->must_change_password);
    }

    public function test_change_password_validations_for_wrong_current_password_and_mismatch(): void
    {
        $student = User::factory()->create([
            'role' => 'siswa',
            'password' => Hash::make('password123'),
        ]);

        // 1. Wrong current password
        $resWrongCurrent = $this->actingAs($student)->put(route('password.update'), [
            'current_password' => 'salahPassword',
            'password' => 'passwordBaru123',
            'password_confirmation' => 'passwordBaru123',
        ]);
        $resWrongCurrent->assertSessionHasErrorsIn('updatePassword', 'current_password');

        // 2. Confirmation mismatch
        $resMismatch = $this->actingAs($student)->put(route('password.update'), [
            'current_password' => 'password123',
            'password' => 'passwordBaru123',
            'password_confirmation' => 'passwordBedaLagi456',
        ]);
        $resMismatch->assertSessionHasErrorsIn('updatePassword', 'password');

        // 3. Too short (less than 8 chars)
        $resShort = $this->actingAs($student)->put(route('password.update'), [
            'current_password' => 'password123',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);
        $resShort->assertSessionHasErrorsIn('updatePassword', 'password');
    }

    public function test_guest_cannot_access_change_password_page(): void
    {
        $response = $this->get(route('password.edit'));
        $response->assertRedirect(route('login'));
    }
}
