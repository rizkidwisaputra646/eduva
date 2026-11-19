<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                    ['label' => 'Manajemen Kelas', 'url' => route('admin.academic.classes.index')],
                    ['label' => $schoolClass->name, 'url' => null]
                ]" />
                <div class="flex items-center gap-3 mt-1">
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ $schoolClass->name }}</h1>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $schoolClass->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $schoolClass->status === 'active' ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                        {{ ucfirst($schoolClass->status) }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-0.5">
                    Tahun Ajaran: {{ $schoolClass->academicYear ? $schoolClass->academicYear->name . ' (' . $schoolClass->academicYear->semester . ')' : 'Belum diatur' }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" x-data @click="$dispatch('open-modal', 'edit-class-modal')" class="px-3.5 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-2xs">
                    Edit Kelas
                </button>
                <button type="button" x-data @click="$dispatch('open-modal', 'add-students-modal')" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary hover:bg-[#357ebd] text-white text-xs font-semibold rounded-xl shadow-sm transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    <span>Tambahkan Siswa</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Informasi Ringkas Kelas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Wali Kelas</span>
                <p class="text-base font-bold text-gray-900 mt-1">{{ $schoolClass->homeroomTeacher->name ?? 'Belum Ditentukan' }}</p>
                <p class="text-xs text-gray-500 mt-0.5">NIP: {{ $schoolClass->homeroomTeacher->identity_number ?? '-' }}</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Tingkat & Jurusan</span>
                <p class="text-base font-bold text-gray-900 mt-1">Kelas {{ $schoolClass->level ?? '-' }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Program: {{ $schoolClass->department ?? 'Umum' }}</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Siswa Aktif</span>
                <p class="text-2xl font-bold text-primary mt-1">{{ $activeHistories->total() }} <span class="text-xs font-normal text-gray-400">Siswa</span></p>
                <p class="text-xs text-gray-500 mt-0.5">Terdaftar pada rombel ini</p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-2xs">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Mata Pelajaran Terhubung</span>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $schoolClass->courses->count() }} <span class="text-xs font-normal text-gray-400">Mapel</span></p>
                <p class="text-xs text-gray-500 mt-0.5">Aktivitas pembelajaran</p>
            </div>
        </div>

        <!-- Tabel Siswa Terdaftar -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <h2 class="font-bold text-gray-900 text-sm">Daftar Siswa Terdaftar ({{ $activeHistories->total() }})</h2>
                <form action="{{ route('admin.academic.classes.show', $schoolClass->id) }}" method="GET" class="relative w-full sm:w-64">
                    <input type="text" name="student_search" value="{{ request('student_search') }}" placeholder="Cari nama atau NIM..." class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-200 text-gray-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4 w-12">No</th>
                            <th class="py-3 px-4">Nama Siswa</th>
                            <th class="py-3 px-4">NIM</th>
                            <th class="py-3 px-4">Email</th>
                            <th class="py-3 px-4">Tanggal Masuk</th>
                            <th class="py-3 px-4 text-center">Status Akun</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($activeHistories as $idx => $hist)
                            @php $student = $hist->user; @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3 px-4 text-gray-400 font-mono">{{ $activeHistories->firstItem() + $idx }}</td>
                                <td class="py-3 px-4 font-semibold text-gray-900">{{ $student->name }}</td>
                                <td class="py-3 px-4 font-mono font-medium text-gray-700">{{ $student->identity_number }}</td>
                                <td class="py-3 px-4 text-gray-500">{{ $student->email ?? '-' }}</td>
                                <td class="py-3 px-4 text-gray-600">{{ $hist->assigned_at ? $hist->assigned_at->format('d/m/Y') : '-' }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $student->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                        {{ $student->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5" x-data>
                                        <!-- Tombol Pindahkan Siswa -->
                                        <button type="button" @click="$dispatch('open-modal', 'move-student-modal-{{ $student->id }}')" class="px-2.5 py-1 text-[11px] font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors cursor-pointer" title="Pindahkan ke kelas lain">
                                            Pindahkan
                                        </button>

                                        <!-- Tombol Keluarkan Siswa -->
                                        <form action="{{ route('admin.academic.classes.students.remove', [$schoolClass->id, $student->id]) }}" method="POST" onsubmit="return confirm('Keluarkan siswa {{ $student->name }} dari kelas {{ $schoolClass->name }}? Akun siswa tidak akan terhapus.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1 text-[11px] font-medium text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer" title="Keluarkan dari kelas">
                                                Keluarkan
                                            </button>
                                        </form>

                                        <!-- Modal Pindah Kelas Siswa Ini -->
                                        <x-modal name="move-student-modal-{{ $student->id }}" maxWidth="sm" focusable>
                                            <form method="POST" action="{{ route('admin.academic.classes.students.move', [$schoolClass->id, $student->id]) }}" class="p-5 text-left">
                                                @csrf
                                                <h3 class="text-sm font-bold text-gray-900 pb-2 border-b border-gray-100 mb-3">
                                                    Pindahkan {{ $student->name }}
                                                </h3>
                                                <p class="text-xs text-gray-500 mb-3">
                                                    Pindahkan siswa dari rombel <strong>{{ $schoolClass->name }}</strong> ke kelas tujuan baru.
                                                </p>

                                                <div class="space-y-3 text-xs">
                                                    <div>
                                                        <label class="block font-semibold text-gray-700 mb-1">Kelas Tujuan <span class="text-rose-500">*</span></label>
                                                        <select name="target_class_id" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs">
                                                            <option value="">-- Pilih Kelas Tujuan --</option>
                                                            @foreach($otherClasses as $oc)
                                                                <option value="{{ $oc->id }}">{{ $oc->name }} ({{ $oc->level }})</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label class="block font-semibold text-gray-700 mb-1">Alasan / Catatan Perpindahan</label>
                                                        <textarea name="notes" rows="2" placeholder="Contoh: Permintaan wali murid, penyesuaian jurusan" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs"></textarea>
                                                    </div>
                                                </div>

                                                <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
                                                    <button type="button" @click="$dispatch('close-modal', 'move-student-modal-{{ $student->id }}')" class="px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg">
                                                        Batal
                                                    </button>
                                                    <button type="submit" class="px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-lg hover:bg-[#357ebd]">
                                                        Konfirmasi Pindah
                                                    </button>
                                                </div>
                                            </form>
                                        </x-modal>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-gray-400">
                                    Belum ada siswa terdaftar di kelas ini. Klik tombol "Tambahkan Siswa" untuk menempatkan siswa.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($activeHistories->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $activeHistories->links() }}
                </div>
            @endif
        </div>

        <!-- Riwayat Siswa Pindah / Keluar -->
        @if($pastHistories->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs p-5">
            <h3 class="font-bold text-gray-900 text-sm mb-3">Riwayat Siswa yang Pernah Berpindah / Dikeluarkan</h3>
            <div class="space-y-2">
                @foreach($pastHistories as $ph)
                    <div class="p-3 bg-gray-50/70 rounded-xl border border-gray-100 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-semibold text-gray-800">{{ $ph->user->name ?? 'Pengguna' }}</span>
                            <span class="text-gray-400 font-mono ml-1">({{ $ph->user->identity_number ?? '-' }})</span>
                            <p class="text-[11px] text-gray-500 mt-0.5">{{ $ph->notes ?: 'Tidak ada catatan perpindahan' }}</p>
                        </div>
                        <div class="text-right text-gray-400 text-[11px]">
                            {{ $ph->moved_at ? $ph->moved_at->format('d M Y, H:i') : '-' }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Modal Tambahkan Siswa ke Kelas Ini -->
    <x-modal name="add-students-modal" maxWidth="lg" focusable>
        <form method="POST" action="{{ route('admin.academic.classes.students.add', $schoolClass->id) }}" class="p-6">
            @csrf
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Tambahkan Siswa ke {{ $schoolClass->name }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Pilih siswa yang belum ditempatkan pada tahun ajaran ini.</p>
                </div>
                <button type="button" @click="$dispatch('close-modal', 'add-students-modal')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="space-y-3" x-data="{ search: '' }">
                <div class="relative">
                    <input type="text" x-model="search" placeholder="Cari nama atau NIM siswa..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <div class="border border-gray-200 rounded-xl p-2 max-h-64 overflow-y-auto divide-y divide-gray-100 text-xs">
                    @forelse($availableStudents as $s)
                        <label class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg cursor-pointer transition-colors" x-show="search === '' || '{{ strtolower($s->name) }}'.includes(search.toLowerCase()) || '{{ $s->identity_number }}'.includes(search)">
                            <div class="flex items-center gap-2.5">
                                <input type="checkbox" name="student_ids[]" value="{{ $s->id }}" class="rounded border-gray-300 text-primary focus:ring-primary">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $s->name }}</p>
                                    <p class="text-[11px] text-gray-500 font-mono">{{ $s->identity_number }}</p>
                                </div>
                            </div>
                            <span class="text-[10px] bg-blue-50 text-blue-700 px-2 py-0.5 rounded font-medium">Siswa</span>
                        </label>
                    @empty
                        <div class="p-6 text-center text-gray-400">
                            Semua akun siswa sudah memiliki penempatan kelas pada tahun ajaran ini.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-end gap-2">
                <button type="button" @click="$dispatch('close-modal', 'add-students-modal')" class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold text-xs rounded-xl hover:bg-gray-200 transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-primary text-white font-semibold text-xs rounded-xl hover:bg-[#357ebd] transition-colors shadow-sm">
                    Simpan Penempatan Siswa
                </button>
            </div>
        </form>
    </x-modal>

    <!-- Modal Edit Informasi Kelas -->
    <x-modal name="edit-class-modal" maxWidth="md" focusable>
        <form method="POST" action="{{ route('admin.academic.classes.update', $schoolClass->id) }}" class="p-6">
            @csrf
            @method('PUT')
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                <h3 class="text-base font-bold text-gray-900">Edit Kelas Rombel</h3>
                <button type="button" @click="$dispatch('close-modal', 'edit-class-modal')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Nama Kelas <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ $schoolClass->name }}" required class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Tahun Ajaran <span class="text-rose-500">*</span></label>
                    <select name="academic_year_id" required class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ $schoolClass->academic_year_id == $ay->id ? 'selected' : '' }}>
                                {{ $ay->name }} ({{ $ay->semester }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Tingkat</label>
                        <input type="text" name="level" value="{{ $schoolClass->level }}" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Jurusan</label>
                        <input type="text" name="department" value="{{ $schoolClass->department }}" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Wali Kelas</label>
                    <select name="homeroom_teacher_id" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs">
                        <option value="">-- Belum Ditentukan --</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" {{ $schoolClass->homeroom_teacher_id == $t->id ? 'selected' : '' }}>
                                {{ $t->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Status Kelas</label>
                    <select name="status" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs">
                        <option value="active" {{ $schoolClass->status === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="archived" {{ $schoolClass->status === 'archived' ? 'selected' : '' }}>Diarsipkan</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-end gap-2">
                <button type="button" @click="$dispatch('close-modal', 'edit-class-modal')" class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold text-xs rounded-xl">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-primary text-white font-semibold text-xs rounded-xl hover:bg-[#357ebd]">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
