# EDUVA v4.0 — Pengawasan ujian

## Penggunaan guru

Buka **Kelola Kuis → Live Monitoring Ujian**. Gunakan preset Standard (pencatatan), Strict (menambahkan fullscreen), atau Custom. Pengiriman otomatis pada ambang insiden selalu nonaktif secara default dan harus dipilih secara eksplisit. Kebijakan tersimpan sebagai snapshot pada saat sesi dimulai; pengeditan berikutnya berlaku untuk sesi baru. Akomodasi siswa merupakan pengecualian yang dicatat dalam audit. Siswa perlu memuat ulang setelah akomodasi agar seluruh antarmuka menyesuaikan.

Dashboard menampilkan peserta, status koneksi, fokus, kegiatan terakhir, jumlah insiden, risiko, dan ringkasan ujian. Filter nama/identitas, kelas, status, risiko, serta urutkan berdasarkan insiden atau aktivitas. Detail siswa menyediakan timeline bertahap, statistik fokus/fullscreen/shortcut/clipboard/interupsi koneksi, review, catatan, peringatan, pengakhiran ujian, serta ekspor CSV/XLSX/cetak PDF. Review tidak menghapus bukti asli dan tidak mengubah nilai otomatis.

## Penggunaan siswa

Baca aturan dan centang persetujuan sebelum mulai. Fullscreen diminta melalui tindakan siswa jika diwajibkan; selalu tersedia jalan keluar. Kebijakan fallback menentukan apakah perangkat tanpa dukungan fullscreen boleh melanjutkan. Pemberitahuan ini tidak menggantikan izin institusi atau penjelasan guru mengenai akomodasi.

Jawaban teks disimpan di server melalui sinkronisasi setiap 5 detik. Perubahan yang belum terkirim disimpan sementara di sessionStorage tab yang sama. Refresh memulihkan sesi, jawaban, timer dari server, hitungan dan antrean kejadian. Lampiran diunggah segera setelah dipilih dan dipulihkan sebagai referensi server. Lampiran yang belum berhasil diunggah tetap harus dipilih ulang. Menutup tab/browser sebelum data terkirim dapat menghilangkan data lokal yang belum tersimpan.

Status simpan, status koneksi dan peringatan terpisah. Setelah selesai, pemantauan, timer dan heartbeat berhenti. Jawaban yang telah dikumpulkan tidak dapat ditimpa oleh request berikutnya.

## Arsitektur dan integritas

- `ExamMonitoringService`: otorisasi sesi, snapshot kebijakan, registrasi instance, korelasi fokus, hitungan transaksional, eskalasi dan heartbeat.
- `ExamSubmissionService`: penilaian dan finalisasi bersama untuk manual, timeout, ambang kebijakan, serta tindakan guru. Kunci baris sesi melindungi proses finalisasi.
- `ExamLockdownService`: validasi Config Key SEB terhadap URL request.
- `exam/anti-cheat-monitor.js`: deteksi browser, antrean UUID, retry, sinkronisasi jawaban, fullscreen, clipboard dan shortcut.
- `exam/dashboard.js`: polling/incremental timeline, filter, SSE opsional, review dan tindakan guru.

Blur/hidden dalam satu siklus menjadi satu insiden. Kejadian kembali menyimpan durasi berdasarkan waktu penerimaan server. `incident_id` menghubungkan raw evidence terkait; `counted` membedakan bukti mentah dan insiden yang dihitung. Fullscreen exit hanya dihitung setelah server menerima fullscreen enter. Heartbeat memakai nomor urut per instance untuk mencegah jawaban lama menimpa snapshot baru. UUID request bersifat idempoten. Replay UUID milik sesi lain ditolak. Semua endpoint memerlukan autentikasi, CSRF untuk perubahan, validasi, pembatasan laju pada ingestion, dan otorisasi enrollment atau kepemilikan guru/admin.

Jendela kedua ditolak saat instance pertama masih mengirim heartbeat. Konflik tercatat sekali per instance. Setelah lease lebih dari 20 detik tanpa heartbeat, instance baru boleh mengambil alih; instance lama ditolak ketika kembali. Ini mendeteksi instance browser yang terdaftar, bukan seluruh aktivitas perangkat. Memanipulasi kode browser tetap mungkin dilakukan oleh pemilik perangkat.

Seluruh waktu server menjadi acuan. Timestamp klien hanya diagnostik. Interupsi koneksi dicatat terpisah ketika koneksi pulih; lama interupsi adalah perkiraan interval antar-heartbeat, bukan durasi tepat hilangnya jaringan. Offline tidak menambah hitungan pelanggaran.

## Deploy dan konfigurasi

```powershell
composer install
php artisan migrate --no-interaction
npm.cmd ci
npm.cmd run build
php artisan schedule:work
```

Di produksi, jalankan `php artisan schedule:run` setiap menit melalui scheduler sistem. `exam:maintain` mengumpulkan sesi kedaluwarsa dari jawaban terakhir di server tanpa menunggu browser kembali. `exam:maintain --prune` menghapus evidence/review dan registrasi instance yang melewati retensi, tanpa menghapus nilai atau jawaban akademik. Jadwal bawaan memeriksa timeout setiap menit dan retensi setiap hari. Backup database sebelum migrasi; tidak ada reset database.

Variabel `.env` opsional:

```dotenv
EXAM_HEARTBEAT_SECONDS=5
EXAM_UNCERTAIN_SECONDS=10
EXAM_OFFLINE_SECONDS=20
EXAM_RETENTION_DAYS=180
EXAM_SSE_ENABLED=false
SEB_CONFIG_KEYS=
```

Pertahankan offline threshold lebih panjang daripada uncertain threshold dan frekuensi heartbeat. Sekolah menentukan retensi, otorisasi peninjau dan prosedur penanganan false positive sebelum produksi. Data yang dikumpulkan dibatasi pada aktivitas halaman ujian dan jawaban yang diperlukan. Tidak ada webcam, mikrofon, isi clipboard, nama proses, URL tab lain, atau screenshot.

Polling terautentikasi setiap 5 detik merupakan mode bawaan tanpa infrastruktur tambahan. Untuk SSE, set `EXAM_SSE_ENABLED=true` pada deployment dengan beberapa worker PHP, nonaktifkan buffering proxy untuk endpoint stream, dan sediakan worker yang cukup untuk siswa/guru. Stream berakhir dan menyambung kembali secara berkala; jika gagal, dashboard memakai polling. Jangan mengaktifkan SSE pada server development PHP dengan satu worker karena dapat menghambat request lain. Target awal 100 peserta memerlukan pengujian beban di lingkungan deployment; tes fungsional tidak membuktikan kapasitas tersebut.

## Integrasi Level 2: Safe Exam Browser

Integrasi opsional menggunakan **Config Key request hash** sesuai dokumentasi resmi: https://safeexambrowser.org/developer/seb-config-key.html. Ini merupakan gerbang konfigurasi klien, bukan pemantauan proses dari situs web.

1. Sekolah membuat dan mendistribusikan konfigurasi `.seb` memakai aplikasi konfigurasi SEB. Tetapkan URL mulai, daftar URL yang diizinkan, pembatasan aplikasi, kata sandi admin/keluar, dan kebijakan darurat sesuai perangkat.
2. Aktifkan **Use Browser & Config Keys (send in HTTP header)** pada klien yang mendukung. Salin Config Key konfigurasi ke `SEB_CONFIG_KEYS` server (beberapa key dipisahkan koma). Jangan menaruh key di kode JavaScript atau repositori.
3. Aktifkan `Wajib Config Key SEB sekolah` untuk ujian. Server memeriksa `X-SafeExamBrowser-ConfigKeyHash = SHA256(absoluteRequestURL + ConfigKey)` pada request sesi/ujian. Hash salah atau tidak ada ditolak; jawaban yang sudah tersimpan dipertahankan.
4. Gunakan HTTPS, URL publik yang konsisten, dan konfigurasi trusted proxy sesuai deployment agar URL yang dihitung cocok dengan URL klien. Uji login, start, heartbeat, upload, refresh dan submit pada perangkat sekolah sebelum ujian.

Jalur yang disediakan menggunakan header request, terutama untuk klien Windows yang mendukung. Modern WebView macOS/iOS dapat memerlukan SEB JavaScript API yang belum menjadi bagian integrasi header ini. Klien tanpa header yang valid ditolak. Belum dilakukan pengujian dengan aplikasi SEB terpasang di perangkat sekolah; tes otomatis memverifikasi formula hash dan penolakan akses. Config Key sendiri tidak menjamin keaslian perangkat dan dapat disalahgunakan jika bocor. Kebijakan sekolah, distribusi konfigurasi, kontrol perangkat, dan verifikasi pengawas tetap diperlukan.

## Pengujian

```powershell
php artisan test
npm.cmd run build
npx.cmd playwright install chromium
npx.cmd playwright test
```

PHPUnit menggunakan database in-memory. Playwright membuat database khusus `storage/framework/testing/browser.sqlite`, menyiapkan akun fiktif, dan menjalankan server port 8011. Jangan memakai fixture browser pada database produksi. Tes browser memeriksa pemulihan jawaban, jendela kedua, timeline/review, koneksi offline/reconnect, fullscreen jika browser mendukung dan auto-submit. Perubahan fokus dalam headless diuji dengan sinyal DOM terkontrol; ini tidak memverifikasi perilaku seluruh shortcut sistem operasi atau berpindah aplikasi fisik. Lakukan uji perangkat dan aksesibilitas sebelum ujian resmi.
