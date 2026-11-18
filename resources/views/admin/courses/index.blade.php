<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                    ['label' => 'Monitoring Kelas', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Monitoring Kelas Pembelajaran</h1>
                <p class="text-sm text-gray-500 mt-0.5">Pantau seluruh kelas yang diampu guru, kode akses siswa, dan kelola status keaktifan kelas.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1.5 rounded-xl bg-gray-100 text-xs font-semibold text-gray-600">
                    Mode Administrator (Read-Only Materi)
                </span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        <!-- Banner Prinsip Peran Guru & Admin -->
        <div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-4 flex items-start gap-3 text-xs text-indigo-900 leading-relaxed">
            <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
                <strong class="font-semibold text-indigo-950">Catatan Prinsip Sistem:</strong> Tenaga pendidik (Guru) memiliki kewenangan penuh untuk membuat kelas, menyusun topik pertemuan, bahan ajar, dan evaluasi. Administrator bertindak sebagai pemantau integritas, mengelola status operasional, dan meninjau keanggotaan siswa tanpa mengintervensi substansi belajar.
            </div>
        </div>

        <!-- Filter & Pencarian -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <form action="{{ route('admin.courses.index') }}" method="GET" class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                
                <!-- Filter Status Tabs -->
                <div class="flex items-center gap-1 overflow-x-auto text-sm">
                    <a href="{{ route('admin.courses.index', array_merge(request()->except('status'))) }}" class="px-3 py-1.5 rounded-lg font-medium whitespace-nowrap transition-colors {{ !request('status') ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                        Semua Kelas ({{ $counts['all'] }})
                    </a>
                    <a href="{{ route('admin.courses.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" class="px-3 py-1.5 rounded-lg font-medium whitespace-nowrap transition-colors {{ request('status') === 'active' ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                        Aktif ({{ $counts['active'] }})
                    </a>
                    <a href="{{ route('admin.courses.index', array_merge(request()->except('status'), ['status' => 'inactive'])) }}" class="px-3 py-1.5 rounded-lg font-medium whitespace-nowrap transition-colors {{ request('status') === 'inactive' ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                        Nonaktif ({{ $counts['inactive'] }})
                    </a>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <!-- Filter Berdasarkan Guru -->
                    <select name="teacher_id" onchange="this.form.submit()" class="w-full sm:w-48 px-3 py-1.5 text-xs bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Semua Guru Pengampu</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                {{ $teacher->name }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kelas, kode, guru..." class="w-full pl-9 pr-4 py-2 text-xs bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>

                    @if(request('search') || request('teacher_id') || request('status'))
                        <a href="{{ route('admin.courses.index') }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100" title="Hapus seluruh filter">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </a>
                    @endif
                </div>

            </form>
        </div>

        <!-- Tabel Kelas -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-100 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3.5 px-4">Kelas & Subjek</th>
                            <th class="py-3.5 px-4">Guru Pengampu</th>
                            <th class="py-3.5 px-4">Kode Undangan</th>
                            <th class="py-3.5 px-4">Jumlah Siswa</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Dibuat Pada</th>
                            <th class="py-3.5 px-4 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($courses as $course)
                            <tr class="hover:bg-gray-50/50 transition-colors {{ !$course->is_active ? 'bg-slate-50/40 text-slate-500' : '' }}">
                                <!-- Kelas & Deskripsi -->
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-gray-900">{{ $course->title }}</div>
                                    <div class="text-xs text-gray-400 line-clamp-1 max-w-xs">{{ $course->description ?: 'Tidak ada deskripsi' }}</div>
                                </td>

                                <!-- Guru Pengampu -->
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-gray-900">{{ $course->teacher?->name ?? 'Belum ditentukan' }}</div>
                                    <div class="text-xs text-gray-400 font-mono">NIP: {{ $course->teacher?->identity_number ?? '-' }}</div>
                                </td>

                                <!-- Kode Undangan -->
                                <td class="py-3.5 px-4">
                                    <span class="font-mono text-xs font-bold text-gray-800 bg-gray-100 px-2.5 py-1 rounded-lg tracking-wider border border-gray-200">
                                        {{ $course->invitation_code }}
                                    </span>
                                </td>

                                <!-- Jumlah Siswa -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-1.5 font-medium text-gray-800">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                        <span>{{ $course->students_count }} Siswa</span>
                                    </div>
                                    <div class="text-[11px] text-gray-400 mt-0.5">{{ $course->materials_count }} materi • {{ $course->quizzes_count }} aktivitas</div>
                                </td>

                                <!-- Status Aktif -->
                                <td class="py-3.5 px-4">
                                    @if($course->is_active)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            Dinonaktifkan
                                        </span>
                                    @endif
                                </td>

                                <!-- Dibuat Pada -->
                                <td class="py-3.5 px-4 text-xs text-gray-500 whitespace-nowrap">
                                    {{ $course->created_at->format('d M Y') }}
                                </td>

                                <!-- Tindakan Admin -->
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Tombol Detail Anggota -->
                                        <a href="{{ route('admin.courses.show', $course->id) }}" class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-colors shadow-2xs">
                                            <svg class="w-3.5 h-3.5 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            Detail & Anggota
                                        </a>

                                        <!-- Tombol Toggle Status -->
                                        <form action="{{ route('admin.courses.toggle-status', $course->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin {{ $course->is_active ? 'menonaktifkan' : 'mengaktifkan kembali' }} kelas {{ $course->title }}?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg transition-colors border {{ $course->is_active ? 'border-amber-200 text-amber-700 bg-amber-50 hover:bg-amber-100' : 'border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                                                {{ $course->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-500 text-sm">
                                    Tidak ada data kelas yang cocok dengan kriteria filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($courses->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $courses->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>

