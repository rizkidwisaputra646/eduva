<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                    ['label' => 'Manajemen Akademik', 'url' => null],
                    ['label' => 'Tahun Ajaran', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Pengelolaan Tahun Ajaran</h1>
                <p class="text-sm text-gray-500 mt-0.5">Atur periode akademik aktif, semester berjalan, dan pengarsipan tahun ajaran sekolah.</p>
            </div>
            <div>
                <button type="button" x-data @click="$dispatch('open-modal', 'add-year-modal')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-[#357ebd] text-white text-xs font-semibold rounded-xl shadow-sm transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Tambah Tahun Ajaran</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Banner Tahun Ajaran Aktif -->
        @if($activeYear)
        <div class="p-5 rounded-2xl bg-gradient-to-r from-blue-50 to-indigo-50/50 border border-blue-200/60 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-primary text-white flex items-center justify-center font-bold text-lg shadow-sm shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-primary">Tahun Ajaran Aktif Saat Ini</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800">AKTIF</span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mt-0.5">{{ $activeYear->name }} — Semester {{ $activeYear->semester }}</h2>
                    <p class="text-xs text-gray-600 mt-0.5">
                        Periode: {{ $activeYear->start_date ? $activeYear->start_date->format('d M Y') : 'Belum diatur' }} s/d {{ $activeYear->end_date ? $activeYear->end_date->format('d M Y') : 'Belum diatur' }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <a href="{{ route('admin.academic.classes.index', ['academic_year_id' => $activeYear->id]) }}" class="px-3.5 py-2 bg-white text-gray-700 font-semibold border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-2xs">
                    Lihat Kelas Terdaftar
                </a>
            </div>
        </div>
        @endif

        <!-- Filter & Search -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs p-4">
            <form action="{{ route('admin.academic.years.index') }}" method="GET" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.academic.years.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ !request('status') ? 'bg-primary text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                        Semua
                    </a>
                    <a href="{{ route('admin.academic.years.index', ['status' => 'active']) }}" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request('status') === 'active' ? 'bg-primary text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                        Aktif
                    </a>
                    <a href="{{ route('admin.academic.years.index', ['status' => 'archived']) }}" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request('status') === 'archived' ? 'bg-primary text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                        Diarsipkan
                    </a>
                </div>
                <div class="relative w-full md:w-64">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau semester..." class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary outline-hidden">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </form>
        </div>

        <!-- Tabel Tahun Ajaran -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-200 text-gray-500 font-semibold uppercase tracking-wider">
                            <th class="py-3.5 px-5">Tahun Ajaran</th>
                            <th class="py-3.5 px-5">Semester</th>
                            <th class="py-3.5 px-5">Rentang Tanggal</th>
                            <th class="py-3.5 px-5 text-center">Jumlah Kelas</th>
                            <th class="py-3.5 px-5 text-center">Status</th>
                            <th class="py-3.5 px-5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($years as $yr)
                            <tr class="hover:bg-gray-50/60 transition-colors {{ $yr->is_active ? 'bg-blue-50/20' : '' }}">
                                <td class="py-4 px-5">
                                    <div class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                        <span>{{ $yr->name }}</span>
                                        @if($yr->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">AKTIF BERJALAN</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-4 px-5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ $yr->semester === 'Ganjil' ? 'bg-blue-50 text-blue-700 border border-blue-100' : 'bg-purple-50 text-purple-700 border border-purple-100' }}">
                                        Semester {{ $yr->semester }}
                                    </span>
                                </td>
                                <td class="py-4 px-5 text-gray-600">
                                    @if($yr->start_date && $yr->end_date)
                                        {{ $yr->start_date->format('d/m/Y') }} – {{ $yr->end_date->format('d/m/Y') }}
                                    @else
                                        <span class="text-gray-400 italic">Belum ditentukan</span>
                                    @endif
                                </td>
                                <td class="py-4 px-5 text-center font-bold text-gray-800">
                                    {{ $yr->classes_count ?? 0 }} <span class="font-normal text-gray-400">kelas</span>
                                </td>
                                <td class="py-4 px-5 text-center">
                                    @if($yr->status === 'active')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif
                                        </span>
                                    @elseif($yr->status === 'archived')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                            Diarsipkan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-100">
                                            Mendatang
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if(!$yr->is_active)
                                        <form action="{{ route('admin.academic.years.set-active', $yr->id) }}" method="POST" onsubmit="return confirm('Tetapkan tahun ajaran {{ $yr->name }} ({{ $yr->semester }}) sebagai aktif?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors" title="Jadikan Tahun Ajaran Aktif">
                                                Aktifkan
                                            </button>
                                        </form>
                                        @endif

                                        <form action="{{ route('admin.academic.years.archive', $yr->id) }}" method="POST" onsubmit="return confirm('Ubah status arsip tahun ajaran {{ $yr->name }}?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-2.5 py-1.5 text-xs font-medium rounded-lg text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">
                                                {{ $yr->status === 'archived' ? 'Buka Arsip' : 'Arsipkan' }}
                                            </button>
                                        </form>

                                        @if(!$yr->is_active && $yr->classes_count == 0)
                                        <form action="{{ route('admin.academic.years.destroy', $yr->id) }}" method="POST" onsubmit="return confirm('Hapus permanen tahun ajaran {{ $yr->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1.5 text-xs font-medium rounded-lg text-rose-600 hover:bg-rose-50 transition-colors" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-400">
                                    Belum ada data tahun ajaran. Silakan tambahkan tahun ajaran baru.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($years->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $years->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Tambah Tahun Ajaran -->
    <x-modal name="add-year-modal" maxWidth="md" focusable>
        <form method="POST" action="{{ route('admin.academic.years.store') }}" class="p-6">
            @csrf
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                <h3 class="text-base font-bold text-gray-900">Tambah Tahun Ajaran Baru</h3>
                <button type="button" @click="$dispatch('close-modal', 'add-year-modal')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Nama Tahun Ajaran <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: 2026/2027" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Semester <span class="text-rose-500">*</span></label>
                    <select name="semester" required class="w-full px-3 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs">
                        <option value="Ganjil">Semester Ganjil</option>
                        <option value="Genap">Semester Genap</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Tanggal Mulai</label>
                        <input type="date" name="start_date" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Tanggal Selesai</label>
                        <input type="date" name="end_date" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs">
                    </div>
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-primary focus:ring-primary">
                        <span class="font-medium text-gray-700">Jadikan sebagai tahun ajaran aktif sekarang</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-end gap-2">
                <button type="button" @click="$dispatch('close-modal', 'add-year-modal')" class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold text-xs rounded-xl hover:bg-gray-200 transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-primary text-white font-semibold text-xs rounded-xl hover:bg-[#357ebd] transition-colors shadow-sm">
                    Simpan Tahun Ajaran
                </button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
