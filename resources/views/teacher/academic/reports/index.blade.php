<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Rekapitulasi Akademik', 'url' => null],
                    ['label' => 'Laporan Siswa', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Laporan Akademik Siswa</h1>
                <p class="text-sm text-gray-500 mt-0.5">Ringkasan performa terpadu: kehadiran, pengerjaan tugas, evaluasi ujian, dan rekam jejak belajar.</p>
            </div>
            @if($selectedCourse)
            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.academic.reports.export', ['course_id' => $selectedCourse->id, 'format' => 'xlsx']) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition-colors shadow-2xs">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Unduh Excel</span>
                </a>
                <a href="{{ route('teacher.academic.reports.export', ['course_id' => $selectedCourse->id, 'format' => 'print']) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 transition-colors shadow-2xs">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span>Cetak / PDF</span>
                </a>
            </div>
            @endif
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filter Mata Pelajaran & Search -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs p-4">
            <form action="{{ route('teacher.academic.reports.index') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full sm:w-auto">
                    <select name="course_id" onchange="this.form.submit()" class="px-3 py-2 text-xs font-semibold rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ (isset($selectedCourse) && $selectedCourse->id === $c->id) ? 'selected' : '' }}>
                                {{ $c->title }} ({{ $c->students->count() }} Siswa)
                            </option>
                        @endforeach
                    </select>

                    <div class="relative w-full sm:w-64">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau NIM..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>

                @if(request()->filled('search'))
                    <a href="{{ route('teacher.academic.reports.index', ['course_id' => $selectedCourse?->id]) }}" class="text-xs text-gray-500 hover:text-primary font-medium">Reset cari</a>
                @endif
            </form>
        </div>

        @if($selectedCourse)
        <!-- Tabel Ringkasan Akademik Siswa -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-200 text-gray-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4 w-12">No</th>
                            <th class="py-3 px-4">Nama Siswa</th>
                            <th class="py-3 px-4">NIM</th>
                            <th class="py-3 px-4">Kelas Rombel</th>
                            <th class="py-3 px-4 text-center">Kehadiran (%)</th>
                            <th class="py-3 px-4 text-center">Rata-rata Tugas</th>
                            <th class="py-3 px-4 text-center">Rata-rata Ujian</th>
                            <th class="py-3 px-4 text-center font-bold text-gray-900">Nilai Akhir</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($students as $idx => $st)
                            @php $summary = $reportSummary[$st->id] ?? null; @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3.5 px-4 text-gray-400 font-mono">{{ $students->firstItem() + $idx }}</td>
                                <td class="py-3.5 px-4 font-semibold text-gray-900">{{ $st->name }}</td>
                                <td class="py-3.5 px-4 font-mono font-medium text-gray-700">{{ $st->identity_number }}</td>
                                <td class="py-3.5 px-4 text-gray-600">
                                    {{ $st->activeClassHistory?->schoolClass?->name ?? ($selectedCourse->schoolClass?->name ?? 'Reguler') }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @php $att = $summary['attendance_pct'] ?? 0; @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $att >= 80 ? 'bg-emerald-50 text-emerald-700' : ($att >= 70 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') }}">
                                        {{ $att }}%
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-semibold text-blue-700">
                                    {{ $summary['avg_assignment'] ?? 0 }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-semibold text-purple-700">
                                    {{ $summary['avg_exam'] ?? 0 }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-sm text-gray-900">
                                    {{ $summary['final_score'] ?? 0 }}
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <a href="{{ route('teacher.academic.reports.student', [$st->id, 'course_id' => $selectedCourse->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary/10 hover:bg-primary/20 text-primary font-semibold text-xs transition-colors">
                                        <span>Lihat Rapor</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-gray-400">
                                    Belum ada siswa terdaftar pada kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($students->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $students->links() }}
                </div>
            @endif
        </div>
        @endif
    </div>
</x-app-layout>
