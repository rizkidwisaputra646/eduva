# Eduva — Learning Management System (LMS)

Eduva adalah platform Learning Management System modern berbasis Laravel yang dilengkapi dengan AI Tutor kontekstual, AI Quiz Generator, ujian online berfitur anti-cheat, serta manajemen akademik terpadu.

---

## Anggota Kelompok

| Nama | NIM |
| --- | --- |
| Rizki Dwi Saputra | 24312049 |
| Erwin Wijaya | 24312092 |
| Jagarya Vanneska | 24312069 |
| Dionisius Avendrata Putra | 24312062 |
| Greys Olan Timothy Siallagan | 24312096 |

**Universitas Teknokrat Indonesia**

---

## Fitur Utama

- **Role-Based Access Control (RBAC)**: Admin, Guru, dan Siswa dengan alur login NIM/NIP/Username.
- **Manajemen Akademik**: Tahun ajaran, kelas, penempatan siswa, serta rekap nilai dan presensi.
- **AI Tutor Terintegrasi**: Chatbot AI kontekstual per pertemuan/materi (Google Gemini API).
- **AI Quiz Generator & Document Parser**: Ekstrak materi dari PDF/DOCX/PPTX untuk pembuatan soal kuis otomatis.
- **Ujian Online & Anti-Cheat**: Deteksi tab-switch, durasi ujian berbasis server-timer, dan auto-submit saat timeout.

---

## Prasyarat Sistem

- PHP 8.2+
- Ekstensi PHP aktif: `zip`, `sqlite3`, `pdo_sqlite`, `mbstring`, `openssl`, `curl`
- Composer 2.x
- Node.js 18+ & NPM

---

## Setup Lokal

Jalankan perintah berikut di terminal (PowerShell / Bash):

```bash
# 1. Masuk direktori
cd eduva

# 2. Salin env file
cp .env.example .env
# Windows PowerShell: if (!(Test-Path .env)) { Copy-Item .env.example .env }

# 3. Buat database SQLite lokal
touch database/database.sqlite
# Windows PowerShell: if (!(Test-Path database/database.sqlite)) { New-Item database/database.sqlite -ItemType File }

# 4. Install dependensi
composer install
npm install

# 5. Generate app key, migrate schema & seed data
php artisan key:generate
php artisan migrate --seed
php artisan storage:link

# 6. Build aset frontend
npm run build
```

---

## Menjalankan Aplikasi

Jalankan server aplikasi:

```bash
# Terminal 1 - Backend
php artisan serve --host=127.0.0.1 --port=8000

# Terminal 2 - Frontend HMR (saat development)
npm run dev
```

Akses web di: [http://127.0.0.1:8000](http://127.0.0.1:8000)

---

## Akun Demo

Password default semua akun: `password`

| Peran | NIM / NIP / Username | Email |
| --- | --- | --- |
| **Admin** | `admin` | `admin@eduva.com` |
| **Guru** | `198501012010011001` | `guru@eduva.com` |
| **Siswa** | `202401001` | `siswa@eduva.com` |

---

## Konfigurasi Tambahan (.env)

- **AI Tutor / Generator**: Isi `GEMINI_API_KEY=your_api_key_here` di `.env` untuk integrasi AI.
- **Email Log**: Default `MAIL_MAILER=log`, pesan tercatat di `storage/logs/laravel.log`.

---

## Pengujian (Testing)

Jalankan automated test suite:

```bash
php artisan test
```

## Pengawasan ujian EDUVA v4.0

Panduan fitur, kebijakan, setup scheduler, SSE opsional, integrasi Safe Exam Browser, privasi dan pengujian tersedia di [docs/exam-monitoring.md](docs/exam-monitoring.md).
