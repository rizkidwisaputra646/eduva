<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Kehadiran — {{ $course->title }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 11px; color: #111; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #222; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { font-size: 16px; margin: 0 0 4px; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 11px; color: #555; }
        .meta { margin-bottom: 14px; font-size: 11px; }
        .meta table { width: 100%; }
        .meta td { padding: 2px 0; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th, table.data td { border: 1px solid #333; padding: 4px 6px; text-align: left; }
        table.data th { background-color: #f2f2f2; text-align: center; }
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .footer { margin-top: 30px; display: flex; justify-content: space-between; font-size: 11px; }
        .sig-box { width: 200px; text-align: center; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 16px;">
        <button onclick="window.print()" style="padding: 8px 16px; font-weight: bold; background: #2E6C9E; color: white; border: none; border-radius: 6px; cursor: pointer;">
            Cetak Dokumen / Simpan PDF
        </button>
    </div>

    <div class="header">
        <h1>REKAPITULASI PRESENSI KEHADIRAN SISWA</h1>
        <p>LMS EDUVA — PLATFORM PEMBELAJARAN TERPADU</p>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td style="width: 15%;"><strong>Mata Pelajaran:</strong></td>
                <td style="width: 35%;">{{ $course->title }}</td>
                <td style="width: 15%;"><strong>Wali / Guru:</strong></td>
                <td style="width: 35%;">{{ $course->teacher->name ?? '-' }} (NIP: {{ $course->teacher->identity_number ?? '-' }})</td>
            </tr>
            <tr>
                <td><strong>Kelas Rombel:</strong></td>
                <td>{{ $course->schoolClass?->name ?? 'Reguler' }}</td>
                <td><strong>Tanggal Cetak:</strong></td>
                <td>{{ now()->translatedFormat('d F Y, H:i') }} WIB</td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 80px;">NIM</th>
                <th>Nama Siswa</th>
                @foreach($sessions as $idx => $s)
                    <th style="width: 24px;">P{{ $idx + 1 }}</th>
                @endforeach
                <th style="width: 30px;">H</th>
                <th style="width: 30px;">I</th>
                <th style="width: 30px;">S</th>
                <th style="width: 30px;">A</th>
                <th style="width: 45px;">%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $idx => $st)
                @php $h = 0; $i = 0; $s_count = 0; $a = 0; @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center">{{ $st->identity_number ?? '-' }}</td>
                    <td>{{ $st->name }}</td>
                    @foreach($sessions as $s)
                        @php
                            $log = $allLogs->get($st->id)?->get($s->id)?->first();
                            $status = $log ? strtoupper(substr($log->status, 0, 1)) : 'A';
                            $stName = $log ? $log->status : 'alpa';
                            if ($stName === 'hadir') $h++;
                            elseif ($stName === 'izin') $i++;
                            elseif ($stName === 'sakit') $s_count++;
                            else $a++;
                        @endphp
                        <td class="text-center">{{ $status }}</td>
                    @endforeach
                    <td class="text-center"><strong>{{ $h }}</strong></td>
                    <td class="text-center">{{ $i }}</td>
                    <td class="text-center">{{ $s_count }}</td>
                    <td class="text-center">{{ $a }}</td>
                    <td class="text-right">
                        @php $total = count($sessions); $pct = $total > 0 ? round(($h / $total) * 100) : 0; @endphp
                        <strong>{{ $pct }}%</strong>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 10px; font-size: 10px; color: #555;">
        Keterangan: H = Hadir, I = Izin, S = Sakit, A = Alpa / Tanpa Keterangan.
    </div>

    <div class="footer">
        <div class="sig-box">
            <p>Mengetahui,<br>Kepala Sekolah / Kurikulum</p>
            <div style="height: 50px;"></div>
            <p>( .................................................... )</p>
        </div>
        <div class="sig-box">
            <p>Guru Pengampu,<br>&nbsp;</p>
            <div style="height: 50px;"></div>
            <p><strong>{{ $course->teacher->name ?? 'Guru Pengampu' }}</strong><br>NIP: {{ $course->teacher->identity_number ?? '-' }}</p>
        </div>
    </div>
</body>
</html>
