<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Rekapitulasi Akademik', 'url' => null],
                    ['label' => 'Rekap Nilai Siswa', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Rekapitulasi Nilai Siswa</h1>
                <p class="text-sm text-gray-500 mt-0.5">Pemantauan akumulasi nilai tugas, ujian, dan perhitungan nilai akhir per mata pelajaran.</p>
            </div>
            @if($selectedCourse)
            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.academic.grades.export', ['course_id' => $selectedCourse->id, 'assignment_weight' => $assignmentWeight, 'format' => 'xlsx']) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition-colors shadow-2xs">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Unduh Excel</span>
                </a>
                <a href="{{ route('teacher.academic.grades.export', ['course_id' => $selectedCourse->id, 'assignment_weight' => $assignmentWeight, 'format' => 'print']) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 transition-colors shadow-2xs">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span>Cetak / PDF</span>
                </a>
            </div>
            @endif
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filter Mata Pelajaran & Bobot Penilaian -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs p-4">
            <form action="{{ route('teacher.academic.grades.index') }}" method="GET" class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <select name="course_id" onchange="this.form.submit()" class="px-3 py-2 text-xs font-semibold rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ (isset($selectedCourse) && $selectedCourse->id === $c->id) ? 'selected' : '' }}>
                                {{ $c->title }} ({{ $c->students->count() }} Siswa)
                            </option>
                        @endforeach
                    </select>

                    <div class="relative w-full sm:w-60">
                        <input type="text" name="student_search" value="{{ request('student_search') }}" placeholder="Cari nama atau NIM..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>

                <!-- Konfigurasi Bobot Penilaian Transparan -->
                <div class="flex items-center gap-3 text-xs bg-gray-50 px-3 py-1.5 rounded-xl border border-gray-200">
                    <span class="font-medium text-gray-500">Bobot Penilaian:</span>
                    <label class="flex items-center gap-1 font-semibold text-gray-700">
                        Tugas:
                        <input type="number" name="assignment_weight" value="{{ $assignmentWeight }}" min="10" max="90" onchange="this.form.submit()" class="w-14 px-2 py-1 text-center text-xs rounded-lg border border-gray-300"> %
                    </label>
                    <span class="text-gray-400">•</span>
                    <span class="font-semibold text-gray-700">Ujian: {{ $examWeight }}%</span>
                </div>
            </form>
        </div>

        @if($selectedCourse)
        <!-- Ringkasan Statistik Nilai Kelas -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-2xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </div>
                <div>
                    <span class="text-gray-400 font-semibold uppercase text-[10px] tracking-wider block">Total Tugas</span>
                    <span class="text-lg font-bold text-gray-900">{{ $assignments->count() }} Tugas</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-2xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div>
                    <span class="text-gray-400 font-semibold uppercase text-[10px] tracking-wider block">Total Ujian / Kuis</span>
                    <span class="text-lg font-bold text-gray-900">{{ $exams->count() }} Ujian</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-2xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
                <div>
                    <span class="text-gray-400 font-semibold uppercase text-[10px] tracking-wider block">Rata-Rata Kelas</span>
                    <span class="text-lg font-bold text-emerald-700">{{ $classAverage }}</span>
                </div>
            </div>
        </div>

        <!-- Tabel Rekap Nilai -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-200 text-gray-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-3 w-10">No</th>
                            <th class="py-3 px-3 min-w-[170px]">Nama Siswa</th>
                            <th class="py-3 px-3 min-w-[100px]">NIM</th>
                            <th class="py-3 px-3 text-center bg-blue-50/40">Rata-rata Tugas ({{ $assignmentWeight }}%)</th>
                            <th class="py-3 px-3 text-center bg-purple-50/40">Rata-rata Ujian ({{ $examWeight }}%)</th>
                            <th class="py-3 px-3 text-center bg-emerald-50/50 font-bold text-emerald-800">Nilai Akhir</th>
                            <th class="py-3 px-3 text-center">Predikat</th>
                            <th class="py-3 px-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($students as $no => $student)
                            @php $row = $gradeData[$student->id] ?? null; @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3.5 px-3 text-gray-400 font-mono">{{ $no + 1 }}</td>
                                <td class="py-3.5 px-3 font-semibold text-gray-900">{{ $student->name }}</td>
                                <td class="py-3.5 px-3 font-mono text-gray-600">{{ $student->identity_number }}</td>
                                <td class="py-3.5 px-3 text-center font-bold text-blue-700 bg-blue-50/10">
                                    {{ number_format($row['avg_assignment'] ?? 0, 1) }}
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-purple-700 bg-purple-50/10">
                                    {{ number_format($row['avg_exam'] ?? 0, 1) }}
                                </td>
                                <td class="py-3.5 px-3 text-center font-bold text-emerald-800 bg-emerald-50/30 text-sm">
                                    {{ number_format($row['final_score'] ?? 0, 1) }}
                                </td>
                                <td class="py-3.5 px-3 text-center">
                                    @php $pred = $row['predicate'] ?? 'E'; @endphp
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg font-bold text-xs {{ in_array($pred, ['A', 'B']) ? 'bg-emerald-100 text-emerald-800' : (in_array($pred, ['C', 'D']) ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                        {{ $pred }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-right">
                                    <a href="{{ route('teacher.academic.reports.student', [$student->id, 'course_id' => $selectedCourse->id]) }}" class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary hover:underline">
                                        Detail Akademik &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-gray-400">
                                    Belum ada siswa yang terdaftar pada mata pelajaran ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
