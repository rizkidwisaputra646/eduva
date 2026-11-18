<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserImportController extends Controller
{
    private function checkAdmin(): void
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses terbatas untuk Administrator.');
        }
    }

    public function show()
    {
        $this->checkAdmin();
        $importSession = session('admin_import_preview');
        return view('admin.users.import', compact('importSession'));
    }

    /**
     * Download template (Default: Excel .xlsx, opsi: CSV)
     */
    public function downloadTemplate(Request $request, ExcelService $excelService): BinaryFileResponse|StreamedResponse
    {
        $this->checkAdmin();

        $format = strtolower($request->query('format', 'xlsx'));

        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="template_import_pengguna_eduva.csv"',
            ];

            return response()->stream(function () {
                $handle = fopen('php://output', 'w');
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($handle, ['nama', 'nomor_identitas', 'peran', 'email']);
                fputcsv($handle, ['Ahmad Dahlan', '202401010', 'siswa', 'ahmad@sekolah.sch.id']);
                fputcsv($handle, ['Siti Aminah', '202401011', 'siswa', 'siti@sekolah.sch.id']);
                fputcsv($handle, ['Dr. Budi Utomo, M.Kom', '198205122010011002', 'guru', 'budi.utomo@sekolah.sch.id']);
                fputcsv($handle, ['Dewi Sartika, S.Pd', '198708202014022003', 'guru', 'dewi.sartika@sekolah.sch.id']);
                fclose($handle);
            }, 200, $headers);
        }

        // Default Excel (.xlsx)
        $tempPath = tempnam(sys_get_temp_dir(), 'eduva_tpl_') . '.xlsx';
        $excelService->generateTemplateXlsx($tempPath);

        return response()->download($tempPath, 'template_import_pengguna_eduva.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Preview and validate uploaded Excel (.xlsx) or CSV
     */
    public function preview(Request $request, ExcelService $excelService)
    {
        $this->checkAdmin();

        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // Max 10MB
        ], [
            'file.required' => 'Silakan pilih berkas Excel (.xlsx) atau CSV yang ingin diimpor.',
            'file.max' => 'Ukuran berkas tidak boleh lebih dari 10MB.',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'csv', 'txt'])) {
            return back()->with('error', 'Format berkas tidak didukung. Harap unggah berkas Excel (.xlsx) atau CSV (.csv).');
        }

        try {
            $rawRows = $excelService->parseFile($file->getRealPath(), $file->getClientOriginalName());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca berkas: ' . $e->getMessage());
        }

        if (empty($rawRows)) {
            return back()->with('error', 'Berkas yang diunggah kosong atau tidak memiliki data.');
        }

        $header = array_shift($rawRows);
        if (!$header || !is_array($header)) {
            return back()->with('error', 'Baris judul (header) berkas tidak ditemukan.');
        }

        // Normalize header strings
        $normalizedHeader = array_map(function ($h) {
            $h = strtolower(trim((string) $h));
            return match ($h) {
                'nim', 'nip', 'nim_nip', 'identitas', 'no_identitas', 'nomor_identitas', 'no identitas' => 'nomor_identitas',
                'role', 'peran', 'jabatan', 'jenis akun', 'jenis_akun' => 'peran',
                'name', 'nama_lengkap', 'nama', 'nama lengkap' => 'nama',
                'email', 'surel', 'e-mail' => 'email',
                default => $h
            };
        }, $header);

        $nameIndex = array_search('nama', $normalizedHeader);
        $identIndex = array_search('nomor_identitas', $normalizedHeader);
        $roleIndex = array_search('peran', $normalizedHeader);
        $emailIndex = array_search('email', $normalizedHeader);

        if ($nameIndex === false || $identIndex === false || $roleIndex === false) {
            return back()->with('error', 'Header berkas tidak sesuai. Pastikan ada kolom "nama", "nomor_identitas", dan "peran".');
        }

        $validRows = [];
        $invalidRows = [];
        $seenFileIdentities = [];
        $seenFileEmails = [];
        $existingIdentities = User::pluck('identity_number')->filter()->toArray();
        $existingEmails = User::pluck('email')->filter()->toArray();

        $rowNumber = 1;
        foreach ($rawRows as $data) {
            $rowNumber++;

            // Abaikan baris kosong
            if (empty(array_filter($data, fn($v) => trim((string)$v) !== ''))) {
                continue;
            }

            $name = trim((string)($data[$nameIndex] ?? ''));
            $ident = trim((string)($data[$identIndex] ?? ''));
            $rawRole = strtolower(trim((string)($data[$roleIndex] ?? '')));
            $email = ($emailIndex !== false && isset($data[$emailIndex])) ? trim((string)$data[$emailIndex]) : null;
            if ($email === '') $email = null;

            // Normalisasi peran
            $role = match ($rawRole) {
                'siswa', 'student', 'murid', 'peserta didik' => 'siswa',
                'guru', 'teacher', 'dosen', 'pengajar', 'pendidik' => 'guru',
                default => null,
            };

            $errors = [];

            // Validasi Nama
            if (empty($name)) {
                $errors[] = 'Nama lengkap wajib diisi.';
            }

            // Validasi Nomor Identitas
            if (empty($ident)) {
                $errors[] = 'NIM/NIP wajib diisi.';
            } else {
                if (in_array($ident, $existingIdentities)) {
                    $errors[] = "NIM/NIP '{$ident}' sudah terdaftar di sistem.";
                } elseif (isset($seenFileIdentities[$ident])) {
                    $errors[] = "NIM/NIP '{$ident}' terduplikasi di dalam berkas (baris {$seenFileIdentities[$ident]}).";
                } else {
                    $seenFileIdentities[$ident] = $rowNumber;
                }
            }

            // Validasi Peran
            if (!$role) {
                $errors[] = "Peran '{$rawRole}' tidak valid. Harus 'siswa' atau 'guru'.";
            }

            // Validasi Email (opsional)
            if ($email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Format email '{$email}' tidak valid.";
                } elseif (in_array(strtolower($email), array_map('strtolower', $existingEmails))) {
                    $errors[] = "Email '{$email}' sudah digunakan akun lain.";
                } elseif (isset($seenFileEmails[strtolower($email)])) {
                    $errors[] = "Email '{$email}' terduplikasi di berkas (baris {$seenFileEmails[strtolower($email)]}).";
                } else {
                    $seenFileEmails[strtolower($email)] = $rowNumber;
                }
            }

            $record = [
                'row' => $rowNumber,
                'name' => $name,
                'identity_number' => $ident,
                'role' => $role ?? $rawRole,
                'email' => $email,
            ];

            if (empty($errors)) {
                $record['status'] = 'valid';
                $validRows[] = $record;
            } else {
                $record['status'] = 'invalid';
                $record['errors'] = $errors;
                $invalidRows[] = $record;
            }
        }

        $previewData = [
            'filename' => $file->getClientOriginalName(),
            'file_type' => strtoupper($ext),
            'total_rows' => count($validRows) + count($invalidRows),
            'valid_count' => count($validRows),
            'invalid_count' => count($invalidRows),
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
        ];

        session(['admin_import_preview' => $previewData]);

        return redirect()->route('admin.users.import')->with('preview_ready', true);
    }

    public function confirm(Request $request)
    {
        $this->checkAdmin();

        $previewData = session('admin_import_preview');
        if (!$previewData || empty($previewData['valid_rows'])) {
            return redirect()->route('admin.users.import')->with('error', 'Tidak ada data valid yang dapat diimpor.');
        }

        $defaultPassword = SystemSetting::get('default_password', 'eduva123@');
        $hashedPassword = Hash::make($defaultPassword);
        $importedCount = 0;
        $studentCount = 0;
        $teacherCount = 0;

        DB::beginTransaction();
        try {
            foreach ($previewData['valid_rows'] as $row) {
                User::create([
                    'name' => $row['name'],
                    'identity_number' => $row['identity_number'],
                    'role' => $row['role'],
                    'email' => $row['email'],
                    'password' => $hashedPassword,
                    'must_change_password' => true,
                    'is_active' => true,
                ]);

                $importedCount++;
                if ($row['role'] === 'siswa') $studentCount++;
                if ($row['role'] === 'guru') $teacherCount++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('admin.users.import')->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }

        // Hapus session preview
        session()->forget('admin_import_preview');

        ActivityLog::record(
            'user.import',
            "Admin berhasil mengimpor {$importedCount} akun massal ({$studentCount} siswa, {$teacherCount} guru) dari berkas {$previewData['filename']}."
        );

        return redirect()->route('admin.users.index')->with(
            'success',
            "Berhasil mengimpor {$importedCount} akun ({$studentCount} siswa, {$teacherCount} guru). Seluruh akun disetel dengan password default '{$defaultPassword}' dan wajib mengganti password saat login pertama kali."
        );
    }

    public function cancel()
    {
        $this->checkAdmin();
        session()->forget('admin_import_preview');
        return redirect()->route('admin.users.import')->with('info', 'Proses pratinjau impor telah dibatalkan.');
    }
}
