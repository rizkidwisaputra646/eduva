<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Kelas (Mata Pelajaran)
    Route::get('/kelas', [\App\Http\Controllers\CourseController::class, 'index'])->name('kelas.index');
    Route::post('/kelas', [\App\Http\Controllers\CourseController::class, 'store'])->name('kelas.store');
    Route::post('/kelas/join', [\App\Http\Controllers\CourseController::class, 'join'])->name('kelas.join');
    Route::get('/kelas/{course}', [\App\Http\Controllers\CourseController::class, 'show'])->name('kelas.show');
    Route::post('/kelas/{course}/leave', [\App\Http\Controllers\CourseController::class, 'leave'])->name('kelas.leave');
    Route::put('/kelas/{course}', [\App\Http\Controllers\CourseController::class, 'update'])->name('kelas.update');
    Route::delete('/kelas/{course}', [\App\Http\Controllers\CourseController::class, 'destroy'])->name('kelas.destroy');
    Route::delete('/kelas/{course}/meetings', [\App\Http\Controllers\CourseController::class, 'destroyMeeting'])->name('kelas.meetings.destroy');
    
    // Pengumuman
    Route::post('/kelas/{course}/announcements', [\App\Http\Controllers\AnnouncementController::class, 'store'])->name('announcements.store');
    Route::put('/announcements/{announcement}', [\App\Http\Controllers\AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}', [\App\Http\Controllers\AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    // Materi
    Route::post('/kelas/{course}/materials', [\App\Http\Controllers\MaterialController::class, 'store'])->name('materials.store');
    Route::put('/materials/{material}', [\App\Http\Controllers\MaterialController::class, 'update'])->name('materials.update');
    Route::patch('/materials/{material}/toggle', [\App\Http\Controllers\MaterialController::class, 'toggleVisibility'])->name('materials.toggle');
    Route::delete('/materials/{material}', [\App\Http\Controllers\MaterialController::class, 'destroy'])->name('materials.destroy');

    // Kuis & Tugas
    Route::post('/kelas/{course}/quizzes', [\App\Http\Controllers\QuizController::class, 'store'])->name('quizzes.store');
    Route::put('/quizzes/{quiz}', [\App\Http\Controllers\QuizController::class, 'update'])->name('quizzes.update');
    Route::patch('/quizzes/{quiz}/toggle', [\App\Http\Controllers\QuizController::class, 'toggleVisibility'])->name('quizzes.toggle');
    Route::delete('/quizzes/{quiz}', [\App\Http\Controllers\QuizController::class, 'destroy'])->name('quizzes.destroy');

    // Absensi (Attendance)
    Route::post('/kelas/{course}/attendances', [\App\Http\Controllers\AttendanceController::class, 'store'])->name('attendances.store');
    Route::put('/attendances/{attendance}', [\App\Http\Controllers\AttendanceController::class, 'update'])->name('attendances.update');
    Route::patch('/attendances/{attendance}/toggle-status', [\App\Http\Controllers\AttendanceController::class, 'toggleStatus'])->name('attendances.toggle-status');
    Route::patch('/attendances/{attendance}/toggle-visibility', [\App\Http\Controllers\AttendanceController::class, 'toggleVisibility'])->name('attendances.toggle-visibility');
    Route::delete('/attendances/{attendance}', [\App\Http\Controllers\AttendanceController::class, 'destroy'])->name('attendances.destroy');
    Route::post('/attendances/{attendance}/submit', [\App\Http\Controllers\AttendanceController::class, 'submit'])->name('attendances.submit');
    Route::get('/attendances/{attendance}/report', [\App\Http\Controllers\AttendanceController::class, 'report'])->name('attendances.report');

    // Pengumpulan Tugas (Submissions)
    Route::post('/assignments/{quiz}/submit', [\App\Http\Controllers\SubmissionController::class, 'store'])->name('assignments.submit');
    Route::get('/assignments/{quiz}/submissions', [\App\Http\Controllers\SubmissionController::class, 'index'])->name('assignments.submissions');
    Route::put('/submissions/{submission}/grade', [\App\Http\Controllers\SubmissionController::class, 'grade'])->name('submissions.grade');

    // Kelola Kuis & Soal
    Route::get('/quizzes/{quiz}', [\App\Http\Controllers\QuizController::class, 'show'])->name('quizzes.show');
    Route::post('/quizzes/{quiz}/questions', [\App\Http\Controllers\QuizController::class, 'storeQuestion'])->name('questions.store');
    Route::put('/questions/{question}', [\App\Http\Controllers\QuizController::class, 'updateQuestion'])->name('questions.update');
    Route::delete('/questions/{question}', [\App\Http\Controllers\QuizController::class, 'destroyQuestion'])->name('questions.destroy');

    // Import Soal via Word (.docx)
    Route::get('/quizzes/templates/download/{type}', [\App\Http\Controllers\QuizImportController::class, 'downloadTemplate'])->name('quizzes.templates.download');
    Route::post('/quizzes/{quiz}/import/upload', [\App\Http\Controllers\QuizImportController::class, 'upload'])->name('quizzes.import.upload');
    Route::get('/quizzes/{quiz}/import/preview', [\App\Http\Controllers\QuizImportController::class, 'preview'])->name('quizzes.import.preview');
    Route::post('/quizzes/{quiz}/import/confirm', [\App\Http\Controllers\QuizImportController::class, 'confirm'])->name('quizzes.import.confirm');

    // AI Question Generator
    Route::get('/quizzes/{quiz}/ai-generator', [\App\Http\Controllers\QuizAiGeneratorController::class, 'show'])->name('quizzes.ai-generator.show');
    Route::post('/quizzes/{quiz}/ai-generator/generate', [\App\Http\Controllers\QuizAiGeneratorController::class, 'generate'])->name('quizzes.ai-generator.generate');
    Route::get('/quizzes/{quiz}/ai-generator/preview', [\App\Http\Controllers\QuizAiGeneratorController::class, 'preview'])->name('quizzes.ai-generator.preview');
    Route::post('/quizzes/{quiz}/ai-generator/regenerate', [\App\Http\Controllers\QuizAiGeneratorController::class, 'regenerate'])->name('quizzes.ai-generator.regenerate');
    Route::post('/quizzes/{quiz}/ai-generator/store', [\App\Http\Controllers\QuizAiGeneratorController::class, 'store'])->name('quizzes.ai-generator.store');
    Route::post('/quizzes/{quiz}/ai-generator/cancel', [\App\Http\Controllers\QuizAiGeneratorController::class, 'cancel'])->name('quizzes.ai-generator.cancel');

    // Anggota Kelas
    Route::delete('/kelas/{course}/students/{user}', [\App\Http\Controllers\CourseController::class, 'removeStudent'])->name('kelas.students.remove');
    
    // Admin Module (Pusat Pengelolaan & Monitoring Sistem)
    Route::prefix('admin')->name('admin.')->group(function () {
        // 1. Dashboard
        Route::get('/', [\App\Http\Controllers\AdminDashboardController::class, 'index'])->name('index');
        Route::get('/dashboard', [\App\Http\Controllers\AdminDashboardController::class, 'index'])->name('dashboard');

        // 2. Manajemen Pengguna
        Route::get('/users', [\App\Http\Controllers\AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [\App\Http\Controllers\AdminUserController::class, 'store'])->name('store');
        Route::put('/users/{user}', [\App\Http\Controllers\AdminUserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle-status', [\App\Http\Controllers\AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('/users/{user}/reset-password', [\App\Http\Controllers\AdminUserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{user}', [\App\Http\Controllers\AdminUserController::class, 'destroy'])->name('users.destroy');

        // 3. Import Pengguna
        Route::get('/users/import', [\App\Http\Controllers\AdminUserImportController::class, 'show'])->name('users.import');
        Route::get('/users/import/template', [\App\Http\Controllers\AdminUserImportController::class, 'downloadTemplate'])->name('users.import.template');
        Route::post('/users/import/preview', [\App\Http\Controllers\AdminUserImportController::class, 'preview'])->name('users.import.preview');
        Route::post('/users/import/confirm', [\App\Http\Controllers\AdminUserImportController::class, 'confirm'])->name('users.import.confirm');
        Route::post('/users/import/cancel', [\App\Http\Controllers\AdminUserImportController::class, 'cancel'])->name('users.import.cancel');

        // 4. Monitoring Kelas
        Route::get('/courses', [\App\Http\Controllers\AdminCourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course}', [\App\Http\Controllers\AdminCourseController::class, 'show'])->name('courses.show');
        Route::patch('/courses/{course}/toggle-status', [\App\Http\Controllers\AdminCourseController::class, 'toggleStatus'])->name('courses.toggle-status');

        // 5. Keamanan Akun
        Route::get('/security', [\App\Http\Controllers\AdminSecurityController::class, 'index'])->name('security');
        Route::post('/security/{user}/reset-password', [\App\Http\Controllers\AdminSecurityController::class, 'resetPassword'])->name('security.reset-password');
        Route::patch('/security/{user}/toggle-status', [\App\Http\Controllers\AdminSecurityController::class, 'toggleStatus'])->name('security.toggle-status');

        // 6. Log Aktivitas
        Route::get('/logs', [\App\Http\Controllers\AdminLogController::class, 'index'])->name('logs');

        // 7. Pengaturan Sistem
        Route::get('/settings', [\App\Http\Controllers\AdminSettingController::class, 'index'])->name('settings');
        Route::post('/settings', [\App\Http\Controllers\AdminSettingController::class, 'update'])->name('settings.update');
    });

    // Ujian / Pengerjaan Kuis & Monitoring Anti-Cheating
    Route::get('/kuis', function () { return view('kuis.index'); })->name('kuis.index');
    Route::get('/kuis/{quiz}/kerjakan', [\App\Http\Controllers\QuizController::class, 'attempt'])->name('kuis.kerjakan');
    Route::post('/kuis/{quiz}/start', [\App\Http\Controllers\QuizController::class, 'startSession'])->name('kuis.start');
    Route::post('/kuis/{quiz}/log-event', [\App\Http\Controllers\QuizController::class, 'logEvent'])->name('kuis.log-event');
    Route::post('/kuis/{quiz}/submit', [\App\Http\Controllers\QuizController::class, 'submit'])->name('kuis.submit');
    // AI Tutor
    Route::get('/ai-tutor', [\App\Http\Controllers\AiTutorController::class, 'index'])->name('ai-tutor.index');
    Route::get('/ai-tutor/courses/{courseId}/meetings', [\App\Http\Controllers\AiTutorController::class, 'getCourseMeetings'])->name('ai-tutor.courses.meetings');
    Route::post('/ai-tutor/chat', [\App\Http\Controllers\AiTutorController::class, 'sendMessage'])->name('ai-tutor.chat');
    Route::get('/ai-tutor/new', [\App\Http\Controllers\AiTutorController::class, 'newConversation'])->name('ai-tutor.new');
    Route::put('/ai-tutor/conversations/{conversation}', [\App\Http\Controllers\AiTutorController::class, 'renameConversation'])->name('ai-tutor.rename');
    Route::delete('/ai-tutor/conversations/{conversation}', [\App\Http\Controllers\AiTutorController::class, 'deleteConversation'])->name('ai-tutor.delete');
});

require __DIR__.'/auth.php';
