<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                    ['label' => 'Keamanan Akun', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Pusat Keamanan & Kredensial Akun</h1>
                <p class="text-sm text-gray-500 mt-0.5">Monitoring kebijakan password pengguna, aktivasi/deaktivasi akses, dan audit keamanan sistem.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-xs font-semibold text-emerald-800 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Sistem Otentikasi Aktif
                </span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        <!-- 4 Statistik Keamanan -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Akun Terdaftar</span>
                <div class="mt-3">
                    <div class="text-3xl font-extrabold text-gray-900">{{ number_format($securityStats['total_users']) }}</div>
                    <div class="text-xs text-gray-500 mt-1">Guru, Siswa, dan Administrator</div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-700">Perlu Ganti Password</span>
                <div class="mt-3">
                    <div class="text-3xl font-extrabold text-amber-600">{{ number_format($securityStats['default_password_count']) }}</div>
                    <div class="text-xs text-amber-700 mt-1">Masih memakai password default</div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-rose-700">Akun Dinonaktifkan</span>
                <div class="mt-3">
                    <div class="text-3xl font-extrabold text-rose-600">{{ number_format($securityStats['inactive_count']) }}</div>
                    <div class="text-xs text-rose-700 mt-1">Akses login diblokir oleh admin</div>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Akun Aktif & Terlindungi</span>
                <div class="mt-3">
                    <div class="text-3xl font-extrabold text-emerald-600">{{ number_format($securityStats['active_count']) }}</div>
                    <div class="text-xs text-emerald-700 mt-1">Bebas dari status pemblokiran</div>
                </div>
            </div>
        </div>

        <!-- 2 Kolom: Manajemen Akses Cepat & Log Keamanan -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Kolom Kiri (8 Kolom): Tabel Pengguna & Tindakan Keamanan Cepat -->
            <div class="lg:col-span-8 space-y-4">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        
                        <!-- Filter Tabs -->
                        <div class="flex items-center gap-1 overflow-x-auto text-xs font-medium">
                            <a href="{{ route('admin.security', ['filter' => 'all']) }}" class="px-3 py-1.5 rounded-lg whitespace-nowrap {{ request('filter', 'all') === 'all' ? 'bg-primary text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                                Semua Akun
                            </a>
                            <a href="{{ route('admin.security', ['filter' => 'must_change']) }}" class="px-3 py-1.5 rounded-lg whitespace-nowrap {{ request('filter') === 'must_change' ? 'bg-primary text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                                Perlu Ganti Password ({{ $securityStats['default_password_count'] }})
                            </a>
                            <a href="{{ route('admin.security', ['filter' => 'inactive']) }}" class="px-3 py-1.5 rounded-lg whitespace-nowrap {{ request('filter') === 'inactive' ? 'bg-primary text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                                Akun Nonaktif ({{ $securityStats['inactive_count'] }})
                            </a>
                        </div>

                        <!-- Search -->
                        <form action="{{ route('admin.security') }}" method="GET">
                            @if(request('filter')) <input type="hidden" name="filter" value="{{ request('filter') }}"> @endif
                            <div class="relative w-full sm:w-52">
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / identitas..." class="w-full pl-8 pr-3 py-1.5 text-xs bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabel Akun -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50/75 border-b border-gray-100 text-gray-500 font-semibold uppercase tracking-wider">
                                    <th class="py-3 px-4">Pengguna</th>
                                    <th class="py-3 px-4">NIM / NIP</th>
                                    <th class="py-3 px-4">Status Password</th>
                                    <th class="py-3 px-4">Status Akses</th>
                                    <th class="py-3 px-4 text-right">Tindakan Cepat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($users as $user)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="py-3 px-4">
                                            <div class="font-semibold text-gray-900">{{ $user->name }}</div>
                                            <div class="text-[11px] text-gray-400 capitalize">{{ $user->role }}</div>
                                        </td>
                                        <td class="py-3 px-4 font-mono font-medium text-gray-700">
                                            {{ $user->identity_number }}
                                        </td>
                                        <td class="py-3 px-4">
                                            @if($user->must_change_password)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                                    Wajib Ganti Password
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-50 text-slate-600">
                                                    Password Kustom
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-medium {{ $user->is_active ? 'text-emerald-700' : 'text-rose-700' }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $user->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                                {{ $user->is_active ? 'Aktif' : 'Diblokir' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-1.5">
                                                
                                                <!-- Reset Password Button -->
                                                <form action="{{ route('admin.security.reset-password', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Reset password akun {{ $user->name }} kembali ke default eduva123@?');">
                                                    @csrf
                                                    <button type="submit" class="px-2.5 py-1 text-[11px] font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors border border-blue-200">
                                                        Reset PW
                                                    </button>
                                                </form>

                                                <!-- Toggle Deactivate Button -->
                                                @if($user->id !== Auth::id())
                                                    <form action="{{ route('admin.security.toggle-status', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Ubah status keaktifan akun {{ $user->name }}?');">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="px-2.5 py-1 text-[11px] font-medium rounded-lg transition-colors border {{ $user->is_active ? 'border-rose-200 text-rose-700 bg-rose-50 hover:bg-rose-100' : 'border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                                                            {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                        </button>
                                                    </form>
                                                @endif

                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-10 text-center text-gray-400">
                                            Tidak ada akun yang sesuai kriteria.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($users->hasPages())
                        <div class="p-3 border-t border-gray-100">
                            {{ $users->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Kolom Kanan (4 Kolom): Log Audit Keamanan Terkini -->
            <div class="lg:col-span-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                        <div>
                            <h2 class="text-sm font-bold text-gray-900">Audit Keamanan Terkini</h2>
                            <p class="text-[11px] text-gray-400 mt-0.5">Catatan reset, kegagalan login, & blokir akun</p>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-50 mt-2">
                        @forelse($securityLogs as $sLog)
                            <div class="py-3 text-xs">
                                <div class="flex items-center justify-between text-[11px] text-gray-400">
                                    <span class="font-mono text-primary font-semibold">{{ $sLog->action }}</span>
                                    <span>{{ $sLog->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="text-gray-800 font-medium mt-1 leading-snug">{{ $sLog->description }}</div>
                                <div class="text-[11px] text-gray-400 mt-0.5">IP: {{ $sLog->ip_address ?: '127.0.0.1' }}</div>
                            </div>
                        @empty
                            <div class="py-8 text-center text-xs text-gray-400">
                                Belum ada insiden keamanan atau aksi reset terkini.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 mt-4">
                    <a href="{{ route('admin.logs', ['category' => 'auth']) }}" class="w-full inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-xl transition-colors">
                        Buka Seluruh Log Autentikasi
                    </a>
                </div>
            </div>

        </div>

    </div>
</x-app-layout>

