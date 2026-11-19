<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Absensi Siswa', 'url' => route('teacher.academic.attendance.index')],
                    ['label' => 'Rekapitulasi Kehadiran', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Rekapitulasi Kehadiran Siswa</h1>
                <p class="text-sm text-gray-500 mt-0.5">Matriks persentase kehadiran seluruh pertemuan per mata pelajaran.</p>
            </div>
            @if($selectedCourse)
            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.academic.attendance.export', ['course_id' => $selectedCourse->id, 'format' => 'xlsx']) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition-colors shadow-2xs">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Unduh Excel</span>
                </a>
                <a href="{{ route('teacher.academic.attendance.export', ['course_id' => $selectedCourse->id, 'format' => 'print']) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 transition-colors shadow-2xs">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span>Cetak / PDF</span>
                </a>
            </div>
            @endif
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filter Mata Pelajaran -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs p-4">
            <form action="{{ route('teacher.academic.attendance.recap') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full sm:w-auto">
                    <select name="course_id" onchange="this.form.submit()" class="px-3 py-2 text-xs font-semibold rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ (isset($selectedCourse) && $selectedCourse->id === $c->id) ? 'selected' : '' }}>
                                {{ $c->title }} ({{ $c->students->count() }} Siswa)
                            </option>
                        @endforeach
                    </select>

                    <div class="relative w-full sm:w-64">
                        <input type="text" name="student_search" value="{{ request('student_search') }}" placeholder="Cari nama atau NIM siswa..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>

                @if(request()->filled('student_search'))
                    <a href="{{ route('teacher.academic.attendance.recap', ['course_id' => $selectedCourse?->id]) }}" class="text-xs text-gray-500 hover:text-primary font-medium">Reset cari</a>
                @endif
            </form>
        </div>

        @if($selectedCourse)
        <!-- Ringkasan Kehadiran Kelas -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-2xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-sm">H</div>
                <div>
                    <span class="text-gray-400 font-semibold uppercase text-[10px] tracking-wider block">Total Hadir</span>
                    <span class="text-lg font-bold text-emerald-700">{{ $summary['total_hadir'] }}</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-2xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-sm">I</div>
                <div>
                    <span class="text-gray-400 font-semibold uppercase text-[10px] tracking-wider block">Total Izin</span>
                    <span class="text-lg font-bold text-blue-700">{{ $summary['total_izin'] }}</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-2xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold text-sm">S</div>
                <div>
                    <span class="text-gray-400 font-semibold uppercase text-[10px] tracking-wider block">Total Sakit</span>
                    <span class="text-lg font-bold text-amber-700">{{ $summary['total_sakit'] }}</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-2xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center font-bold text-sm">A</div>
                <div>
                    <span class="text-gray-400 font-semibold uppercase text-[10px] tracking-wider block">Total Alpa</span>
                    <span class="text-lg font-bold text-rose-700">{{ $summary['total_alpa'] }}</span>
                </div>
            </div>
        </div>

        <!-- Matriks Kehadiran -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-200 text-gray-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-3 w-10">No</th>
                            <th class="py-3 px-3 min-w-[160px]">Nama Siswa</th>
                            <th class="py-3 px-3 min-w-[100px]">NIM</th>
                            @foreach($sessions as $idx => $s)
                                <th class="py-3 px-2 text-center whitespace-nowrap" title="{{ $s->title }} ({{ $s->start_time->format('d/m/Y') }})">
                                    P{{ $idx + 1 }}
                                    <span class="block text-[10px] font-normal text-gray-400">{{ $s->start_time->format('d/m') }}</span>
                                </th>
                            @endforeach
                            <th class="py-3 px-2 text-center text-emerald-700 bg-emerald-50/40">H</th>
                            <th class="py-3 px-2 text-center text-blue-700 bg-blue-50/40">I</th>
                            <th class="py-3 px-2 text-center text-amber-700 bg-amber-50/40">S</th>
                            <th class="py-3 px-2 text-center text-rose-700 bg-rose-50/40">A</th>
                            <th class="py-3 px-3 text-right bg-gray-50 font-bold">Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($students as $no => $student)
                            @php $row = $matrix[$student->id] ?? null; @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3 px-3 text-gray-400 font-mono">{{ $no + 1 }}</td>
                                <td class="py-3 px-3 font-semibold text-gray-900">{{ $student->name }}</td>
                                <td class="py-3 px-3 font-mono text-gray-600">{{ $student->identity_number }}</td>
                                @foreach($sessions as $s)
                                    @php
                                        $sData = $row['sessions'][$s->id] ?? ['status' => 'alpa', 'note' => null];
                                        $st = $sData['status'];
                                    @endphp
                                    <td class="py-3 px-2 text-center">
                                        @if($st === 'hadir')
                                            <span class="inline-block w-5 h-5 leading-5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800" title="Hadir">H</span>
                                        @elseif($st === 'izin')
                                            <span class="inline-block w-5 h-5 leading-5 rounded text-[10px] font-bold bg-blue-100 text-blue-800" title="Izin: {{ $sData['note'] ?? '' }}">I</span>
                                        @elseif($st === 'sakit')
                                            <span class="inline-block w-5 h-5 leading-5 rounded text-[10px] font-bold bg-amber-100 text-amber-800" title="Sakit: {{ $sData['note'] ?? '' }}">S</span>
                                        @else
                                            <span class="inline-block w-5 h-5 leading-5 rounded text-[10px] font-bold bg-rose-100 text-rose-800" title="Alpa">A</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="py-3 px-2 text-center font-bold text-emerald-700 bg-emerald-50/20">{{ $row['hadir'] ?? 0 }}</td>
                                <td class="py-3 px-2 text-center font-bold text-blue-700 bg-blue-50/20">{{ $row['izin'] ?? 0 }}</td>
                                <td class="py-3 px-2 text-center font-bold text-amber-700 bg-amber-50/20">{{ $row['sakit'] ?? 0 }}</td>
                                <td class="py-3 px-2 text-center font-bold text-rose-700 bg-rose-50/20">{{ $row['alpa'] ?? 0 }}</td>
                                <td class="py-3 px-3 text-right font-bold text-gray-900 bg-gray-50/40">
                                    {{ $row['percentage'] ?? 0 }}%
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($sessions) + 8 }}" class="py-12 text-center text-gray-400">
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
