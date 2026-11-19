# Perubahan EDUVA v4.0

## Skema dan backend

- `database/migrations/2026_11_19_000001_add_exam_monitoring.php`
- `database/migrations/2026_11_19_000002_extend_exam_evidence.php`
- `database/migrations/2026_11_19_000003_complete_exam_monitoring.php`
- `database/migrations/2026_11_19_000004_track_exam_client_sequences.php`
- `app/Http/Controllers/ExamMonitoringController.php`
- `app/Http/Controllers/QuizController.php`
- `app/Http/Controllers/SubmissionController.php`
- `app/Services/ExamMonitoringService.php`
- `app/Services/ExamSubmissionService.php`
- `app/Services/ExamLockdownService.php`
- `app/Models/ExamSession.php`
- `app/Models/ExamLog.php`
- `app/Models/Quiz.php`
- `config/exam.php`, `routes/web.php`, `routes/console.php`, `.env.example`

Empat migrasi memperluas quizzes, exam_sessions dan exam_logs; menambah exam_client_instances dan exam_monitoring_reviews. Canonical incident diwakili event counted dengan incident_id; raw event terkait memakai incident_id yang sama. UUID event memiliki unique index. Data akademik tidak di-reset.

## Frontend

- `resources/js/app.js`
- `resources/js/exam/anti-cheat-monitor.js`
- `resources/js/exam/dashboard.js`
- `resources/views/kuis/monitoring.blade.php`
- `resources/views/kuis/monitoring-report.blade.php`
- `resources/views/kuis/kerjakan.blade.php`
- `resources/views/kuis/show.blade.php`
- `resources/views/kelas/show.blade.php`

## Pengujian dan dokumentasi

- `tests/Feature/ExamMonitoringTest.php`
- `tests/Feature/ExamSecurityTest.php`
- `tests/Feature/QuizAntiCheatAndWordImportTest.php`
- `tests/Feature/QuizDurationAndSessionTest.php`
- `tests/browser/fixture.php`, `tests/browser/setup.js`, `tests/browser/monitoring.spec.js`
- `playwright.config.js`, `package.json`, `package-lock.json`, `.gitignore`
- `README.md`, `prd.md`, `docs/exam-monitoring.md`, `docs/exam-changes.md`

## Hasil validasi

- PHPUnit: 106 tes, 636 assertions lulus.
- Playwright Chromium: 2 skenario lulus. Fokus DOM terkontrol, recovery, koneksi, dashboard/review, fullscreen bersyarat dan auto-submit tercakup.
- Build Vite berhasil.
- Migrasi SQLite: up, rollback empat migrasi, dan reapply berhasil di database sementara.
- Aplikasi SEB pada perangkat sekolah, database produksi MySQL/PostgreSQL, dan beban 100 peserta belum diuji di lingkungan ini.

Panduan deployment, privasi, dan batasan: [exam-monitoring.md](exam-monitoring.md).
