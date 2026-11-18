<x-app-layout>
    <x-slot name="header">
        <div>
            <x-breadcrumb :items="['Kelas & Mata Pelajaran' => route('kelas.index'), ($attendance->course->title ?? 'Kelas') => route('kelas.show', $attendance->course_id), ('Rekap Presensi ' . $attendance->title) => '']" />
            <div class="flex items-center gap-3">
                <a href="{{ route('kelas.show', $attendance->course_id) }}" class="text-gray-400 hover:text-gray-600 transition-colors" title="Kembali ke Kelas">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                        Rekap Presensi: {{ $attendance->title }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $attendance->course->title }} &bull; {{ $attendance->topic }}</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto space-y-6">
        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-800 p-4 rounded-xl border border-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @php
            $students = $attendance->course->students;
            $logs = $attendance->logs->keyBy('user_id');
            $hadirCount = $attendance->logs->where('status', 'hadir')->count();
            $izinCount = $attendance->logs->where('status', 'izin')->count();
            $sakitCount = $attendance->logs->where('status', 'sakit')->count();
            $totalEnrolled = $students->count();
            $belumCount = max(0, $totalEnrolled - $attendance->logs->count());
        @endphp

        <!-- Metrics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <p class="text-xs font-semibold text-emerald-600 uppercase">Hadir</p>
                <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $hadirCount }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <p class="text-xs font-semibold text-blue-600 uppercase">Izin</p>
                <p class="text-2xl font-bold text-blue-700 mt-1">{{ $izinCount }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <p class="text-xs font-semibold text-amber-600 uppercase">Sakit</p>
                <p class="text-2xl font-bold text-amber-700 mt-1">{{ $sakitCount }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <p class="text-xs font-semibold text-rose-600 uppercase">Belum Mengisi</p>
                <p class="text-2xl font-bold text-rose-700 mt-1">{{ $belumCount }}</p>
            </div>
        </div>

        <!-- Attendance Detail Box -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h3 class="font-bold text-gray-900 text-lg">{{ $attendance->title }}</h3>
                <p class="text-sm text-gray-500 mt-1">
                    Periode: {{ $attendance->start_time->format('d M Y, H:i') }} - {{ $attendance->end_time->format('H:i') }} WIB
                </p>
                @if($attendance->description)
                <p class="text-sm text-gray-600 mt-2 bg-gray-50 p-2.5 rounded-lg border border-gray-100">{{ $attendance->description }}</p>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('attendances.toggle-status', $attendance->id) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="px-4 py-2 rounded-lg text-xs font-bold {{ $attendance->is_active ? 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' }} transition-colors">
                        {{ $attendance->is_active ? 'Kunci / Tutup Sesi' : 'Buka Kembali Sesi' }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h4 class="font-bold text-gray-900">Daftar Kehadiran Siswa</h4>
                <span class="text-xs text-gray-500">{{ $totalEnrolled }} Siswa Terdaftar</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-gray-600 font-semibold text-xs">
                            <th class="p-4">No</th>
                            <th class="p-4">Nama Siswa</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Waktu Presensi</th>
                            <th class="p-4">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($students as $index => $student)
                        @php
                            $log = $logs->get($student->id);
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-4 text-gray-400 font-mono">{{ $index + 1 }}</td>
                            <td class="p-4">
                                <div class="font-bold text-gray-900">{{ $student->name }}</div>
                                <div class="text-xs text-gray-500">{{ $student->email }}</div>
                            </td>
                            <td class="p-4">
                                @if($log)
                                    @if($log->status === 'hadir')
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Hadir</span>
                                    @elseif($log->status === 'izin')
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">Izin</span>
                                    @elseif($log->status === 'sakit')
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">Sakit</span>
                                    @else
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">Alpa</span>
                                    @endif
                                @else
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Belum Mengisi</span>
                                @endif
                            </td>
                            <td class="p-4 text-xs text-gray-600">
                                {{ $log ? $log->created_at->format('d M Y, H:i') : '-' }}
                            </td>
                            <td class="p-4 text-xs text-gray-600">
                                {{ $log && $log->note ? $log->note : '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-gray-500">
                                Belum ada siswa yang tergabung di kelas ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
