<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                    ['label' => 'Manajemen Akademik', 'url' => null],
                    ['label' => 'Monitoring Akademik', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Monitoring Akademik Sekolah</h1>
                <p class="text-sm text-gray-500 mt-0.5">Pantau ringkasan performa akademik, distribusi siswa, tingkat kehadiran, dan penilaian sekolah.</p>
            </div>
            <div>
                <form action="{{ route('admin.academic.monitoring.index') }}" method="GET" class="flex items-center gap-2">
                    <select name="academic_year_id" onchange="this.form.submit()" class="px-3 py-2 text-xs font-semibold rounded-xl border border-gray-200 bg-white focus:border-primary focus:ring-1 focus:ring-primary shadow-2xs">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $selectedYearId == $ay->id ? 'selected' : '' }}>
                                Tahun: {{ $ay->name }} ({{ $ay->semester }}) {{ $ay->is_active ? '— Aktif' : '' }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- 4 Grid Statistik Utama -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Rombel Kelas</span>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total_classes'] }} <span class="text-xs font-normal text-gray-400">kelas</span></p>
                <p class="text-[11px] text-gray-500 mt-1">Tahun ajaran ini</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Siswa Ditempatkan</span>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['total_placed_students'] }} <span class="text-xs font-normal text-gray-400">/ {{ $stats['total_students'] }} siswa</span></p>
                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2">
                    @php $placedPct = $stats['total_students'] > 0 ? round(($stats['total_placed_students'] / $stats['total_students']) * 100) : 0; @endphp
                    <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $placedPct }}%"></div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Tingkat Kehadiran Sekolah</span>
                <p class="text-2xl font-bold text-primary mt-1">{{ $attendanceStats['rate'] }}%</p>
                <p class="text-[11px] text-gray-500 mt-1">Dari {{ $attendanceStats['total'] }} total catatan log presensi</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Tugas Dinilai</span>
                <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $submissionStats['graded_submissions'] }} <span class="text-xs font-normal text-gray-400">/ {{ $submissionStats['total_submissions'] }}</span></p>
                <p class="text-[11px] text-gray-500 mt-1">{{ $submissionStats['pending_submissions'] }} tugas menunggu penilaian guru</p>
            </div>
        </div>

        <!-- 2 Kolom: Distribusi Kelas & Ringkasan Kehadiran Global -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Kolom Kiri: Distribusi Kelas Rombel (2 Kolom) -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-2xs p-5">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                    <h2 class="font-bold text-gray-900 text-sm">Distribusi Siswa per Kelas Rombel</h2>
                    <a href="{{ route('admin.academic.classes.index') }}" class="text-xs font-semibold text-primary hover:underline">Kelola Kelas &rarr;</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/75 border-b border-gray-100 text-gray-500 font-semibold uppercase tracking-wider">
                                <th class="py-2.5 px-3">Nama Rombel</th>
                                <th class="py-2.5 px-3">Tingkat</th>
                                <th class="py-2.5 px-3">Wali Kelas</th>
                                <th class="py-2.5 px-3 text-right">Jumlah Siswa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($classesDistribution as $cd)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="py-2.5 px-3 font-semibold text-gray-900">
                                        <a href="{{ route('admin.academic.classes.show', $cd->id) }}" class="hover:text-primary hover:underline">
                                            {{ $cd->name }}
                                        </a>
                                    </td>
                                    <td class="py-2.5 px-3 text-gray-600">Kelas {{ $cd->level ?? '-' }}</td>
                                    <td class="py-2.5 px-3 text-gray-600">{{ $cd->homeroomTeacher->name ?? 'Belum Ditentukan' }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-gray-900">{{ $cd->students_count }} Siswa</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-gray-400">Belum ada kelas rombel terdaftar pada tahun ajaran ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Kolom Kanan: Breakdown Kehadiran Global -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs p-5 space-y-4">
                <div class="pb-3 border-b border-gray-100">
                    <h2 class="font-bold text-gray-900 text-sm">Ringkasan Kehadiran Sekolah</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Komposisi kehadiran seluruh siswa</p>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <div class="flex items-center justify-between text-gray-700 font-medium mb-1">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Hadir
                            </span>
                            <span class="font-bold">{{ $attendanceStats['hadir'] }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            @php $hPct = $attendanceStats['total'] > 0 ? round(($attendanceStats['hadir'] / $attendanceStats['total']) * 100) : 0; @endphp
                            <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $hPct }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-gray-700 font-medium mb-1">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Izin
                            </span>
                            <span class="font-bold">{{ $attendanceStats['izin'] }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            @php $iPct = $attendanceStats['total'] > 0 ? round(($attendanceStats['izin'] / $attendanceStats['total']) * 100) : 0; @endphp
                            <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $iPct }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-gray-700 font-medium mb-1">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Sakit
                            </span>
                            <span class="font-bold">{{ $attendanceStats['sakit'] }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            @php $sPct = $attendanceStats['total'] > 0 ? round(($attendanceStats['sakit'] / $attendanceStats['total']) * 100) : 0; @endphp
                            <div class="bg-amber-500 h-2 rounded-full" style="width: {{ $sPct }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-gray-700 font-medium mb-1">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Alpa
                            </span>
                            <span class="font-bold">{{ $attendanceStats['alpa'] }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            @php $aPct = $attendanceStats['total'] > 0 ? round(($attendanceStats['alpa'] / $attendanceStats['total']) * 100) : 0; @endphp
                            <div class="bg-rose-500 h-2 rounded-full" style="width: {{ $aPct }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 text-[11px] text-gray-500 leading-relaxed mt-4">
                    Catatan: Rekapitulasi absensi diperbarui secara real-time berdasarkan input presensi dari masing-masing guru mata pelajaran dan absensi kelas.
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
