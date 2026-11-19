<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Nilai — {{ $course->title }}</title>
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
        <h1>DAFTAR REKAPITULASI NILAI AKADEMIK SISWA</h1>
        <p>LMS EDUVA — PLATFORM PEMBELAJARAN TERPADU</p>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td style="width: 15%;"><strong>Mata Pelajaran:</strong></td>
                <td style="width: 35%;">{{ $course->title }}</td>
                <td style="width: 15%;"><strong>Guru Pengampu:</strong></td>
                <td style="width: 35%;">{{ $course->teacher->name ?? '-' }} (NIP: {{ $course->teacher->identity_number ?? '-' }})</td>
            </tr>
            <tr>
                <td><strong>Kelas Rombel:</strong></td>
                <td>{{ $course->schoolClass?->name ?? 'Reguler' }}</td>
                <td><strong>Bobot Penilaian:</strong></td>
                <td>Tugas: {{ $assignmentWeight }}% | Ujian: {{ $examWeight }}%</td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 80px;">NIM</th>
                <th>Nama Siswa</th>
                <th style="width: 80px;">Rata-rata Tugas</th>
                <th style="width: 80px;">Rata-rata Ujian</th>
                <th style="width: 70px;">Nilai Akhir</th>
                <th style="width: 50px;">Predikat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $idx => $st)
                @php
                    $asgScores = [];
                    foreach ($assignments as $a) {
                        $sub = $submissions->get($st->id)?->get($a->id)?->first();
                        if ($sub && !is_null($sub->score)) $asgScores[] = $sub->score;
                    }
                    $exmScores = [];
                    foreach ($exams as $e) {
                        $sub = $submissions->get($st->id)?->get($e->id)?->first();
                        if ($sub && !is_null($sub->score)) $exmScores[] = $sub->score;
                    }
                    $avgAsg = count($asgScores) > 0 ? round(array_sum($asgScores) / count($asgScores), 1) : 0;
                    $avgExm = count($exmScores) > 0 ? round(array_sum($exmScores) / count($exmScores), 1) : 0;
                    $final = round(($avgAsg * ($assignmentWeight / 100)) + ($avgExm * ($examWeight / 100)), 1);
                    $pred = match (true) {
                        $final >= 85 => 'A',
                        $final >= 75 => 'B',
                        $final >= 65 => 'C',
                        $final >= 50 => 'D',
                        default => 'E',
                    };
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center">{{ $st->identity_number ?? '-' }}</td>
                    <td>{{ $st->name }}</td>
                    <td class="text-center">{{ $avgAsg }}</td>
                    <td class="text-center">{{ $avgExm }}</td>
                    <td class="text-center"><strong>{{ $final }}</strong></td>
                    <td class="text-center"><strong>{{ $pred }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

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
