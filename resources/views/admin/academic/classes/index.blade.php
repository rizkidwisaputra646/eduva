<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                    ['label' => 'Manajemen Akademik', 'url' => null],
                    ['label' => 'Manajemen Kelas', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Manajemen Kelas Rombel</h1>
                <p class="text-sm text-gray-500 mt-0.5">Kelola rombongan belajar, wali kelas, penempatan siswa, dan tahun ajaran.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.academic.students.index') }}" class="px-3.5 py-2.5 bg-white text-gray-700 text-xs font-semibold rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors shadow-2xs">
                    Penempatan Siswa
                </a>
                <button type="button" x-data @click="$dispatch('open-modal', 'add-class-modal')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-[#357ebd] text-white text-xs font-semibold rounded-xl shadow-sm transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Buat Kelas Baru</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Statistik Cepat -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 text-primary flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Kelas</p>
                    <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ $stats['total_classes'] ?? 0 }} <span class="text-xs font-normal text-gray-400">rombel</span></p>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Kelas Aktif</p>
                    <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ $stats['active_classes'] ?? 0 }} <span class="text-xs font-normal text-gray-400">kelas</span></p>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-purple-50 border border-purple-100 text-purple-600 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Siswa Ditempatkan</p>
                    <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ $stats['total_students_placed'] ?? 0 }} <span class="text-xs font-normal text-gray-400">siswa</span></p>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs p-4">
            <form action="{{ route('admin.academic.classes.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Search -->
                <div class="relative lg:col-span-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama kelas atau wali kelas..." class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary outline-hidden">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <!-- Filter Tahun Ajaran -->
                <div>
                    <select name="academic_year_id" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        <option value="">Semua Tahun Ajaran</option>
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>
                                {{ $ay->name }} ({{ $ay->semester }}) {{ $ay->is_active ? '★' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Tingkat -->
                <div>
                    <select name="level" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        <option value="">Semua Tingkat</option>
                        <option value="10" {{ request('level') == '10' ? 'selected' : '' }}>Kelas 10</option>
                        <option value="11" {{ request('level') == '11' ? 'selected' : '' }}>Kelas 11</option>
                        <option value="12" {{ request('level') == '12' ? 'selected' : '' }}>Kelas 12</option>
                    </select>
                </div>

                <!-- Filter Status -->
                <div class="flex items-center gap-2">
                    <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Diarsipkan</option>
                    </select>
                    @if(request()->hasAny(['search', 'academic_year_id', 'level', 'status']))
                        <a href="{{ route('admin.academic.classes.index') }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100" title="Reset filter">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Grid Kelas Rombel -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($classes as $c)
                <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between overflow-hidden group">
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase {{ $c->level == '12' ? 'bg-amber-50 text-amber-800 border border-amber-200' : ($c->level == '11' ? 'bg-blue-50 text-blue-800 border border-blue-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200') }}">
                                    Tingkat {{ $c->level ?? '-' }} • {{ $c->department ?? 'Umum' }}
                                </span>
                                <h3 class="text-lg font-bold text-gray-900 mt-2 group-hover:text-primary transition-colors">
                                    <a href="{{ route('admin.academic.classes.show', $c->id) }}">{{ $c->name }}</a>
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $c->academicYear ? $c->academicYear->name . ' (' . $c->academicYear->semester . ')' : 'Tanpa Tahun Ajaran' }}
                                </p>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $c->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $c->status === 'active' ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                {{ ucfirst($c->status) }}
                            </span>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-100 space-y-2 text-xs">
                            <div class="flex items-center justify-between text-gray-600">
                                <span class="text-gray-400">Wali Kelas:</span>
                                <span class="font-semibold text-gray-800 truncate max-w-[170px]">{{ $c->homeroomTeacher->name ?? 'Belum Ditentukan' }}</span>
                            </div>
                            <div class="flex items-center justify-between text-gray-600">
                                <span class="text-gray-400">Jumlah Siswa:</span>
                                <span class="font-bold text-gray-900 bg-gray-50 px-2.5 py-0.5 rounded-lg border border-gray-100">
                                    {{ $c->students_count }} Siswa
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-gray-50/70 border-t border-gray-100 flex items-center justify-between gap-2 text-xs">
                        <form action="{{ route('admin.academic.classes.archive', $c->id) }}" method="POST" onsubmit="return confirm('Ubah status kelas {{ $c->name }}?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-2.5 py-1.5 text-gray-500 hover:text-gray-800 font-medium hover:bg-gray-100 rounded-lg transition-colors">
                                {{ $c->status === 'archived' ? 'Buka Arsip' : 'Arsipkan' }}
                            </button>
                        </form>

                        <a href="{{ route('admin.academic.classes.show', $c->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary text-white font-semibold rounded-lg hover:bg-[#357ebd] transition-colors shadow-2xs">
                            <span>Kelola Kelas</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-2xl border border-gray-200 p-12 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    <p class="font-semibold text-gray-700">Belum ada kelas rombel ditemukan</p>
                    <p class="text-xs text-gray-400 mt-1">Gunakan tombol "Buat Kelas Baru" di atas untuk menambahkan kelas pertama.</p>
                </div>
            @endforelse
        </div>

        @if($classes->hasPages())
            <div class="p-4 bg-white rounded-2xl border border-gray-200">
                {{ $classes->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Tambah Kelas Baru -->
    <x-modal name="add-class-modal" maxWidth="md" focusable>
        <form method="POST" action="{{ route('admin.academic.classes.store') }}" class="p-6">
            @csrf
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                <h3 class="text-base font-bold text-gray-900">Buat Kelas Rombel Baru</h3>
                <button type="button" @click="$dispatch('close-modal', 'add-class-modal')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Nama Kelas <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Bebas, contoh: 12 IPA A, 10 IPS B, X RPL 1" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs">
                    <p class="text-[11px] text-gray-400 mt-1">Anda bebas menentukan format nama kelas sesuai kebutuhan sekolah.</p>
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Tahun Ajaran <span class="text-rose-500">*</span></label>
                    <select name="academic_year_id" required class="w-full px-3 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ ($activeYear && $activeYear->id === $ay->id) ? 'selected' : '' }}>
                                {{ $ay->name }} ({{ $ay->semester }}) {{ $ay->is_active ? '— Aktif' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Tingkat Kelas</label>
                        <input type="text" name="level" placeholder="10, 11, atau 12" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Jurusan / Program</label>
                        <input type="text" name="department" placeholder="IPA, IPS, dll" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Wali Kelas (Opsional)</label>
                    <select name="homeroom_teacher_id" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs">
                        <option value="">-- Belum Ditentukan --</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}">{{ $t->name }} (NIP: {{ $t->identity_number ?? '-' }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-end gap-2">
                <button type="button" @click="$dispatch('close-modal', 'add-class-modal')" class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold text-xs rounded-xl hover:bg-gray-200 transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-primary text-white font-semibold text-xs rounded-xl hover:bg-[#357ebd] transition-colors shadow-sm">
                    Simpan Kelas
                </button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
