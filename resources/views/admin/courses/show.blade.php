<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                    ['label' => 'Monitoring Kelas', 'url' => route('admin.courses.index')],
                    ['label' => $course->title, 'url' => null]
                ]" />
                <div class="flex items-center gap-3 mt-1">
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ $course->title }}</h1>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $course->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $course->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                        {{ $course->is_active ? 'Kelas Aktif' : 'Kelas Dinonaktifkan' }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-0.5">Pemantauan detail kelas, penanggung jawab guru, dan daftar siswa terdaftar.</p>
            </div>
            <div class="flex items-center gap-2">
                <form action="{{ route('admin.courses.toggle-status', $course->id) }}" method="POST" onsubmit="return confirm('Ubah status operasional kelas ini?');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="px-3.5 py-2 text-sm font-semibold rounded-xl border transition-colors shadow-sm {{ $course->is_active ? 'border-amber-200 text-amber-800 bg-amber-50 hover:bg-amber-100' : 'border-emerald-200 text-emerald-800 bg-emerald-50 hover:bg-emerald-100' }}">
                        {{ $course->is_active ? 'Nonaktifkan Kelas' : 'Aktifkan Kembali Kelas' }}
                    </button>
                </form>
                <a href="{{ route('admin.courses.index') }}" class="px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                    Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        <!-- Grid Ringkasan Kelas & Guru Pengampu -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Info Kelas & Kode (2 Kolom) -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col justify-between">
                <div>
                    <span class="text-xs font-semibold text-primary uppercase tracking-wider">Informasi Kelas</span>
                    <h2 class="text-xl font-bold text-gray-900 mt-1">{{ $course->title }}</h2>
                    <p class="text-xs text-gray-500 mt-2 leading-relaxed">
                        {{ $course->description ?: 'Tidak ada deskripsi khusus untuk mata pelajaran ini.' }}
                    </p>

                    <!-- Kode Undangan -->
                    <div class="mt-6 flex flex-wrap items-center gap-4 p-4 bg-gray-50 rounded-xl border border-gray-100">
                        <div>
                            <span class="text-xs text-gray-400 font-medium block">Kode Undangan Siswa</span>
                            <span class="font-mono text-lg font-extrabold text-gray-900 tracking-widest">{{ $course->invitation_code }}</span>
                        </div>
                        <div class="border-l border-gray-200 pl-4 text-xs text-gray-500 leading-tight">
                            Siswa bergabung ke dalam kelas secara mandiri menggunakan kode ini.
                            @if(!$course->is_active)
                                <div class="text-rose-600 font-semibold mt-1">Pendaftaran siswa ditutup karena kelas dinonaktifkan.</div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 4 Statistik Angka -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-5 border-t border-gray-100 text-center">
                    <div class="p-2">
                        <div class="text-xs text-gray-400 font-medium">Siswa Terdaftar</div>
                        <div class="text-xl font-bold text-gray-900 mt-0.5">{{ $course->students->count() }}</div>
                    </div>
                    <div class="p-2">
                        <div class="text-xs text-gray-400 font-medium">Materi Pelajaran</div>
                        <div class="text-xl font-bold text-gray-900 mt-0.5">{{ $course->materials->count() }}</div>
                    </div>
                    <div class="p-2">
                        <div class="text-xs text-gray-400 font-medium">Kuis & Ujian</div>
                        <div class="text-xl font-bold text-gray-900 mt-0.5">{{ $course->quizzes->count() }}</div>
                    </div>
                    <div class="p-2">
                        <div class="text-xs text-gray-400 font-medium">Sesi Absensi</div>
                        <div class="text-xl font-bold text-gray-900 mt-0.5">{{ $course->attendances->count() }}</div>
                    </div>
                </div>
            </div>

            <!-- Kartu Guru Pengampu (1 Kolom) -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col justify-between">
                <div>
                    <span class="text-xs font-semibold text-primary uppercase tracking-wider">Tenaga Pendidik (Guru)</span>
                    <div class="mt-4 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-700 font-bold text-base flex items-center justify-center shrink-0">
                            {{ strtoupper(substr($course->teacher?->name ?? 'G', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-bold text-gray-900 truncate">{{ $course->teacher?->name ?? 'Guru Tidak Ditemukan' }}</h3>
                            <div class="text-xs font-mono text-gray-500 mt-0.5">NIP: {{ $course->teacher?->identity_number ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="mt-5 space-y-2 text-xs text-gray-600 border-t border-gray-100 pt-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400">Email:</span>
                            <span class="font-medium text-gray-800">{{ $course->teacher?->email ?? 'Tidak ada email' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400">Status Akun Guru:</span>
                            <span class="font-medium {{ $course->teacher?->is_active ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $course->teacher?->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400">Tanggal Dibuat:</span>
                            <span class="font-medium text-gray-800">{{ $course->created_at->format('d F Y') }}</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 mt-4 text-[11px] text-gray-400">
                    Guru bertanggung jawab terhadap penilaian dan materi pada kelas ini.
                </div>
            </div>

        </div>

        <!-- Tabel Siswa Terdaftar -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-gray-900">Daftar Anggota Siswa</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Total {{ $course->students->count() }} siswa bergabung ke dalam mata pelajaran ini.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-100 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 px-4 w-12 text-center">No</th>
                            <th class="py-3 px-4">Nama Siswa</th>
                            <th class="py-3 px-4">Nomor Induk Mahasiswa / Siswa (NIM)</th>
                            <th class="py-3 px-4">Email</th>
                            <th class="py-3 px-4">Status Akun</th>
                            <th class="py-3 px-4 text-right">Bergabung Sejak</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($course->students as $index => $student)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3 px-4 text-center text-xs text-gray-400 font-mono">{{ $index + 1 }}</td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 font-bold text-xs flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($student->name, 0, 1)) }}
                                        </div>
                                        <span class="font-semibold text-gray-900">{{ $student->name }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-mono text-xs font-medium text-gray-700 bg-gray-100 px-2 py-0.5 rounded">
                                        {{ $student->identity_number }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-xs text-gray-500">{{ $student->email ?? '-' }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center gap-1 text-xs font-medium {{ $student->is_active ? 'text-emerald-700' : 'text-rose-700' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $student->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                        {{ $student->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right text-xs text-gray-500 whitespace-nowrap">
                                    {{ $student->pivot->created_at ? \Carbon\Carbon::parse($student->pivot->created_at)->format('d M Y') : 'Terdaftar' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-sm text-gray-400">
                                    Belum ada siswa yang bergabung ke kelas ini. Siswa dapat bergabung menggunakan kode undangan <strong class="font-mono text-gray-700">{{ $course->invitation_code }}</strong>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>

