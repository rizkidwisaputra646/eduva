<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Hasil Ujian — {{ $exam->title }}</title>
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
        <h1>REKAPITULASI HASIL EVALUASI UJIAN / KUIS</h1>
        <p>LMS EDUVA — PLATFORM PEMBELAJARAN TERPADU</p>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td style="width: 15%;"><strong>Judul Ujian:</strong></td>
                <td style="width: 35%;">{{ $exam->title }}</td>
                <td style="width: 15%;"><strong>Guru Pengampu:</strong></td>
                <td style="width: 35%;">{{ $exam->course->teacher->name ?? '-' }} (NIP: {{ $exam->course->teacher->identity_number ?? '-' }})</td>
            </tr>
            <tr>
                <td><strong>Mata Pelajaran:</strong></td>
                <td>{{ $exam->course->title }}</td>
                <td><strong>Waktu Mulai:</strong></td>
                <td>{{ $exam->start_time ? $exam->start_time->format('d/m/Y H:i') . ' WIB' : '-' }}</td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 80px;">NIM</th>
                <th>Nama Siswa</th>
                <th style="width: 130px;">Status Pengerjaan</th>
                <th style="width: 120px;">Waktu Submit</th>
                <th style="width: 60px;">Nilai</th>
                <th style="width: 50px;">Predikat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $idx => $st)
                @php
                    $sub = $submissions->get($st->id);
                    $status = $sub ? 'Selesai Mengerjakan' : 'Belum Mengerjakan';
                    $score = $sub && !is_null($sub->score) ? $sub->score : '-';
                    $pred = '-';
                    if ($sub && !is_null($sub->score)) {
                        $v = (float) $sub->score;
                        $pred = match(true) {
                            $v >= 85 => 'A',
                            $v >= 75 => 'B',
                            $v >= 65 => 'C',
                            $v >= 50 => 'D',
                            default => 'E',
                        };
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center">{{ $st->identity_number ?? '-' }}</td>
                    <td>{{ $st->name }}</td>
                    <td class="text-center">{{ $status }}</td>
                    <td class="text-center">{{ $sub ? $sub->created_at->format('d/m/Y H:i') : '-' }}</td>
                    <td class="text-center"><strong>{{ $score }}</strong></td>
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
            <p><strong>{{ $exam->course->teacher->name ?? 'Guru Pengampu' }}</strong><br>NIP: {{ $exam->course->teacher->identity_number ?? '-' }}</p>
        </div>
    </div>
</body>
</html>
