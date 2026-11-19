<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Laporan Akademik', 'url' => route('teacher.academic.reports.index', ['course_id' => $course->id])],
                    ['label' => $user->name, 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Laporan Perkembangan Akademik Siswa</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ $user->name }} • {{ $course->title }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.academic.reports.index', ['course_id' => $course->id]) }}" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 transition-colors shadow-2xs">
                    Kembali
                </a>
                <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-primary text-white hover:bg-[#357ebd] transition-colors shadow-sm cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span>Cetak Laporan</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- 1. Identitas Siswa -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-[#DFEAF9] text-[#2E6C9E] font-bold text-xl flex items-center justify-center shrink-0">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">{{ $user->name }}</h2>
                        <div class="flex flex-wrap items-center gap-3 mt-1 text-xs text-gray-500 font-mono">
                            <span>NIM: <strong class="text-gray-800">{{ $user->identity_number }}</strong></span>
                            <span>•</span>
                            <span>Email: {{ $user->email ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <div class="sm:text-right border-t sm:border-t-0 pt-3 sm:pt-0 border-gray-100 text-xs">
                    <span class="text-gray-400 font-semibold uppercase text-[10px] tracking-wider block">Kelas Rombel & Tahun Ajaran</span>
                    <p class="font-bold text-gray-900 text-sm mt-0.5">
                        {{ $user->activeClassHistory?->schoolClass?->name ?? ($course->schoolClass?->name ?? 'Kelas Reguler') }}
                    </p>
                    <p class="text-gray-500 text-[11px] mt-0.5">
                        {{ $user->activeClassHistory?->academicYear?->name ?? 'Tahun Berjalan' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- 2. Metrik Capaian 360 -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs">
                <span class="text-gray-400 font-semibold uppercase text-[10px] tracking-wider block">Tingkat Kehadiran</span>
                <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $attPct }}%</p>
                <p class="text-[11px] text-gray-500 mt-1">
                    {{ $attCounts['hadir'] }} Hadir • {{ $attCounts['izin'] }} Izin • {{ $attCounts['sakit'] }} Sakit • {{ $attCounts['alpa'] }} Alpa
                </p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs">
                <span class="text-gray-400 font-semibold uppercase text-[10px] tracking-wider block">Rata-Rata Tugas</span>
                <p class="text-2xl font-bold text-blue-700 mt-1">{{ $avgAssignment }}</p>
                <p class="text-[11px] text-gray-500 mt-1">
                    {{ $submittedAssignmentsCount }} dari {{ $assignments->count() }} tugas dikerjakan
                </p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs">
                <span class="text-gray-400 font-semibold uppercase text-[10px] tracking-wider block">Rata-Rata Ujian</span>
                <p class="text-2xl font-bold text-purple-700 mt-1">{{ $avgExam }}</p>
                <p class="text-[11px] text-gray-500 mt-1">
                    {{ $attemptedExamsCount }} dari {{ $exams->count() }} ujian diselesaikan
                </p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs">
                <span class="text-gray-400 font-semibold uppercase text-[10px] tracking-wider block">Nilai Akhir & Predikat</span>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-2xl font-bold text-gray-900">{{ $finalScore }}</span>
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg font-bold text-sm bg-emerald-100 text-emerald-800">
                        {{ $predicate }}
                    </span>
                </div>
                <p class="text-[11px] text-gray-500 mt-1">Bobot: 40% Tugas + 60% Ujian</p>
            </div>
        </div>

        <!-- 3. Rincian Penugasan Siswa -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-bold text-gray-900 text-sm">Riwayat Penugasan & Tugas Siswa</h3>
                <span class="text-xs text-gray-400">{{ $assignments->count() }} Total Tugas</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-100 text-gray-500 font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-4">Nama Tugas</th>
                            <th class="py-2.5 px-4">Batas Waktu</th>
                            <th class="py-2.5 px-4">Status Pengumpulan</th>
                            <th class="py-2.5 px-4 text-center">Nilai</th>
                            <th class="py-2.5 px-4">Feedback Guru</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($assignments as $asg)
                            @php $sub = $asg->submissions->first(); @endphp
                            <tr class="hover:bg-gray-50/50">
                                <td class="py-3 px-4 font-semibold text-gray-900">{{ $asg->title }}</td>
                                <td class="py-3 px-4 text-gray-500">{{ $asg->deadline ? $asg->deadline->format('d/m/Y H:i') : '-' }}</td>
                                <td class="py-3 px-4">
                                    @if($sub)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700">
                                            Selesai ({{ $sub->created_at->format('d/m/Y') }})
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-50 text-rose-700">
                                            Belum Mengumpulkan
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-gray-900">
                                    {{ ($sub && !is_null($sub->score)) ? $sub->score : '-' }}
                                </td>
                                <td class="py-3 px-4 text-gray-500">{{ $sub?->feedback ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-gray-400">Belum ada tugas pada mata pelajaran ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. Rincian Ujian / Evaluasi Siswa -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-bold text-gray-900 text-sm">Riwayat Pengerjaan Ujian / Kuis</h3>
                <span class="text-xs text-gray-400">{{ $exams->count() }} Total Ujian</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-100 text-gray-500 font-semibold uppercase tracking-wider">
                            <th class="py-2.5 px-4">Nama Ujian</th>
                            <th class="py-2.5 px-4">Jadwal Ujian</th>
                            <th class="py-2.5 px-4">Status Pengerjaan</th>
                            <th class="py-2.5 px-4 text-center">Nilai Ujian</th>
                            <th class="py-2.5 px-4 text-center">Predikat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($exams as $exm)
                            @php
                                $sub = $exm->submissions->first();
                                $score = $sub?->score;
                                $pred = '-';
                                if (!is_null($score)) {
                                    $pred = match(true) {
                                        $score >= 85 => 'A',
                                        $score >= 75 => 'B',
                                        $score >= 65 => 'C',
                                        $score >= 50 => 'D',
                                        default => 'E',
                                    };
                                }
                            @endphp
                            <tr class="hover:bg-gray-50/50">
                                <td class="py-3 px-4 font-semibold text-gray-900">{{ $exm->title }}</td>
                                <td class="py-3 px-4 text-gray-500">{{ $exm->start_time ? $exm->start_time->format('d/m/Y H:i') : '-' }}</td>
                                <td class="py-3 px-4">
                                    @if($sub)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700">
                                            Sudah Mengerjakan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-600">
                                            Belum Mengerjakan
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-gray-900">{{ !is_null($score) ? $score : '-' }}</td>
                                <td class="py-3 px-4 text-center font-bold text-gray-800">{{ $pred }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-gray-400">Belum ada ujian pada mata pelajaran ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
