<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                    ['label' => 'Manajemen Akademik', 'url' => null],
                    ['label' => 'Penempatan Siswa', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Daftar Siswa & Penempatan Kelas</h1>
                <p class="text-sm text-gray-500 mt-0.5">Pantau status penempatan kelas setiap siswa dan riwayat perpindahan per tahun ajaran.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.academic.classes.index') }}" class="px-3.5 py-2.5 bg-white text-gray-700 text-xs font-semibold rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors shadow-2xs">
                    Kelola Rombel
                </a>
                <a href="{{ route('admin.users.import') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-primary text-white text-xs font-semibold rounded-xl hover:bg-[#357ebd] transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span>Import Massal Siswa</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Metrik Penempatan -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 text-primary flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Siswa Terdaftar</p>
                    <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ $counts['total_students'] ?? 0 }} <span class="text-xs font-normal text-gray-400">akun</span></p>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Sudah Ditempatkan</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-0.5">{{ $counts['placed'] ?? 0 }} <span class="text-xs font-normal text-gray-400">siswa</span></p>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Belum Memiliki Kelas</p>
                    <p class="text-2xl font-bold text-rose-600 mt-0.5">{{ $counts['unplaced'] ?? 0 }} <span class="text-xs font-normal text-gray-400">siswa</span></p>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs p-4">
            <form action="{{ route('admin.academic.students.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search -->
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau NIM..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <!-- Filter Tahun Ajaran -->
                <div>
                    <select name="academic_year_id" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $selectedYearId == $ay->id ? 'selected' : '' }}>
                                Tahun: {{ $ay->name }} ({{ $ay->semester }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Status Penempatan -->
                <div>
                    <select name="placement_status" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        <option value="">Semua Status Penempatan</option>
                        <option value="placed" {{ request('placement_status') === 'placed' ? 'selected' : '' }}>Sudah Ditempatkan</option>
                        <option value="unplaced" {{ request('placement_status') === 'unplaced' ? 'selected' : '' }}>Belum Ditempatkan</option>
                    </select>
                </div>

                <!-- Filter Kelas -->
                <div class="flex items-center gap-2">
                    <select name="class_id" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        <option value="">Semua Kelas Rombel</option>
                        @foreach($classes as $cls)
                            <option value="{{ $cls->id }}" {{ request('class_id') == $cls->id ? 'selected' : '' }}>{{ $cls->name }}</option>
                        @endforeach
                    </select>
                    @if(request()->hasAny(['search', 'placement_status', 'class_id']))
                        <a href="{{ route('admin.academic.students.index', ['academic_year_id' => $selectedYearId]) }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100" title="Reset filter">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Tabel Siswa & Penempatan -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-200 text-gray-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4 w-12">No</th>
                            <th class="py-3 px-4">Nama Siswa</th>
                            <th class="py-3 px-4">NIM</th>
                            <th class="py-3 px-4">Email</th>
                            <th class="py-3 px-4">Kelas Aktif</th>
                            <th class="py-3 px-4">Riwayat Kelas Sebelumnya</th>
                            <th class="py-3 px-4 text-center">Status Akun</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($students as $idx => $st)
                            @php
                                $activeHist = $st->activeClassHistory;
                                $pastHists = $st->classHistories->where('status', '!=', 'active');
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3 px-4 text-gray-400 font-mono">{{ $students->firstItem() + $idx }}</td>
                                <td class="py-3 px-4 font-semibold text-gray-900">{{ $st->name }}</td>
                                <td class="py-3 px-4 font-mono font-medium text-gray-700">{{ $st->identity_number }}</td>
                                <td class="py-3 px-4 text-gray-500">{{ $st->email ?? '-' }}</td>
                                <td class="py-3 px-4">
                                    @if($activeHist && $activeHist->schoolClass)
                                        <a href="{{ route('admin.academic.classes.show', $activeHist->school_class_id) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition-colors">
                                            {{ $activeHist->schoolClass->name }}
                                        </a>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-700 bg-rose-50 px-2.5 py-0.5 rounded-full border border-rose-100">
                                            Belum Ada Kelas
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-gray-500">
                                    @if($pastHists->isNotEmpty())
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($pastHists as $ph)
                                                <span class="inline-block text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded" title="{{ $ph->notes }}">
                                                    {{ $ph->schoolClass->name ?? 'Kelas' }} ({{ $ph->academicYear->name ?? '' }})
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-400 italic text-[11px]">Tidak ada</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $st->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                        {{ $st->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-400">
                                    Tidak ada data siswa ditemukan dengan kriteria filter saat ini.
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
    </div>
</x-app-layout>
