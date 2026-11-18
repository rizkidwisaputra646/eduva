<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                    ['label' => 'Manajemen Pengguna', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Manajemen Pengguna</h1>
                <p class="text-sm text-gray-500 mt-0.5">Kelola data identitas Guru (NIP) dan Siswa (NIM), aktivasi akun, dan atur ulang kredensial.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.users.import') }}" class="inline-flex items-center px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-colors shadow-sm">
                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Import Massal (CSV)
                </a>
                <button type="button" x-data="" @click="$dispatch('open-modal', 'create-user-modal')" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary rounded-xl hover:bg-primary/90 transition-colors shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Tambah Pengguna
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{ editingUser: null }">

        <!-- Tabs & Filter Bar -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                
                <!-- Tab Buttons -->
                <div class="flex items-center gap-1 overflow-x-auto pb-2 lg:pb-0 text-sm">
                    <a href="{{ route('admin.users.index') }}" class="px-3 py-1.5 rounded-lg font-medium whitespace-nowrap transition-colors {{ !request('role') && !request('status') && !request('password_status') ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                        Semua ({{ $counts['all'] }})
                    </a>
                    <a href="{{ route('admin.users.index', ['role' => 'guru']) }}" class="px-3 py-1.5 rounded-lg font-medium whitespace-nowrap transition-colors {{ request('role') === 'guru' ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                        Guru / NIP ({{ $counts['guru'] }})
                    </a>
                    <a href="{{ route('admin.users.index', ['role' => 'siswa']) }}" class="px-3 py-1.5 rounded-lg font-medium whitespace-nowrap transition-colors {{ request('role') === 'siswa' ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                        Siswa / NIM ({{ $counts['siswa'] }})
                    </a>
                    <a href="{{ route('admin.users.index', ['status' => 'inactive']) }}" class="px-3 py-1.5 rounded-lg font-medium whitespace-nowrap transition-colors {{ request('status') === 'inactive' ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                        Nonaktif ({{ $counts['inactive'] }})
                    </a>
                    <a href="{{ route('admin.users.index', ['password_status' => 'default']) }}" class="px-3 py-1.5 rounded-lg font-medium whitespace-nowrap transition-colors {{ request('password_status') === 'default' ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                        Perlu Ganti Password ({{ $counts['default_password'] }})
                    </a>
                </div>

                <!-- Search Input -->
                <form action="{{ route('admin.users.index') }}" method="GET" class="flex items-center gap-2">
                    @if(request('role')) <input type="hidden" name="role" value="{{ request('role') }}"> @endif
                    @if(request('status')) <input type="hidden" name="status" value="{{ request('status') }}"> @endif
                    @if(request('password_status')) <input type="hidden" name="password_status" value="{{ request('password_status') }}"> @endif

                    <div class="relative w-full sm:w-64">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIM, NIP..." class="w-full pl-9 pr-4 py-2 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>

                    @if(request('search'))
                        <a href="{{ route('admin.users.index') }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100" title="Hapus filter pencarian">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </a>
                    @endif
                </form>

            </div>
        </div>

        <!-- Tabel Pengguna -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-100 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3.5 px-4">Pengguna</th>
                            <th class="py-3.5 px-4">Nomor Identitas (NIM/NIP)</th>
                            <th class="py-3.5 px-4">Peran</th>
                            <th class="py-3.5 px-4">Status Akun</th>
                            <th class="py-3.5 px-4">Status Password</th>
                            <th class="py-3.5 px-4">Terdaftar</th>
                            <th class="py-3.5 px-4 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-50/50 transition-colors {{ !$user->is_active ? 'bg-slate-50/40 text-slate-500' : '' }}">
                                <!-- Kolom Pengguna -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shrink-0 {{ $user->role === 'guru' ? 'bg-indigo-100 text-indigo-700' : ($user->role === 'siswa' ? 'bg-blue-100 text-blue-700' : 'bg-slate-800 text-white') }}">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-gray-900 truncate">{{ $user->name }}</div>
                                            <div class="text-xs text-gray-400 truncate">{{ $user->email ?? 'Tidak ada email' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kolom Identitas (NIM/NIP) -->
                                <td class="py-3.5 px-4">
                                    <div class="font-mono text-xs font-semibold text-gray-700 bg-gray-100 px-2 py-0.5 rounded inline-block">
                                        {{ $user->identity_number }}
                                    </div>
                                </td>

                                <!-- Kolom Peran -->
                                <td class="py-3.5 px-4">
                                    @if($user->role === 'guru')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            Guru (NIP)
                                        </span>
                                    @elseif($user->role === 'siswa')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                            Siswa (NIM)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-900 text-white">
                                            Administrator
                                        </span>
                                    @endif
                                </td>

                                <!-- Kolom Status Akun -->
                                <td class="py-3.5 px-4">
                                    @if($user->is_active)
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <!-- Kolom Status Password -->
                                <td class="py-3.5 px-4">
                                    @if($user->must_change_password)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                            Default (Perlu Ganti)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-50 text-slate-600">
                                            Kustom (Aman)
                                        </span>
                                    @endif
                                </td>

                                <!-- Kolom Terdaftar -->
                                <td class="py-3.5 px-4 text-xs text-gray-500 whitespace-nowrap">
                                    {{ $user->created_at->format('d M Y') }}
                                </td>

                                <!-- Kolom Tindakan -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        
                                        <!-- Tombol Edit Data Pengguna -->
                                        <button type="button" @click="editingUser = {{ json_encode($user) }}; $dispatch('open-modal', 'edit-user-modal')" class="p-1.5 text-gray-500 hover:text-gray-800 rounded-lg hover:bg-gray-100 transition-colors" title="Edit Data Pengguna">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>

                                        <!-- Tombol Toggle Aktif/Nonaktif -->
                                        @if($user->id !== Auth::id())
                                            <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin {{ $user->is_active ? 'menonaktifkan' : 'mengaktifkan' }} akun {{ $user->name }}?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="p-1.5 {{ $user->is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }} rounded-lg transition-colors" title="{{ $user->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}">
                                                    @if($user->is_active)
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                    @else
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    @endif
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Tombol Reset Password -->
                                        <form action="{{ route('admin.users.reset-password', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Kembalikan password akun {{ $user->name }} ke default (eduva123@)?');">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Reset Password ke Default">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                            </button>
                                        </form>

                                        <!-- Tombol Hapus (Kecuali Akun Sendiri) -->
                                        @if($user->id !== Auth::id())
                                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus permanen akun {{ $user->name }}? Tindakan ini tidak dapat dibatalkan.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus Pengguna">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        @endif

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-500 text-sm">
                                    Tidak ada data pengguna yang sesuai dengan kriteria filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

        <!-- MODAL TAMBAH PENGGUNA -->
        <x-modal name="create-user-modal" focusable>
            <form action="{{ route('admin.store') }}" method="POST" class="p-6">
                @csrf
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Tambah Akun Pengguna</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Daftarkan tenaga pendidik atau peserta didik baru.</p>
                    </div>
                    <button type="button" @click="$dispatch('close')" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="space-y-4 my-5 text-sm">
                    <!-- Peran Akun -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Peran Akun *</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative flex items-center p-3 border border-gray-200 rounded-xl cursor-pointer hover:border-primary/50 transition-colors">
                                <input type="radio" name="role" value="siswa" checked class="text-primary focus:ring-primary h-4 w-4">
                                <div class="ml-3">
                                    <span class="block text-sm font-semibold text-gray-900">Siswa</span>
                                    <span class="block text-xs text-gray-500">Login dengan NIM</span>
                                </div>
                            </label>
                            <label class="relative flex items-center p-3 border border-gray-200 rounded-xl cursor-pointer hover:border-primary/50 transition-colors">
                                <input type="radio" name="role" value="guru" class="text-primary focus:ring-primary h-4 w-4">
                                <div class="ml-3">
                                    <span class="block text-sm font-semibold text-gray-900">Guru</span>
                                    <span class="block text-xs text-gray-500">Login dengan NIP</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Nama Lengkap -->
                    <div>
                        <label for="create_name" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Nama Lengkap *</label>
                        <input type="text" id="create_name" name="name" required placeholder="Contoh: Muhammad Rizki, S.Pd" class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <!-- Nomor Identitas (NIM / NIP) -->
                    <div>
                        <label for="create_ident" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Nomor Identitas (NIM / NIP) *</label>
                        <input type="text" id="create_ident" name="identity_number" required placeholder="Contoh: 202401015 atau 19850101..." class="w-full px-3.5 py-2.5 text-sm font-mono bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="text-[11px] text-gray-400 mt-1">Digunakan sebagai username saat login.</p>
                    </div>

                    <!-- Email (Opsional) -->
                    <div>
                        <label for="create_email" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Email (Opsional)</label>
                        <input type="email" id="create_email" name="email" placeholder="pengguna@sekolah.sch.id" class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <!-- Peringatan Password Default Otomatis -->
                    <div class="bg-blue-50/70 border border-blue-100 rounded-xl p-3.5 flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div class="text-xs text-blue-800 leading-relaxed">
                            Password awal pengguna otomatis ditetapkan sebagai <strong class="font-mono bg-blue-100/70 px-1.5 py-0.5 rounded">eduva123@</strong>. Pengguna diwajibkan memperbarui password saat login pertama kali.
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="$dispatch('close')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-xl hover:bg-primary/90 transition-colors shadow-sm">
                        Simpan Akun
                    </button>
                </div>
            </form>
        </x-modal>

        <!-- MODAL EDIT PENGGUNA -->
        <x-modal name="edit-user-modal" focusable>
            <template x-if="editingUser">
                <form :action="'/admin/users/' + editingUser.id" method="POST" class="p-6">
                    @csrf
                    @method('PUT')
                    
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Perbarui Data Pengguna</h2>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="'Mengedit akun: ' + editingUser.name"></p>
                        </div>
                        <button type="button" @click="$dispatch('close')" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="space-y-4 my-5 text-sm">
                        <!-- Peran Akun -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Peran Akun *</label>
                            <select name="role" x-model="editingUser.role" class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="siswa">Siswa</option>
                                <option value="guru">Guru</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>

                        <!-- Nama Lengkap -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Nama Lengkap *</label>
                            <input type="text" name="name" x-model="editingUser.name" required class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>

                        <!-- NIM / NIP -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Nomor Identitas (NIM/NIP) *</label>
                            <input type="text" name="identity_number" x-model="editingUser.identity_number" required class="w-full px-3.5 py-2.5 text-sm font-mono bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Email (Opsional)</label>
                            <input type="email" name="email" x-model="editingUser.email" class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>

                        <!-- Status Akun (Aktif / Nonaktif) -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Status Akun</label>
                            <select name="is_active" :value="editingUser.is_active ? '1' : '0'" class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="1">Aktif (Dapat Login)</option>
                                <option value="0">Nonaktif (Akses Masuk Diblokir)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="$dispatch('close')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-xl hover:bg-primary/90 transition-colors shadow-sm">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </template>
        </x-modal>

    </div>
</x-app-layout>

