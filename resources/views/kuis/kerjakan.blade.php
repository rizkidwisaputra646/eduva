<!DOCTYPE html>
@php($examPolicy = array_replace(\App\Services\ExamMonitoringService::policy($quiz), $examSession?->policy_snapshot ?? []))
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mengerjakan Ujian: {{ $quiz->title }} - Eduva</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 text-gray-900 select-none min-h-screen flex flex-col" x-data="examApp()">
    
    <!-- Topbar Banner Pengawasan -->
    @if($quiz->detect_tab_switch)
    <div class="bg-amber-600 text-white text-center py-2 px-4 text-xs font-semibold shadow-xs z-50 relative flex items-center justify-center gap-2">
        <svg class="w-4 h-4 text-amber-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        <span>MODE UJIAN TERPANTAU: Deteksi pindah tab aktif. Aktivitas meninggalkan jendela ujian akan dicatat otomatis.</span>
    </div>
    @else
    <div class="bg-slate-800 text-white text-center py-2 px-4 text-xs font-medium shadow-xs z-50 relative flex items-center justify-center gap-2">
        <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span>LEMBAR UJIAN EDUVA: Kerjakan seluruh butir soal dengan teliti dan cermat.</span>
    </div>
    @endif

    @if($isTimedOut)
    <!-- Layar Peringatan Waktu Sudah Habis Sebelumnya -->
    <div class="max-w-md mx-auto my-auto py-12 px-4 w-full text-center">
        <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-200">
            <div class="w-16 h-16 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">Batas Waktu Ujian Telah Berakhir</h2>
            <p class="text-xs text-gray-600 leading-relaxed mb-6">
                Sesi pengerjaan ujian untuk kuis <strong>{{ $quiz->title }}</strong> telah kedaluwarsa sesuai durasi yang ditentukan oleh guru. Anda tidak dapat lagi mengisi atau mengubah lembar jawaban.
            </p>
            <a href="{{ route('kelas.show', $quiz->course_id) }}" class="inline-flex items-center justify-center w-full px-5 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-xl text-xs font-bold transition-colors">
                Kembali ke Halaman Kelas
            </a>
        </div>
    </div>
    @else

    <!-- ================= 1. KARTU PERSIAPAN MULAI UJIAN ================= -->
    <div class="max-w-2xl mx-auto py-12 px-4 flex-1 flex items-center" x-show="!started" x-cloak>
        <div class="bg-white p-8 sm:p-10 rounded-2xl shadow-xl text-center border border-gray-100 w-full">
            <div class="w-20 h-20 bg-indigo-50 text-primary rounded-full flex items-center justify-center mx-auto mb-5">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            </div>
            
            <h2 class="text-2xl font-bold text-gray-900 mb-1.5">{{ $quiz->title }}</h2>
            <p class="text-xs text-gray-500 mb-5">{{ $quiz->course->title ?? '' }} &bull; {{ $quiz->questions->count() }} Butir Soal</p>
            
            <!-- Box Durasi & Waktu Ujian -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6 text-left">
                <div class="p-3.5 bg-blue-50/60 rounded-xl border border-blue-200">
                    <span class="text-[11px] font-semibold text-blue-700 uppercase tracking-wider block mb-1">Durasi Ujian</span>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="text-sm font-bold text-blue-900">{{ $quiz->formatted_duration }}</span>
                    </div>
                </div>

                <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200">
                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block mb-1">Tenggat Waktu</span>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span class="text-sm font-bold text-gray-800">{{ $quiz->deadline ? $quiz->deadline->format('d M Y, H:i') . ' WIB' : 'Tidak Dibatasi' }}</span>
                    </div>
                </div>
            </div>

            @if($quiz->duration && $quiz->duration > 0)
            <div class="p-3.5 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800 mb-6 text-left flex items-start gap-2.5">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div class="leading-relaxed">
                    <strong class="block mb-0.5">Ketentuan Timer Countdown:</strong>
                    Timer pengerjaan sebesar <strong>{{ $quiz->formatted_duration }}</strong> akan langsung berjalan setelah Anda menekan tombol di bawah. Timer tetap berjalan dan tidak dapat dijeda meskipun Anda me-refresh halaman atau menutup peramban. Sistem akan otomatis menutup dan mengumpulkan ujian ketika waktu habis.
                </div>
            </div>
            @endif

            @if($quiz->description)
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 text-xs text-gray-600 mb-6 text-left leading-relaxed">
                <span class="font-bold text-gray-700 block mb-1">Petunjuk Ujian:</span>
                {{ $quiz->description }}
            </div>
            @endif

            <p id="monitoring-notice" class="text-sm text-gray-600 my-4">Saat pengawasan aktif, EDUVA mencatat kehilangan fokus, perubahan visibilitas, fullscreen, dan interaksi sesuai aturan guru. Nama aplikasi lain, isi clipboard, layar, serta aktivitas perangkat lain tidak direkam. Kehilangan koneksi bukan pelanggaran. Memulai ujian berarti Anda memahami pemberitahuan ini.</p>
            @php($examPolicy = array_replace(\App\Services\ExamMonitoringService::policy($quiz), $examSession?->policy_snapshot ?? []))
            <div class="p-3 bg-blue-50 text-left text-sm my-3">Pengawasan: {{ $examPolicy['enabled'] ? 'Aktif' : 'Nonaktif' }} • Fullscreen: {{ $examPolicy['fullscreen'] ? 'Diperlukan' : 'Opsional' }} • Ambang insiden: {{ $examPolicy['threshold'] }} • Auto-submit pada ambang: {{ $examPolicy['auto_submit'] ? 'YA' : 'Tidak' }}. Batasan clipboard/menu: {{ $examPolicy['restrict_clipboard'] ? 'Aktif' : 'Sesuai pengaturan guru' }}.</div>
            <label class="block text-sm my-3"><input type="checkbox" x-model="acknowledged"> Saya memahami aturan dan kebijakan pengawasan ujian.</label>
            <button 
                aria-describedby="monitoring-notice"
                type="button" 
                @click="startExam()" 
                :disabled="starting || !acknowledged"
                class="w-full sm:w-auto px-8 py-3.5 bg-primary hover:bg-primary-dark text-white font-bold rounded-xl shadow-lg transition-all hover:scale-105 inline-flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
            >
                <template x-if="!starting">
                    <div class="inline-flex items-center gap-2">
                        <span>Mulai Ujian Sekarang</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </div>
                </template>
                <template x-if="starting">
                    <span>Menyiapkan Sesi Ujian...</span>
                </template>
            </button>
        </div>
    </div>

    <!-- ================= 2. LEMBAR PENGERJAAN SOAL ================= -->
    <div class="w-full flex-1 flex flex-col" x-show="started" x-cloak>
        <!-- Sticky Header Bar: Timer Countdown & Info -->
        <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-gray-200 shadow-2xs py-3 px-4 sm:px-8">
            <div class="max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 line-clamp-1">{{ $quiz->title }}</h3>
                        <p class="text-[11px] text-gray-500">{{ $quiz->questions->count() }} Soal &bull; Total Poin: {{ $quiz->questions->sum('points') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Deteksi Tab Switch Badge -->
                    @if($quiz->detect_tab_switch)
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-xs font-semibold text-amber-800">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        <span>Pindah Tab: <strong x-text="violationCount">0</strong>x</span>
                    </div>
                    @endif

                    <!-- Countdown Timer Widget -->
                    <template x-if="hasDuration">
                        <div 
                            class="flex items-center gap-2 px-3.5 py-1.5 rounded-xl border text-xs font-mono font-bold shadow-2xs transition-colors"
                            :class="{
                                'bg-slate-900 text-white border-slate-800': remainingSeconds > 300,
                                'bg-amber-100 text-amber-900 border-amber-300': remainingSeconds <= 300 && remainingSeconds > 60,
                                'bg-rose-600 text-white border-rose-700 animate-pulse': remainingSeconds <= 60
                            }"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="tracking-wider text-sm" x-text="timerDisplay">00:00:00</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Banner Peringatan Waktu Hampir Habis -->
            <div x-show="hasDuration && remainingSeconds <= 300 && remainingSeconds > 60" x-transition class="max-w-5xl mx-auto mt-2">
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-3 py-1.5 rounded-lg text-xs flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span>Peringatan: Waktu ujian tersisa kurang dari 5 menit. Segera periksa kelengkapan jawaban Anda.</span>
                    </div>
                </div>
            </div>

            <div x-show="hasDuration && remainingSeconds <= 60 && remainingSeconds > 0" x-transition class="max-w-5xl mx-auto mt-2">
                <div class="bg-rose-50 border border-rose-300 text-rose-800 px-3 py-1.5 rounded-lg text-xs flex items-center justify-between font-bold">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span>PERINGATAN KRITIS: Waktu tersisa kurang dari 1 menit! Sistem akan otomatis mengumpulkan seluruh jawaban Anda ketika waktu habis.</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Konten Butir Soal -->
        <main class="max-w-4xl mx-auto py-8 px-4 w-full flex-1">
            <form data-return-url="{{ route('kelas.show', $quiz->course_id) }}" x-ref="examForm" method="POST" action="{{ route('kuis.submit', $quiz->id) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="is_timeout" x-model="isTimeOut">
                
                @forelse($quiz->questions as $index => $q)
                <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-2xs border border-gray-200 mb-5">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <span class="inline-block px-2.5 py-1 bg-slate-100 text-slate-700 font-bold text-xs rounded-lg border border-slate-200">
                            Soal #{{ $index + 1 }} ({{ $q->points }} Poin)
                        </span>
                        <span class="text-xs text-gray-400 font-medium">
                            {{ $q->type === 'multiple_choice' ? 'Pilihan Ganda' : 'Esai' }}
                        </span>
                    </div>

                    <p class="text-sm font-medium text-gray-900 mb-6 leading-relaxed whitespace-pre-line">
                        {{ $q->question_text }}
                    </p>
                    
                    @if($q->type === 'multiple_choice')
                    <div class="space-y-2.5">
                        @foreach($q->options as $optIndex => $opt)
                        <label class="flex items-center p-3.5 border border-gray-200 rounded-xl hover:bg-indigo-50/60 cursor-pointer transition-colors group">
                            <input type="radio" name="answers[{{ $q->id }}]" value="{{ $opt->id }}" class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <span class="w-6 font-bold text-xs text-gray-400 group-hover:text-primary ml-2">{{ chr(65 + $optIndex) }}.</span>
                            <span class="ml-1 text-sm text-gray-700 group-hover:text-primary font-medium">{{ $opt->option_text }}</span>
                        </label>
                        @endforeach
                    </div>
                    @else
                    <div class="space-y-4">
                        <textarea name="answers[{{ $q->id }}]" rows="4" class="w-full text-sm rounded-xl border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20" placeholder="Ketikkan jawaban uraian Anda di sini..."></textarea>
                        
                        <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-xl">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Lampirkan file jawaban tambahan (Opsional)</label>
                            <input type="file" name="files[{{ $q->id }}]" class="w-full text-xs text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-colors">
                        </div>
                    </div>
                    @endif
                </div>
                @empty
                <div class="bg-white p-8 rounded-2xl shadow-xs border border-gray-200 mb-6 text-center text-gray-500 text-sm">
                    Belum ada soal untuk kuis ini.
                </div>
                @endforelse

                <div class="flex justify-end mt-8 pb-12">
                    <button type="submit"  class="px-8 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow-md hover:shadow-lg transition-all inline-flex items-center gap-2 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Selesai & Kumpulkan Ujian</span>
                    </button>
                </div>
            </form>
        </main>
    </div>
    @endif

    <!-- Overlay Otomatis Saat Waktu Habis (Auto Submit Non-Dismissible) -->
    <div x-show="timeOutOverlay" class="fixed inset-0 z-[200] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4" style="display: none;" x-transition>
        <div class="bg-white p-8 rounded-2xl max-w-md w-full text-center shadow-2xl border border-gray-150">
            <div class="w-16 h-16 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4 animate-pulse">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Waktu Ujian Telah Habis!</h3>
            <p class="text-xs text-gray-600 mb-6 leading-relaxed">
                Batas waktu pengerjaan telah berakhir. Sistem sedang otomatis mengumpulkan dan menyimpan lembar jawaban Anda ke server...
            </p>
            <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                <div class="bg-rose-600 h-2 rounded-full animate-pulse w-full"></div>
            </div>
        </div>
    </div>

    <!-- Modal Peringatan Pelanggaran Deteksi Pindah Tab -->
    <div x-show="violationModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 backdrop-blur-xs p-4" style="display: none;" x-transition>
        <div class="bg-white p-6 sm:p-8 rounded-2xl max-w-md w-full text-center shadow-2xl border border-gray-100">
            <div class="w-16 h-16 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Peringatan Berpindah Halaman!</h3>
            <p class="text-xs text-gray-600 mb-2 leading-relaxed" x-text="violationMessage"></p>
            <div class="p-3 bg-red-50 rounded-xl border border-red-100 text-[11px] text-red-700 mb-6 text-left">
                <strong>Catatan:</strong> Kejadian ini telah dicatat dalam riwayat ujian guru beserta waktu kejadiannya. Ujian Anda tidak dibatalkan, namun harap tetap berada di halaman ujian hingga selesai.
            </div>
            <button @click="dismissViolation()" class="w-full px-5 py-3 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-md transition-colors cursor-pointer">
                Saya Mengerti, Kembali Mengerjakan Ujian
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('examApp', () => ({
                started: {{ $isStarted ? 'true' : 'false' }},
                starting: false,
                acknowledged: false,
                detectTabSwitch: {{ $quiz->detect_tab_switch ? 'true' : 'false' }},
                hasDuration: {{ $quiz->duration && $quiz->duration > 0 ? 'true' : 'false' }},
                remainingSeconds: {{ $remainingSeconds >= 0 ? $remainingSeconds : 0 }},
                isTimeOut: false,
                timeOutOverlay: false,
                quizId: {{ $quiz->id }},
                violationModal: false,
                violationMessage: '',
                violationCount: 0,
                lastViolationTime: 0,
                timerInterval: null,
                
                init() {
                    if (this.started) {
                        this.startMonitoring();
                    }
                },

                startExam() {
                    if (!this.acknowledged) return;
                    @if($examPolicy['fullscreen'])
                    document.documentElement.requestFullscreen?.().catch(() => {});
                    @endif
                    this.starting = true;
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                    fetch(`/kuis/${this.quizId}/start`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.starting = false;
                        if (data.status === 'success') {
                            this.started = true;
                            if (data.remaining_seconds >= 0) {
                                this.remainingSeconds = data.remaining_seconds;
                            }
                            this.startMonitoring();
                        } else {
                            alert(data.message || 'Gagal memulai ujian.');
                        }
                    })
                    .catch(err => {
                        this.starting = false;
                        console.error('Error starting exam:', err);
                        // Sesi harus dikonfirmasi server
                        alert('Sesi belum dimulai. Periksa koneksi dan coba kembali.');
                    });
                },

                async startMonitoring() {
                    const ready = await window.monitorExam(this).catch(() => { this._monitorStarted = false; return false; });
                    if (ready && this.hasDuration) this.initCountdownTimer();
                },

                initCountdownTimer() {
                    if (this.timerInterval) clearInterval(this.timerInterval);

                    this.timerInterval = setInterval(() => {
                        if (this.remainingSeconds > 0) {
                            this.remainingSeconds--;
                        } else {
                            clearInterval(this.timerInterval);
                            this.triggerAutoSubmit();
                        }
                    }, 1000);
                },

                triggerAutoSubmit() {
                    if (window.finishExam) window.finishExam(true);
                },

                get timerDisplay() {
                    const sec = Math.max(0, this.remainingSeconds);
                    const hours = Math.floor(sec / 3600);
                    const minutes = Math.floor((sec % 3600) / 60);
                    const seconds = sec % 60;

                    const pad = (n) => String(n).padStart(2, '0');

                    if (hours > 0) {
                        return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
                    } else {
                        return `${pad(minutes)}:${pad(seconds)}`;
                    }
                },

                dismissViolation() {
                    this.violationModal = false;
                }
            }))
        })
    </script>
</body>
</html>
