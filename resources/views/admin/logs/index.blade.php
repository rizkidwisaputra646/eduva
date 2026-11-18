<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                    ['label' => 'Log Aktivitas', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Log & Rekam Jejak Aktivitas</h1>
                <p class="text-sm text-gray-500 mt-0.5">Audit log terperinci mencakup aktivitas autentikasi, manajemen pengguna, kelas, dan perubahan sistem.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1.5 rounded-xl bg-gray-100 text-xs font-semibold text-gray-700">
                    Total: {{ number_format($stats['total']) }} Log Peristiwa
                </span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        <!-- Filter Bar -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <form action="{{ route('admin.logs') }}" method="GET" class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                
                <!-- Filter Tabs Kategori -->
                <div class="flex items-center gap-1 overflow-x-auto text-xs font-medium">
                    <a href="{{ route('admin.logs', array_merge(request()->except('category'))) }}" class="px-3 py-1.5 rounded-lg whitespace-nowrap {{ !request('category') ? 'bg-primary text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                        Semua Kategori ({{ $stats['total'] }})
                    </a>
                    <a href="{{ route('admin.logs', array_merge(request()->except('category'), ['category' => 'auth'])) }}" class="px-3 py-1.5 rounded-lg whitespace-nowrap {{ request('category') === 'auth' ? 'bg-primary text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                        Autentikasi ({{ $stats['auth'] }})
                    </a>
                    <a href="{{ route('admin.logs', array_merge(request()->except('category'), ['category' => 'user'])) }}" class="px-3 py-1.5 rounded-lg whitespace-nowrap {{ request('category') === 'user' ? 'bg-primary text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                        Pengguna ({{ $stats['user'] }})
                    </a>
                    <a href="{{ route('admin.logs', array_merge(request()->except('category'), ['category' => 'course'])) }}" class="px-3 py-1.5 rounded-lg whitespace-nowrap {{ request('category') === 'course' ? 'bg-primary text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                        Kelas Pembelajaran ({{ $stats['course'] }})
                    </a>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <!-- Filter Peran -->
                    <select name="role" onchange="this.form.submit()" class="w-full sm:w-40 px-3 py-1.5 text-xs bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Semua Peran</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                        <option value="guru" {{ request('role') === 'guru' ? 'selected' : '' }}>Guru</option>
                        <option value="siswa" {{ request('role') === 'siswa' ? 'selected' : '' }}>Siswa</option>
                        <option value="system" {{ request('role') === 'system' ? 'selected' : '' }}>Sistem / Anonim</option>
                    </select>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari keterangan / nama..." class="w-full pl-8 pr-3 py-1.5 text-xs bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>

                    @if(request('search') || request('role') || request('category'))
                        <a href="{{ route('admin.logs') }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100" title="Reset filter">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </a>
                    @endif
                </div>

            </form>
        </div>

        <!-- Tabel Log Aktivitas -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-100 text-gray-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4 w-40">Waktu & Tanggal</th>
                            <th class="py-3 px-4 w-48">Pengguna</th>
                            <th class="py-3 px-4 w-36">Aksi</th>
                            <th class="py-3 px-4">Keterangan Aktivitas</th>
                            <th class="py-3 px-4 w-32 text-right">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <!-- Waktu -->
                                <td class="py-3 px-4 whitespace-nowrap text-gray-500">
                                    <div class="font-medium text-gray-800">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $log->created_at->diffForHumans() }}</div>
                                </td>

                                <!-- Pengguna -->
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-gray-900 truncate max-w-xs">{{ $log->user_name ?? 'Sistem' }}</div>
                                    <div class="text-[10px] text-gray-400 capitalize flex items-center gap-1.5 mt-0.5">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full {{ $log->user_role === 'admin' ? 'bg-purple-500' : ($log->user_role === 'guru' ? 'bg-indigo-500' : ($log->user_role === 'siswa' ? 'bg-blue-500' : 'bg-gray-400')) }}"></span>
                                        <span>{{ $log->user_role ?? 'system' }}</span>
                                    </div>
                                </td>

                                <!-- Aksi / Identifier -->
                                <td class="py-3 px-4">
                                    <span class="font-mono text-[11px] px-2 py-0.5 rounded font-medium
                                        @if(str_starts_with($log->action, 'auth.failed') || str_starts_with($log->action, 'auth.blocked')) bg-rose-50 text-rose-700 border border-rose-200
                                        @elseif(str_starts_with($log->action, 'auth.')) bg-blue-50 text-blue-700 border border-blue-100
                                        @elseif(str_starts_with($log->action, 'course.')) bg-emerald-50 text-emerald-700 border border-emerald-100
                                        @elseif(str_starts_with($log->action, 'user.')) bg-purple-50 text-purple-700 border border-purple-100
                                        @else bg-gray-100 text-gray-700 border border-gray-200 @endif">
                                        {{ $log->action }}
                                    </span>
                                </td>

                                <!-- Keterangan Aktivitas -->
                                <td class="py-3 px-4 text-gray-800 font-medium leading-relaxed">
                                    {{ $log->description }}
                                </td>

                                <!-- IP Address -->
                                <td class="py-3 px-4 text-right font-mono text-gray-400 whitespace-nowrap">
                                    {{ $log->ip_address ?: '127.0.0.1' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-sm text-gray-400">
                                    Belum ada rekam jejak aktivitas yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="p-3 border-t border-gray-100">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>

