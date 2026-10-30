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
    Route::put('/kelas/{course}', [\App\Http\Controllers\CourseController::class, 'update'])->name('kelas.update');
    Route::delete('/kelas/{course}', [\App\Http\Controllers\CourseController::class, 'destroy'])->name('kelas.destroy');
    
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
    Route::delete('/questions/{question}', [\App\Http\Controllers\QuizController::class, 'destroyQuestion'])->name('questions.destroy');

    // Anggota Kelas
    Route::delete('/kelas/{course}/students/{user}', [\App\Http\Controllers\CourseController::class, 'removeStudent'])->name('kelas.students.remove');
    
    // Admin
    Route::get('/admin', [\App\Http\Controllers\AdminUserController::class, 'index'])->name('admin.index');
    Route::post('/admin/users', [\App\Http\Controllers\AdminUserController::class, 'store'])->name('admin.store');
    Route::delete('/admin/users/{user}', [\App\Http\Controllers\AdminUserController::class, 'destroy'])->name('admin.users.destroy');

    // Others
    Route::get('/kuis', function () { return view('kuis.index'); })->name('kuis.index');
    Route::get('/kuis/{quiz}/kerjakan', [\App\Http\Controllers\QuizController::class, 'attempt'])->name('kuis.kerjakan');
    Route::post('/kuis/{quiz}/submit', [\App\Http\Controllers\QuizController::class, 'submit'])->name('kuis.submit');
    // AI Tutor
    Route::get('/ai-tutor', [\App\Http\Controllers\AiTutorController::class, 'index'])->name('ai-tutor.index');
    Route::post('/ai-tutor/chat', [\App\Http\Controllers\AiTutorController::class, 'sendMessage'])->name('ai-tutor.chat');
    Route::get('/ai-tutor/new', [\App\Http\Controllers\AiTutorController::class, 'newConversation'])->name('ai-tutor.new');
    Route::put('/ai-tutor/conversations/{conversation}', [\App\Http\Controllers\AiTutorController::class, 'renameConversation'])->name('ai-tutor.rename');
    Route::delete('/ai-tutor/conversations/{conversation}', [\App\Http\Controllers\AiTutorController::class, 'deleteConversation'])->name('ai-tutor.delete');
});

require __DIR__.'/auth.php';
