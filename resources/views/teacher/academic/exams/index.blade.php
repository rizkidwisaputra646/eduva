<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Rekapitulasi Akademik', 'url' => null],
                    ['label' => 'Rekap Ujian', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Rekapitulasi Ujian & Kuis</h1>
                <p class="text-sm text-gray-500 mt-0.5">Statistik hasil evaluasi siswa, distribusi predikat, nilai rata-rata, tertinggi, dan terendah.</p>
            </div>
            @if($selectedExam)
            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.academic.exams.export', ['exam_id' => $selectedExam->id, 'format' => 'xlsx']) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition-colors shadow-2xs">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Unduh Excel</span>
                </a>
                <a href="{{ route('teacher.academic.exams.export', ['exam_id' => $selectedExam->id, 'format' => 'print']) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 transition-colors shadow-2xs">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span>Cetak / PDF</span>
                </a>
            </div>
            @endif
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filter Mata Pelajaran & Pilihan Ujian -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs p-4">
            <form action="{{ route('teacher.academic.exams.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Mata Pelajaran</label>
                    <select name="course_id" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs font-semibold rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ (isset($selectedCourse) && $selectedCourse->id === $c->id) ? 'selected' : '' }}>
                                {{ $c->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Pilih Ujian / Kuis</label>
                    <select name="exam_id" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs font-semibold rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        @forelse($exams as $e)
                            <option value="{{ $e->id }}" {{ (isset($selectedExam) && $selectedExam->id === $e->id) ? 'selected' : '' }}>
                                {{ $e->title }}
                            </option>
                        @empty
                            <option value="">-- Belum ada ujian pada kelas ini --</option>
                        @endforelse
                    </select>
                </div>
            </form>
        </div>

        @if($selectedExam)
        <!-- Statistik Ujian (Nilai Rata-rata, Tertinggi, Terendah, Partisipasi) -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-2xs">
                <span class="text-gray-400 font-semibold uppercase text-[10px] tracking-wider block">Partisipasi Siswa</span>
                <p class="text-lg font-bold text-gray-900 mt-1">
                    {{ $examStats['attempted'] }} <span class="text-xs font-normal text-gray-400">/ {{ $examStats['total_students'] }} Siswa</span>
                </p>
                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2">
                    @php $pPct = $examStats['total_students'] > 0 ? round(($examStats['attempted'] / $examStats['total_students']) * 100) : 0; @endphp
                    <div class="bg-primary h-1.5 rounded-full" style="width: {{ $pPct }}%"></div>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-2xs">
                <span class="text-gray-400 font-semibold uppercase text-[10px] tracking-wider block">Rata-Rata Nilai</span>
                <p class="text-xl font-bold text-primary mt-1">{{ $examStats['average'] }}</p>
                <span class="text-[11px] text-gray-400 block mt-1">Skala 0 – 100</span>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-2xs">
                <span class="text-gray-400 font-semibold uppercase text-[10px] tracking-wider block">Nilai Tertinggi</span>
                <p class="text-xl font-bold text-emerald-600 mt-1">{{ $examStats['highest'] }}</p>
                <span class="text-[11px] text-gray-400 block mt-1">Skor maksimum dicapai</span>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-2xs">
                <span class="text-gray-400 font-semibold uppercase text-[10px] tracking-wider block">Nilai Terendah</span>
                <p class="text-xl font-bold text-rose-600 mt-1">{{ $examStats['lowest'] }}</p>
                <span class="text-[11px] text-gray-400 block mt-1">Skor minimum dicapai</span>
            </div>
        </div>

        <!-- Distribusi Predikat Nilai Ujian -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs p-5">
            <h3 class="font-bold text-gray-900 text-sm mb-3">Distribusi Sebaran Nilai</h3>
            <div class="grid grid-cols-5 gap-2 text-center text-xs">
                <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-100">
                    <span class="font-bold text-emerald-800 text-base">A</span>
                    <span class="block text-emerald-700 font-bold text-sm mt-1">{{ $examStats['distribution']['A'] }} Siswa</span>
                    <span class="text-[10px] text-emerald-600">&ge; 85</span>
                </div>
                <div class="p-3 bg-blue-50 rounded-xl border border-blue-100">
                    <span class="font-bold text-blue-800 text-base">B</span>
                    <span class="block text-blue-700 font-bold text-sm mt-1">{{ $examStats['distribution']['B'] }} Siswa</span>
                    <span class="text-[10px] text-blue-600">75 – 84</span>
                </div>
                <div class="p-3 bg-indigo-50 rounded-xl border border-indigo-100">
                    <span class="font-bold text-indigo-800 text-base">C</span>
                    <span class="block text-indigo-700 font-bold text-sm mt-1">{{ $examStats['distribution']['C'] }} Siswa</span>
                    <span class="text-[10px] text-indigo-600">65 – 74</span>
                </div>
                <div class="p-3 bg-amber-50 rounded-xl border border-amber-100">
                    <span class="font-bold text-amber-800 text-base">D</span>
                    <span class="block text-amber-700 font-bold text-sm mt-1">{{ $examStats['distribution']['D'] }} Siswa</span>
                    <span class="text-[10px] text-amber-600">50 – 64</span>
                </div>
                <div class="p-3 bg-rose-50 rounded-xl border border-rose-100">
                    <span class="font-bold text-rose-800 text-base">E</span>
                    <span class="block text-rose-700 font-bold text-sm mt-1">{{ $examStats['distribution']['E'] }} Siswa</span>
                    <span class="text-[10px] text-rose-600">&lt; 50</span>
                </div>
            </div>
        </div>

        <!-- Tabel Hasil Siswa -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-200 text-gray-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4 w-12">No</th>
                            <th class="py-3 px-4">Nama Siswa</th>
                            <th class="py-3 px-4">NIM</th>
                            <th class="py-3 px-4">Status Pengerjaan</th>
                            <th class="py-3 px-4">Waktu Ujian</th>
                            <th class="py-3 px-4 text-center">Nilai Ujian</th>
                            <th class="py-3 px-4 text-center">Predikat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($studentRows as $idx => $row)
                            @php
                                $student = $row['student'];
                                $sub = $row['submission'];
                                $hasAttempted = $row['has_attempted'];
                                $score = $row['score'];
                                $pred = '-';
                                if (!is_null($score)) {
                                    $pred = match (true) {
                                        $score >= 85 => 'A',
                                        $score >= 75 => 'B',
                                        $score >= 65 => 'C',
                                        $score >= 50 => 'D',
                                        default => 'E',
                                    };
                                }
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3.5 px-4 text-gray-400 font-mono">{{ $idx + 1 }}</td>
                                <td class="py-3.5 px-4 font-semibold text-gray-900">{{ $student->name }}</td>
                                <td class="py-3.5 px-4 font-mono font-medium text-gray-700">{{ $student->identity_number }}</td>
                                <td class="py-3.5 px-4">
                                    @if($hasAttempted)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                            Selesai Mengerjakan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-600">
                                            Belum Mengerjakan
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-gray-600">
                                    {{ $sub ? $sub->created_at->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-sm text-gray-900">
                                    {{ !is_null($score) ? $score : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($pred !== '-')
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg font-bold text-xs {{ in_array($pred, ['A', 'B']) ? 'bg-emerald-100 text-emerald-800' : (in_array($pred, ['C', 'D']) ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                            {{ $pred }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-400">
                                    Belum ada siswa terdaftar pada kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center text-gray-400">
            Pilih mata pelajaran yang memiliki ujian/kuis untuk melihat rekapitulasi nilai.
        </div>
        @endif
    </div>
</x-app-layout>
