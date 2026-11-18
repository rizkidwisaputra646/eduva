<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-primary uppercase tracking-wider mb-1">
                    <span>Pusat Kendali</span>
                    <span>•</span>
                    <span>Sistem Monitoring</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Dashboard Administrator</h1>
                <p class="text-sm text-gray-500 mt-0.5">Monitoring metrik pengguna, status kelas, keamanan akun, dan rekam jejak aktivitas LMS Eduva.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-colors shadow-sm">
                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    Kelola Pengguna
                </a>
                <a href="{{ route('admin.courses.index') }}" class="inline-flex items-center px-3.5 py-2 text-sm font-medium text-white bg-primary rounded-xl hover:bg-primary/90 transition-colors shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Monitoring Kelas
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        <!-- 1. Statistik Utama -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Siswa -->
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Siswa</span>
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ number_format($totalStudents) }}</div>
                    <div class="flex items-center gap-1.5 mt-1 text-xs text-emerald-600 font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>+{{ $newStudentsThisWeek }} siswa baru minggu ini</span>
                    </div>
                </div>
            </div>

            <!-- Total Guru -->
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Guru</span>
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ number_format($totalTeachers) }}</div>
                    <div class="flex items-center gap-1.5 mt-1 text-xs text-emerald-600 font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        <span>+{{ $newTeachersThisWeek }} guru baru minggu ini</span>
                    </div>
                </div>
            </div>

            <!-- Total Kelas -->
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Kelas</span>
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ number_format($totalCourses) }}</div>
                    <div class="flex items-center gap-2 mt-1 text-xs">
                        <span class="text-emerald-700 font-medium bg-emerald-50 px-2 py-0.5 rounded-md">{{ $activeCourses }} Aktif</span>
                        @if($inactiveCourses > 0)
                            <span class="text-slate-600 font-medium bg-slate-100 px-2 py-0.5 rounded-md">{{ $inactiveCourses }} Nonaktif</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Status Akun & Keamanan -->
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Kesehatan Akun</span>
                    <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ number_format($activeUsers) }} <span class="text-sm font-medium text-gray-500">aktif</span></div>
                    <div class="flex items-center gap-2 mt-1 text-xs">
                        @if($mustChangePasswordUsers > 0)
                            <span class="text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md font-medium">{{ $mustChangePasswordUsers }} Belum Ganti PW</span>
                        @endif
                        @if($inactiveUsers > 0)
                            <span class="text-rose-700 bg-rose-50 px-2 py-0.5 rounded-md font-medium">{{ $inactiveUsers }} Dinonaktifkan</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Ringkasan Pertumbuhan & Kesehatan Sistem -->
        <div class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white rounded-2xl p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <span class="text-xs font-semibold tracking-wider uppercase text-indigo-300">Ringkasan Pertumbuhan 30 Hari Terakhir</span>
                    <h2 class="text-xl font-bold mt-1 text-white">Ekosistem Eduva Berjalan Stabil</h2>
                    <p class="text-sm text-slate-300 mt-1 max-w-xl">
                        Total pendaftaran akun baru mencapai {{ $newUsersThisMonth }} pengguna dengan pembentukan {{ $newCoursesThisMonth }} kelas pembelajaran baru oleh tenaga pendidik.
                    </p>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 border-t lg:border-t-0 lg:border-l border-slate-700/60 pt-4 lg:pt-0 lg:pl-6">
                    <div>
                        <div class="text-xs text-slate-400 font-medium">Pengguna Baru / 30 Hari</div>
                        <div class="text-2xl font-bold text-white mt-0.5">+{{ $newUsersThisMonth }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 font-medium">Kelas Baru / 30 Hari</div>
                        <div class="text-2xl font-bold text-white mt-0.5">+{{ $newCoursesThisMonth }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400 font-medium">Rasio Rerata Siswa/Kelas</div>
                        <div class="text-2xl font-bold text-white mt-0.5">
                            {{ $totalCourses > 0 ? round($totalStudents / $totalCourses, 1) : 0 }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Dua Kolom: Aktivitas Sistem & Kelas Terbaru -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Kolom Kiri (7 Kolom): Aktivitas Terbaru Sistem -->
            <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                        <div>
                            <h2 class="text-base font-bold text-gray-900">Aktivitas Sistem Terkini</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Log peristiwa administratif dan aktivitas pembelajaran terkini</p>
                        </div>
                        <a href="{{ route('admin.logs') }}" class="text-xs font-semibold text-primary hover:text-primary/80 transition-colors inline-flex items-center gap-1">
                            <span>Lihat Semua Log</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>

                    <div class="divide-y divide-gray-50 mt-2">
                        @forelse($recentActivities as $log)
                            <div class="py-3 flex items-start justify-between gap-3 text-sm">
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="mt-0.5 w-7 h-7 rounded-lg shrink-0 flex items-center justify-center text-xs font-bold
                                        @if(str_starts_with($log->action, 'auth.')) bg-blue-50 text-blue-700
                                        @elseif(str_starts_with($log->action, 'course.')) bg-emerald-50 text-emerald-700
                                        @elseif(str_starts_with($log->action, 'user.')) bg-purple-50 text-purple-700
                                        @else bg-gray-50 text-gray-700 @endif">
                                        @if(str_starts_with($log->action, 'auth.')) A
                                        @elseif(str_starts_with($log->action, 'course.')) K
                                        @elseif(str_starts_with($log->action, 'user.')) P
                                        @else S @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-semibold text-gray-900 truncate">{{ $log->description }}</div>
                                        <div class="text-[11px] text-gray-500 flex items-center gap-2 mt-0.5">
                                            <span>Oleh: <strong class="text-gray-700 font-medium">{{ $log->user_name ?? 'Sistem' }}</strong></span>
                                            <span>•</span>
                                            <span class="capitalize">{{ $log->user_role ?? 'system' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-[11px] text-gray-400 shrink-0 whitespace-nowrap">
                                    {{ $log->created_at->diffForHumans() }}
                                </div>
                            </div>
                        @empty
                            <div class="py-8 text-center text-sm text-gray-400">
                                Belum ada catatan aktivitas yang tercatat.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan (5 Kolom): Kelas Terbaru yang Dibuat Guru -->
            <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                        <div>
                            <h2 class="text-base font-bold text-gray-900">Kelas Baru Dibuat</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Pantauan kelas terbaru yang diampu guru</p>
                        </div>
                        <a href="{{ route('admin.courses.index') }}" class="text-xs font-semibold text-primary hover:text-primary/80 transition-colors inline-flex items-center gap-1">
                            <span>Semua Kelas</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>

                    <div class="divide-y divide-gray-50 mt-2">
                        @forelse($recentCourses as $course)
                            <div class="py-3 flex items-center justify-between gap-3 text-sm">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.courses.show', $course->id) }}" class="font-semibold text-gray-900 hover:text-primary transition-colors truncate block">
                                        {{ $course->title }}
                                    </a>
                                    <div class="text-[11px] text-gray-500 mt-0.5 flex items-center gap-2">
                                        <span>Guru: {{ $course->teacher?->name ?? 'Tidak terhubung' }}</span>
                                        <span>•</span>
                                        <span>{{ $course->students_count }} Siswa</span>
                                    </div>
                                </div>
                                <div class="shrink-0 flex items-center gap-1.5">
                                    <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-medium rounded-full {{ $course->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $course->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="py-8 text-center text-sm text-gray-400">
                                Belum ada kelas yang dibuat oleh guru.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 mt-4">
                    <div class="bg-gray-50 rounded-xl p-3 flex items-center justify-between text-xs text-gray-600">
                        <span>Perlu konfigurasi sistem?</span>
                        <a href="{{ route('admin.settings') }}" class="font-semibold text-primary hover:underline">Buka Pengaturan</a>
                    </div>
                </div>
            </div>

        </div>

    </div>
</x-app-layout>

