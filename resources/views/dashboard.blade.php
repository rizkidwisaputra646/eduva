<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-900 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Banner Sambutan Minimalis -->
        <div class="bg-white rounded-2xl p-6 sm:p-7 shadow-2xs border border-gray-200 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <h1 class="text-2xl font-bold text-gray-900">Halo, {{ Auth::user()->name }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                        {{ Auth::user()->role === 'guru' ? 'Tenaga Pengajar' : (Auth::user()->role === 'admin' ? 'Administrator' : 'Siswa') }}
                    </span>
                </div>
                <p class="text-xs text-gray-500">
                    {{ Auth::user()->role === 'guru' ? 'Pantau progres kelas, aktivitas siswa, dan penilaian tugas dari sini.' : 'Selamat belajar kembali! Pantau materi, jadwal tugas, dan kuis Anda.' }}
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('ai-tutor.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-primary border border-indigo-200 transition-colors font-semibold text-xs shadow-2xs">
                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span>Tanya AI Tutor</span>
                </a>
            </div>
        </div>

        @php
            $role = Auth::user()->role ?? 'siswa';
        @endphp

        @if($role === 'siswa')
            <!-- ================= DASHBOARD SISWA ================= -->
            <!-- Metrik Statistik Riil -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl shadow-2xs border border-gray-200 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-primary shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Mata Pelajaran</p>
                        <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ $total_courses ?? 0 }} <span class="text-xs font-normal text-gray-400">kelas</span></p>
                    </div>
                </div>
                
                <div class="bg-white p-5 rounded-2xl shadow-2xs border border-gray-200 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tugas Dikumpulkan</p>
                        <p class="text-2xl font-bold text-emerald-600 mt-0.5">{{ $completed_tasks_count ?? 0 }} <span class="text-xs font-normal text-gray-400">tugas/kuis</span></p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-2xs border border-gray-200 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-primary shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Rata-Rata Nilai</p>
                        <p class="text-2xl font-bold text-primary mt-0.5">{{ $average_score !== null ? $average_score : '-' }} <span class="text-xs font-normal text-gray-400">{{ $average_score !== null ? '/ 100' : 'belum dinilai' }}</span></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Kolom Kiri: Tugas Terdekat & Kelas Aktif -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Tugas / Kuis yang Perlu Dikerjakan Siswa -->
                    <div class="bg-white rounded-2xl shadow-2xs border border-gray-200 p-6">
                        <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100">
                            <div>
                                <h3 class="font-bold text-gray-900 text-base">Tugas & Kuis yang Perlu Dikerjakan</h3>
                                <p class="text-xs text-gray-500">Daftar aktivitas yang belum Anda selesaikan.</p>
                            </div>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ count($pending_tasks ?? []) > 0 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                {{ count($pending_tasks ?? []) }} Pending
                            </span>
                        </div>

                        <div class="space-y-3">
                            @forelse($pending_tasks ?? [] as $task)
                            <div class="p-4 rounded-xl border border-gray-200 bg-white hover:border-gray-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl {{ $task->type === 'quiz' ? 'bg-blue-50 text-blue-600 border border-blue-200' : 'bg-purple-50 text-purple-600 border border-purple-200' }} flex items-center justify-center shrink-0 mt-0.5">
                                        @if($task->type === 'quiz')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                        @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-bold text-sm text-gray-900">{{ $task->title }}</h4>
                                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded {{ $task->type === 'quiz' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700' }}">
                                                {{ $task->type === 'quiz' ? 'Kuis Online' : 'Tugas' }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ $task->course->title ?? '-' }} &bull; {{ $task->topic ?: 'Umum' }}
                                        </p>
                                        @if($task->deadline)
                                        <div class="mt-1.5 flex items-center gap-1 text-[11px] font-medium {{ $task->deadline < now() ? 'text-rose-600' : 'text-amber-700' }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <span>Tenggat: {{ $task->deadline->format('d M Y, H:i') }} WIB</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="shrink-0">
                                    @if($task->type === 'quiz')
                                    <a href="{{ route('kuis.kerjakan', $task->id) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-xl text-xs font-semibold shadow-2xs transition-colors">
                                        <span>Kerjakan Kuis</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </a>
                                    @else
                                    <a href="{{ route('kelas.show', $task->course_id) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-xl text-xs font-semibold shadow-2xs transition-colors">
                                        <span>Kumpulkan Tugas</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </a>
                                    @endif
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-8">
                                <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3 border border-emerald-100">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <h4 class="text-sm font-bold text-gray-900 mb-1">Semua Tugas Selesai</h4>
                                <p class="text-xs text-gray-500">Tidak ada tugas atau kuis yang tertunda saat ini. Kerja bagus!</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Kelas yang Diikuti Siswa -->
                    <div class="bg-white rounded-2xl shadow-2xs border border-gray-200 p-6">
                        <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100">
                            <div>
                                <h3 class="font-bold text-gray-900 text-base">Mata Pelajaran Diikuti</h3>
                                <p class="text-xs text-gray-500">Akses langsung ke ruang kelas Anda.</p>
                            </div>
                            <a href="{{ route('kelas.index') }}" class="text-xs font-semibold text-primary hover:underline">Lihat Semua</a>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @forelse($enrolled_courses ?? [] as $course)
                            <a href="{{ route('kelas.show', $course->id) }}" class="p-4 rounded-xl border border-gray-200 hover:border-primary/50 hover:bg-indigo-50/20 transition-all flex flex-col justify-between group shadow-2xs">
                                <div>
                                    <h4 class="font-bold text-sm text-gray-900 group-hover:text-primary transition-colors line-clamp-1">{{ $course->title }}</h4>
                                    <p class="text-xs text-gray-500 mt-1 line-clamp-1">Guru: {{ $course->teacher->name ?? 'Pengajar' }}</p>
                                </div>
                                <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-400">
                                    <span>{{ $course->materials_count ?? 0 }} Materi &bull; {{ $course->quizzes_count ?? 0 }} Kuis</span>
                                    <span class="font-semibold text-primary group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                                </div>
                            </a>
                            @empty
                            <div class="col-span-full text-center py-6 text-xs text-gray-400">
                                Anda belum bergabung ke kelas manapun. <a href="{{ route('kelas.index') }}" class="text-primary font-semibold hover:underline">Gabung kelas sekarang.</a>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Rekomendasi & AI Tutor Quick Card -->
                <div class="space-y-6">
                    <div class="bg-gradient-to-br from-indigo-900 to-primary rounded-2xl p-6 text-white shadow-sm relative overflow-hidden">
                        <div class="relative z-10">
                            <span class="inline-block px-2.5 py-0.5 rounded-full bg-white/20 backdrop-blur-md text-[10px] font-bold uppercase tracking-wider mb-3">
                                Asisten Belajar
                            </span>
                            <h3 class="text-lg font-bold mb-1.5">Butuh Bantuan Memahami Materi?</h3>
                            <p class="text-xs text-indigo-100 leading-relaxed mb-4">
                                Tanya AI Tutor mengenai materi pelajaran yang sulit atau minta rangkuman penjelasan yang mudah dipahami.
                            </p>
                            <a href="{{ route('ai-tutor.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-primary hover:bg-indigo-50 rounded-xl text-xs font-bold shadow-2xs transition-colors">
                                <span>Mulai Diskusi dengan AI</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Materi Pelajaran Terbaru -->
                    <div class="bg-white rounded-2xl shadow-2xs border border-gray-200 p-6">
                        <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wider mb-3 pb-2 border-b border-gray-100">Materi Pembelajaran Baru</h3>
                        <div class="space-y-3">
                            @forelse($recent_materials ?? [] as $m)
                            <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/50 hover:bg-gray-50 transition-colors">
                                <h4 class="text-xs font-bold text-gray-900 line-clamp-1">{{ $m->title }}</h4>
                                <p class="text-[11px] text-gray-500 mt-0.5">{{ $m->course->title ?? '-' }}</p>
                                <a href="{{ route('kelas.show', $m->course_id) }}" class="inline-block mt-2 text-[11px] font-semibold text-primary hover:underline">
                                    Buka Materi &rarr;
                                </a>
                            </div>
                            @empty
                            <p class="text-xs text-gray-400 text-center py-4">Belum ada materi baru yang diunggah.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

        @elseif($role === 'guru')
            <!-- ================= DASHBOARD GURU ================= -->
            <!-- Metrik Statistik Guru -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl shadow-2xs border border-gray-200">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Mata Pelajaran Aktif</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $total_courses ?? 0 }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl shadow-2xs border border-gray-200">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Materi Terunggah</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $total_materials ?? 0 }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl shadow-2xs border border-gray-200">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Siswa Terdaftar</p>
                    <p class="text-2xl font-bold text-primary mt-1">{{ $total_students ?? 0 }}</p>
                </div>
                <div class="bg-white p-5 rounded-2xl shadow-2xs border {{ ($ungraded_count ?? 0) > 0 ? 'border-amber-300 bg-amber-50/30' : 'border-gray-200' }}">
                    <p class="text-xs font-semibold {{ ($ungraded_count ?? 0) > 0 ? 'text-amber-700' : 'text-gray-500' }} uppercase tracking-wider">Perlu Dinilai</p>
                    <p class="text-2xl font-bold {{ ($ungraded_count ?? 0) > 0 ? 'text-amber-700' : 'text-gray-900' }} mt-1">
                        {{ $ungraded_count ?? 0 }} <span class="text-xs font-normal text-gray-400">tugas</span>
                    </p>
                </div>
            </div>

            <!-- Antrean Tugas yang Perlu Dinilai Guru (Actionable Notification) -->
            @if(($ungraded_count ?? 0) > 0)
            <div class="bg-amber-50 rounded-2xl border border-amber-200 p-5 shadow-2xs">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-amber-900">Pengumpulan Siswa Menunggu Penilaian</h3>
                            <p class="text-xs text-amber-700">Terdapat {{ $ungraded_count }} pengumpulan siswa yang belum Anda periksa.</p>
                        </div>
                    </div>
                </div>

                <div class="divide-y divide-amber-200/60 bg-white rounded-xl border border-amber-200 overflow-hidden">
                    @foreach($ungraded_submissions ?? [] as $unSub)
                    <div class="p-3.5 flex items-center justify-between gap-3 hover:bg-amber-50/30 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs shrink-0">
                                {{ strtoupper(substr($unSub->user->name ?? 'S', 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-gray-900 truncate">{{ $unSub->user->name }}</p>
                                <p class="text-[11px] text-gray-500 truncate">{{ $unSub->quiz->title ?? 'Tugas' }} &bull; {{ $unSub->quiz->course->title ?? '-' }}</p>
                            </div>
                        </div>
                        <a href="{{ route('assignments.submissions', $unSub->quiz_id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold shrink-0 shadow-2xs transition-colors">
                            <span>Buka Penilaian</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Kelas yang Diampu Guru -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-2xl shadow-2xs border border-gray-200 p-6">
                        <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100">
                            <h3 class="font-bold text-gray-900 text-base">Kelas yang Anda Ampu</h3>
                            <a href="{{ route('kelas.index') }}" class="text-xs font-semibold text-primary hover:underline">Kelola Semua Kelas</a>
                        </div>

                        <div class="space-y-3">
                            @forelse($teacher_courses ?? [] as $tCourse)
                            <div class="p-4 rounded-xl border border-gray-200 hover:border-gray-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-sm text-gray-900">{{ $tCourse->title }}</h4>
                                        <!-- Quick Copy Kode -->
                                        <div x-data="{ copied: false }" class="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 border border-slate-200 rounded text-[10px] font-mono text-slate-700">
                                            <span>Kode: {{ $tCourse->invitation_code }}</span>
                                            <button type="button" @click.stop.prevent="navigator.clipboard.writeText('{{ $tCourse->invitation_code }}'); copied = true; $dispatch('toast', { message: 'Kode kelas tersalin!', type: 'success' }); setTimeout(() => copied = false, 2000)" class="hover:text-primary transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $tCourse->students_count ?? 0 }} Siswa &bull; {{ $tCourse->materials_count ?? 0 }} Materi &bull; {{ $tCourse->quizzes_count ?? 0 }} Kuis
                                    </p>
                                </div>
                                <a href="{{ route('kelas.show', $tCourse->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-xs font-semibold shrink-0 transition-colors">
                                    Buka Topik &rarr;
                                </a>
                            </div>
                            @empty
                            <p class="text-xs text-gray-400 text-center py-6">Anda belum membuat mata pelajaran. <a href="{{ route('kelas.index') }}" class="text-primary font-semibold hover:underline">Buat kelas sekarang.</a></p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Aksi Cepat & Materi Terbaru -->
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl shadow-2xs border border-gray-200 p-6">
                        <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wider mb-3 pb-2 border-b border-gray-100">Aksi Cepat</h3>
                        <div class="space-y-2.5">
                            <a href="{{ route('kelas.index') }}" class="flex items-center p-3 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors text-xs font-semibold text-gray-700">
                                <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mr-2.5 shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </div>
                                <span>Buat Mata Pelajaran Baru</span>
                            </a>
                            <a href="{{ route('ai-tutor.index') }}" class="flex items-center p-3 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors text-xs font-semibold text-gray-700">
                                <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center mr-2.5 shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                                <span>Gunakan AI Tutor</span>
                            </a>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-2xs border border-gray-200 p-6">
                        <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wider mb-3 pb-2 border-b border-gray-100">Materi Terakhir Anda</h3>
                        <div class="space-y-3">
                            @forelse($recent_materials ?? [] as $rMat)
                            <div class="text-xs p-2.5 rounded-lg border border-gray-100 bg-gray-50/50">
                                <p class="font-semibold text-gray-800 line-clamp-1">{{ $rMat->title }}</p>
                                <p class="text-[11px] text-gray-500 mt-0.5">{{ $rMat->course->title ?? '-' }}</p>
                            </div>
                            @empty
                            <p class="text-xs text-gray-400 text-center py-4">Belum ada materi yang Anda tambahkan.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
