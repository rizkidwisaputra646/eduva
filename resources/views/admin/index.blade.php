<x-app-layout>
    <x-slot name="header">
        <div>
            <x-breadcrumb :items="['Admin Panel' => '']" />
            <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                {{ __('Manajemen Pengguna & Akun') }}
            </h2>
            <p class="text-xs text-gray-500 mt-1">Kelola data siswa, guru, pendaftaran akun baru, serta reset password.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-800 p-4 rounded-xl border border-emerald-200 flex items-center gap-3 text-xs font-medium shadow-2xs">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-rose-50 text-rose-800 p-4 rounded-xl border border-rose-200 flex items-center gap-3 text-xs font-medium shadow-2xs">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Tambah Pengguna Baru -->
            <div class="bg-white p-6 rounded-2xl shadow-2xs border border-gray-200 lg:col-span-1 h-fit">
                <div class="pb-3 border-b border-gray-100 mb-4">
                    <h3 class="font-bold text-gray-900 text-base">Tambah Akun Baru</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Daftarkan akun siswa atau guru baru ke sistem.</p>
                </div>

                <form action="{{ route('admin.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Peran (Role)</label>
                        <select name="role" required class="w-full rounded-xl border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 text-xs font-medium">
                            <option value="siswa">Siswa</option>
                            <option value="guru">Guru (Pengajar)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" required class="w-full rounded-xl border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 text-xs" placeholder="Contoh: Muhammad Rizki">
                    </div>

                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">NIM / NIP (Username Login)</label>
                        <input type="text" name="identity_number" required class="w-full rounded-xl border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 text-xs font-mono font-medium" placeholder="Cth: 202401001 (Siswa) / 19850101... (Guru)">
                        <p class="text-[10px] text-gray-400 mt-1">Digunakan siswa/guru sebagai ID utama saat masuk ke aplikasi.</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Alamat Email (Opsional)</label>
                        <input type="email" name="email" class="w-full rounded-xl border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 text-xs" placeholder="contoh@sekolah.edu">
                    </div>

                    <div class="p-3 bg-blue-50/70 border border-blue-200 rounded-xl text-[11px] text-blue-800 leading-relaxed">
                        <strong class="block mb-0.5">Password Default Otomatis:</strong>
                        Sistem akan otomatis memberikan password default: <code class="font-bold bg-white px-1.5 py-0.5 rounded border border-blue-200 font-mono">eduva123@</code>. Pengguna disarankan mengganti password setelah login.
                    </div>

                    <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white py-2.5 rounded-xl font-semibold text-xs shadow-2xs transition-colors cursor-pointer">
                        Simpan Akun Pengguna
                    </button>
                </form>
            </div>

            <!-- Tabel Daftar Pengguna -->
            <div class="bg-white rounded-2xl shadow-2xs border border-gray-200 lg:col-span-2 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">Daftar Pengguna LMS</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Total: {{ count($users ?? []) }} pengguna terdaftar</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600">
                        <thead class="bg-gray-50 border-b border-gray-100 text-gray-500 font-semibold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="px-5 py-3.5">Pengguna</th>
                                <th class="px-5 py-3.5">NIM / NIP</th>
                                <th class="px-5 py-3.5">Peran</th>
                                <th class="px-5 py-3.5">Status Keamanan</th>
                                <th class="px-5 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse(isset($users) ? $users : [] as $u)
                            <tr class="hover:bg-gray-50/70 transition-colors">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-slate-700 text-xs shrink-0">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 line-clamp-1">{{ $u->name }}</p>
                                            <p class="text-[11px] text-gray-400 line-clamp-1">{{ $u->email ?: 'Tanpa email' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 font-mono font-medium text-gray-800">
                                    {{ $u->identity_number ?: '-' }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $u->role === 'admin' ? 'bg-purple-100 text-purple-800 border border-purple-200' : ($u->role === 'guru' ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' : 'bg-blue-100 text-blue-800 border border-blue-200') }}">
                                        {{ ucfirst($u->role) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    @if($u->must_change_password)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-medium bg-amber-50 text-amber-800 border border-amber-200" title="Akun masih menggunakan password default eduva123@">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Password Default
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Password Kustom
                                    </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right space-x-2">
                                    @if(Auth::id() !== $u->id)
                                    <!-- Tombol Reset Password -->
                                    <form method="POST" action="{{ route('admin.users.reset-password', $u->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin mereset password akun {{ $u->name }} ({{ $u->identity_number }}) kembali ke password default \'eduva123@\'?')" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-lg transition-colors cursor-pointer" title="Kembalikan password ke eduva123@">
                                            Reset Password
                                        </button>
                                    </form>

                                    <!-- Tombol Hapus Akun -->
                                    <form method="POST" action="{{ route('admin.users.destroy', $u->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun {{ $u->name }}? Seluruh data terkait akan dihapus.')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition-colors cursor-pointer">
                                            Hapus
                                        </button>
                                    </form>
                                    @else
                                    <span class="text-[11px] text-gray-400 italic">Akun Anda</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">Belum ada data pengguna selain Anda.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
