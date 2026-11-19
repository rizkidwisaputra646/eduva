<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Absensi Siswa', 'url' => route('teacher.academic.attendance.index')],
                    ['label' => $attendance->title, 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Input & Koreksi Presensi Siswa</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ $attendance->course->title }} • {{ $attendance->topic }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.academic.attendance.index') }}" class="px-3.5 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-2xs">
                    Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Info Sesi -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-primary">Detail Sesi Presensi</span>
                <h2 class="text-lg font-bold text-gray-900 mt-0.5">{{ $attendance->title }}</h2>
                <p class="text-xs text-gray-500 mt-1">
                    Waktu: {{ $attendance->start_time->format('d M Y, H:i') }} – {{ $attendance->end_time->format('H:i') }} WIB
                </p>
            </div>

            <!-- Tombol Tandai Cepat Semua Hadir -->
            <div class="flex items-center gap-2">
                <button type="button" onclick="markAll('hadir')" class="px-3 py-1.5 bg-emerald-50 text-emerald-800 text-xs font-semibold rounded-xl border border-emerald-200 hover:bg-emerald-100 transition-colors cursor-pointer">
                    Set Semua Hadir
                </button>
                <button type="button" onclick="markAll('alpa')" class="px-3 py-1.5 bg-rose-50 text-rose-800 text-xs font-semibold rounded-xl border border-rose-200 hover:bg-rose-100 transition-colors cursor-pointer">
                    Set Semua Alpa
                </button>
            </div>
        </div>

        <!-- Form Presensi Per Siswa -->
        <form method="POST" action="{{ route('teacher.academic.attendance.update', $attendance->id) }}">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/75 border-b border-gray-200 text-gray-500 font-semibold uppercase tracking-wider">
                                <th class="py-3 px-4 w-12">No</th>
                                <th class="py-3 px-4">Nama Siswa</th>
                                <th class="py-3 px-4">NIM</th>
                                <th class="py-3 px-4 text-center">Status Kehadiran</th>
                                <th class="py-3 px-4">Catatan / Alasan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($students as $idx => $st)
                                @php
                                    $log = $logs->get($st->id);
                                    $currStatus = $log ? $log->status : 'alpa';
                                @endphp
                                <tr class="hover:bg-gray-50/40 transition-colors">
                                    <td class="py-3.5 px-4 font-mono text-gray-400">{{ $idx + 1 }}</td>
                                    <td class="py-3.5 px-4 font-semibold text-gray-900">{{ $st->name }}</td>
                                    <td class="py-3.5 px-4 font-mono font-medium text-gray-700">{{ $st->identity_number }}</td>
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="inline-flex items-center gap-3">
                                            <label class="flex items-center gap-1 cursor-pointer">
                                                <input type="radio" name="attendances[{{ $st->id }}][status]" value="hadir" {{ $currStatus === 'hadir' ? 'checked' : '' }} class="status-radio-hadir text-emerald-600 focus:ring-emerald-500">
                                                <span class="text-xs font-semibold text-emerald-700">Hadir</span>
                                            </label>

                                            <label class="flex items-center gap-1 cursor-pointer">
                                                <input type="radio" name="attendances[{{ $st->id }}][status]" value="izin" {{ $currStatus === 'izin' ? 'checked' : '' }} class="status-radio-izin text-blue-600 focus:ring-blue-500">
                                                <span class="text-xs font-semibold text-blue-700">Izin</span>
                                            </label>

                                            <label class="flex items-center gap-1 cursor-pointer">
                                                <input type="radio" name="attendances[{{ $st->id }}][status]" value="sakit" {{ $currStatus === 'sakit' ? 'checked' : '' }} class="status-radio-sakit text-amber-600 focus:ring-amber-500">
                                                <span class="text-xs font-semibold text-amber-700">Sakit</span>
                                            </label>

                                            <label class="flex items-center gap-1 cursor-pointer">
                                                <input type="radio" name="attendances[{{ $st->id }}][status]" value="alpa" {{ $currStatus === 'alpa' ? 'checked' : '' }} class="status-radio-alpa text-rose-600 focus:ring-rose-500">
                                                <span class="text-xs font-semibold text-rose-700">Alpa</span>
                                            </label>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <input type="text" name="attendances[{{ $st->id }}][note]" value="{{ $log->note ?? '' }}" placeholder="Keterangan surat dokter, izin dll..." class="w-full max-w-xs px-3 py-1.5 text-xs rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-gray-400">
                                        Belum ada siswa yang terdaftar dalam mata pelajaran ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Footer Simpan -->
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">
                        Total <strong>{{ $students->count() }}</strong> siswa terdaftar dalam sesi ini.
                    </p>
                    <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-[#357ebd] text-white font-semibold text-xs rounded-xl shadow-sm transition-all cursor-pointer">
                        Simpan Perubahan Presensi
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function markAll(status) {
            document.querySelectorAll('.status-radio-' + status).forEach(el => {
                el.checked = true;
            });
        }
    </script>
</x-app-layout>
