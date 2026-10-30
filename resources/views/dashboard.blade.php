<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-900 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Clean Welcome Banner -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-1">Halo, {{ Auth::user()->name }}!</h1>
                <p class="text-gray-600">Mari lanjutkan progres belajarmu hari ini.</p>
            </div>
            <div>
                <a href="{{ route('ai-tutor.index') }}" class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-primary text-white hover:bg-primary-dark transition-colors font-medium text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Tanya AI Tutor
                </a>
            </div>
        </div>

        @php
            $role = Auth::user()->role ?? 'siswa';
        @endphp

        @if($role === 'siswa')
            <!-- Siswa Dashboard -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Stats Cards with clean design -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Kelas Diikuti</p>
                        <p class="text-2xl font-bold text-gray-900">4</p>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-emerald-50 flex items-center justify-center text-accent">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Tugas Selesai</p>
                        <p class="text-2xl font-bold text-gray-900">12</p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-slate-50 flex items-center justify-center text-secondary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Rata-rata Nilai</p>
                        <p class="text-2xl font-bold text-gray-900">85<span class="text-sm text-gray-400 font-normal">/100</span></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Progres Kelas -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-gray-900 text-lg">Progres Belajar</h3>
                        <a href="{{ route('kelas.index') }}" class="text-sm text-primary font-medium hover:underline">Lihat Semua</a>
                    </div>
                    <div class="space-y-6">
                        <!-- Item 1 -->
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <div>
                                    <h4 class="font-semibold text-gray-900">Matematika Lanjut</h4>
                                    <p class="text-sm text-gray-500">Bab 4: Integral Tentu</p>
                                </div>
                                <span class="text-sm font-bold text-gray-900">75%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div class="bg-primary h-2 rounded-full" style="width: 75%"></div>
                            </div>
                        </div>
                        
                        <!-- Item 2 -->
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <div>
                                    <h4 class="font-semibold text-gray-900">Biologi Sel</h4>
                                    <p class="text-sm text-gray-500">Bab 2: Struktur Membran</p>
                                </div>
                                <span class="text-sm font-bold text-gray-900">40%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div class="bg-accent h-2 rounded-full w-[40%]"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rekomendasi Belajar -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col">
                    <h3 class="font-bold text-gray-900 text-lg mb-4">Rekomendasi AI</h3>
                    <div class="space-y-3">
                        <div class="p-4 rounded-lg border border-red-100 bg-red-50">
                            <h4 class="text-sm font-semibold text-red-900 mb-1">Fokus Pembelajaran</h4>
                            <p class="text-sm text-red-700 mb-3">Tingkatkan pemahaman Limit Fungsi yang masih kurang.</p>
                            <a href="{{ route('ai-tutor.index') }}" class="text-sm font-medium text-red-800 hover:underline">
                                Mulai Sesi Tutor
                            </a>
                        </div>
                        
                        <div class="p-4 rounded-lg border border-blue-100 bg-blue-50">
                            <h4 class="text-sm font-semibold text-blue-900 mb-1">Tugas Terdekat</h4>
                            <p class="text-sm text-blue-700 mb-3">Kuis Biologi batas pengerjaan besok 23:59.</p>
                            <a href="{{ route('kuis.index') }}" class="text-sm font-medium text-blue-800 hover:underline">
                                Lihat Kuis
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($role === 'guru')
            <!-- Guru Dashboard -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Stats -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <p class="text-sm font-medium text-gray-500 mb-1">Kelas Aktif</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $total_courses ?? 0 }}</p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <p class="text-sm font-medium text-gray-500 mb-1">Total Materi</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $total_materials ?? 0 }}</p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <p class="text-sm font-medium text-gray-500 mb-1">Total Siswa (Sistem)</p>
                    <p class="text-3xl font-bold text-primary">{{ $total_students ?? 0 }}</p>
                </div>
            </div>

            <!-- Aktivitas Terbaru -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8">
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="font-bold text-gray-900 text-lg mb-6">Materi Terbaru Anda</h3>
                    <div class="space-y-4">
                        @forelse($recent_materials ?? [] as $material)
                        <div class="flex items-start gap-4 p-4 rounded-lg border border-gray-100 bg-gray-50">
                            <div class="w-10 h-10 rounded bg-indigo-100 flex items-center justify-center text-primary flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">{{ $material->title }}</h4>
                                <p class="text-xs text-gray-500 mb-1">Mata Pelajaran: {{ $material->course->title ?? '-' }}</p>
                                <p class="text-sm text-gray-600 line-clamp-1">{{ $material->content }}</p>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-8 text-gray-500">
                            Belum ada materi yang Anda tambahkan. 
                            <a href="{{ route('kelas.index') }}" class="text-primary hover:underline">Mulai tambahkan sekarang.</a>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Notifikasi / Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="font-bold text-gray-900 text-lg mb-6">Aksi Cepat</h3>
                    <div class="space-y-3">
                        <a href="{{ route('kelas.index') }}" class="flex items-center p-3 rounded-lg border border-gray-100 hover:bg-gray-50 transition-colors">
                            <div class="w-8 h-8 rounded bg-blue-50 flex items-center justify-center text-blue-600 mr-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700">Buat Kelas Baru</span>
                        </a>
                        <a href="{{ route('ai-tutor.index') }}" class="flex items-center p-3 rounded-lg border border-gray-100 hover:bg-gray-50 transition-colors">
                            <div class="w-8 h-8 rounded bg-purple-50 flex items-center justify-center text-purple-600 mr-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700">Atur AI Tutor</span>
                        </a>
                        <a href="{{ route('kuis.index') }}" class="flex items-center p-3 rounded-lg border border-gray-100 hover:bg-gray-50 transition-colors">
                            <div class="w-8 h-8 rounded bg-emerald-50 flex items-center justify-center text-emerald-600 mr-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700">Buat Kuis Baru</span>
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
