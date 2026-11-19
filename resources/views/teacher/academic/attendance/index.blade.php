<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Rekapitulasi Akademik', 'url' => null],
                    ['label' => 'Absensi Siswa', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Pengelolaan Absensi Siswa</h1>
                <p class="text-sm text-gray-500 mt-0.5">Kelola sesi presensi harian per pertemuan dan pantau rekapitulasi kehadiran kelas.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.academic.attendance.recap') }}" class="px-3.5 py-2.5 bg-white text-gray-700 text-xs font-semibold rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors shadow-2xs">
                    Rekapitulasi Lengkap
                </a>
                <a href="{{ route('teacher.academic.attendance.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-[#357ebd] text-white text-xs font-semibold rounded-xl shadow-sm transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Buka Sesi Presensi</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filter Bar -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs p-4">
            <form action="{{ route('teacher.academic.attendance.index') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full sm:w-auto">
                    <select name="course_id" onchange="this.form.submit()" class="px-3 py-2 text-xs font-semibold rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        <option value="">Semua Mata Pelajaran</option>
                        @foreach($courses as $crs)
                            <option value="{{ $crs->id }}" {{ request('course_id') == $crs->id ? 'selected' : '' }}>
                                {{ $crs->title }}
                            </option>
                        @endforeach
                    </select>

                    <div class="relative w-full sm:w-64">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari judul atau pertemuan..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>

                @if(request()->hasAny(['course_id', 'search']))
                    <a href="{{ route('teacher.academic.attendance.index') }}" class="text-xs text-gray-500 hover:text-primary font-medium">Reset filter</a>
                @endif
            </form>
        </div>

        <!-- Daftar Sesi Presensi -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-200 text-gray-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Pertemuan / Sesi</th>
                            <th class="py-3 px-4">Mata Pelajaran</th>
                            <th class="py-3 px-4">Rentang Waktu</th>
                            <th class="py-3 px-4 text-center">Kehadiran (H / I / S / A)</th>
                            <th class="py-3 px-4 text-center">Status Sesi</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($attendances as $att)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-gray-900 text-sm">{{ $att->title }}</div>
                                    <span class="inline-flex items-center text-[11px] text-gray-500 mt-0.5">Topik: {{ $att->topic }}</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-semibold text-gray-800">{{ $att->course->title ?? '-' }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-gray-600">
                                    <div>{{ $att->start_time ? $att->start_time->format('d M Y, H:i') : '-' }}</div>
                                    <div class="text-[11px] text-gray-400">s/d {{ $att->end_time ? $att->end_time->format('H:i') : '-' }} WIB</div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <div class="inline-flex items-center gap-1.5 font-mono text-xs">
                                        <span class="text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100" title="Hadir">{{ $att->hadir_count }} H</span>
                                        <span class="text-blue-700 font-bold bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100" title="Izin">{{ $att->izin_count }} I</span>
                                        <span class="text-amber-700 font-bold bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100" title="Sakit">{{ $att->sakit_count }} S</span>
                                        <span class="text-rose-700 font-bold bg-rose-50 px-1.5 py-0.5 rounded border border-rose-100" title="Alpa">{{ $att->alpa_count }} A</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @php $isExpired = $att->end_time && now()->greaterThan($att->end_time); @endphp
                                    @if(!$att->is_active || $isExpired)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded-full">
                                            Selesai / Ditutup
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Dibuka
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('teacher.academic.attendance.edit', $att->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-[#357ebd] transition-colors shadow-2xs">
                                            <span>Input / Koreksi</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-400">
                                    Belum ada sesi presensi yang dibuat. Klik "Buka Sesi Presensi" untuk memulai.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($attendances->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $attendances->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
