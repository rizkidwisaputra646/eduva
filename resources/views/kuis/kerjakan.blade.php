<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ujian Aktif - Eduva Monitoring</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 text-gray-900 select-none" x-data="examApp()">
    
    <!-- Topbar Peringatan -->
    <div class="bg-red-600 text-white text-center py-2 px-4 text-sm font-medium shadow-md z-50 relative flex items-center justify-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        MODE UJIAN AKTIF: Dilarang berpindah tab atau keluar dari mode layar penuh. Aktivitas akan dicatat!
    </div>

    <div class="max-w-4xl mx-auto py-8 px-4" x-show="!started">
        <div class="bg-white p-8 rounded-2xl shadow-xl text-center">
            <div class="w-20 h-20 bg-primary/10 text-primary rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            </div>
            <h2 class="text-2xl font-bold mb-4">{{ $quiz->title }}</h2>
            <p class="text-gray-600 mb-8 max-w-lg mx-auto">Pastikan Anda berada di lingkungan yang kondusif. Saat ujian dimulai, browser Anda harus dalam mode layar penuh (Fullscreen) dan Anda dilarang membuka tab/aplikasi lain.</p>
            <button @click="startExam()" class="px-8 py-3 bg-primary hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg transition-transform hover:scale-105">
                Mulai Ujian Sekarang
            </button>
        </div>
    </div>

    <!-- Interface Soal -->
    <div class="max-w-4xl mx-auto py-8 px-4" x-show="started" style="display: none;">
        <div class="flex justify-between items-center mb-6 bg-white p-4 rounded-xl shadow-sm border border-gray-200">
            <div>
                <h3 class="font-bold text-lg text-gray-800">{{ $quiz->title }}</h3>
                <p class="text-sm text-gray-500">{{ $quiz->questions->count() }} Soal</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Sisa Waktu</p>
                    <p class="text-xl font-mono font-bold text-red-600">59:45</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('kuis.submit', $quiz->id) }}" enctype="multipart/form-data">
            @csrf
            
            @forelse($quiz->questions as $index => $q)
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-200 mb-6">
                <p class="text-lg text-gray-800 mb-6 leading-relaxed whitespace-pre-line">
                    {{ $index + 1 }}. {{ $q->question_text }}
                </p>
                
                @if($q->type === 'multiple_choice')
                <div class="space-y-3">
                    @foreach($q->options as $opt)
                    <label class="flex items-center p-4 border border-gray-200 rounded-xl hover:bg-indigo-50 cursor-pointer transition-colors group">
                        <input type="radio" name="answers[{{ $q->id }}]" value="{{ $opt->id }}" class="w-5 h-5 text-primary border-gray-300 focus:ring-primary">
                        <span class="ml-3 text-gray-700 group-hover:text-primary font-medium">{{ $opt->option_text }}</span>
                    </label>
                    @endforeach
                </div>
                @else
                <div class="space-y-4">
                    <textarea name="answers[{{ $q->id }}]" rows="4" class="w-full rounded-xl border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" placeholder="Ketikkan jawaban Anda di sini..."></textarea>
                    
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Atau lampirkan file jawaban (Opsional)</label>
                        <input type="file" name="files[{{ $q->id }}]" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-colors">
                    </div>
                </div>
                @endif
            </div>
            @empty
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-200 mb-6 text-center text-gray-500">
                Belum ada soal untuk kuis ini.
            </div>
            @endforelse

            <div class="flex justify-end mt-6">
                <button type="submit" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow-md">
                    Selesai & Kumpulkan
                </button>
            </div>
        </form>
    </div>

    <!-- Modal Peringatan Pelanggaran -->
    <div x-show="violationModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-70" style="display: none;">
        <div class="bg-white p-8 rounded-2xl max-w-md w-full text-center shadow-2xl transform scale-100">
            <div class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Peringatan Pelanggaran!</h3>
            <p class="text-gray-600 mb-6" x-text="violationMessage"></p>
            <button @click="dismissViolation()" class="w-full px-4 py-3 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700">
                Saya Mengerti, Lanjutkan Ujian
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('examApp', () => ({
                started: false,
                violationModal: false,
                violationMessage: '',
                
                startExam() {
                    this.started = true;
                    if (document.documentElement.requestFullscreen) {
                        document.documentElement.requestFullscreen().catch(err => {
                            console.log(`Error attempting to enable full-screen mode: ${err.message}`);
                        });
                    }
                    this.initMonitoring();
                },

                initMonitoring() {
                    // Monitor Tab Switch
                    document.addEventListener("visibilitychange", () => {
                        if (document.visibilityState === 'hidden' && this.started) {
                            this.logViolation('Berpindah tab atau mengecilkan browser');
                        }
                    });

                    // Monitor Fullscreen Exit
                    document.addEventListener("fullscreenchange", () => {
                        if (!document.fullscreenElement && this.started && !this.violationModal) {
                            this.logViolation('Keluar dari mode layar penuh (Fullscreen)');
                        }
                    });
                },

                logViolation(reason) {
                    this.violationMessage = `Sistem mendeteksi aktivitas yang mencurigakan: ${reason}. Kejadian ini telah dicatat dalam log ujian.`;
                    this.violationModal = true;
                    // TODO: Send AJAX to backend exam_logs
                },

                dismissViolation() {
                    this.violationModal = false;
                    if (document.documentElement.requestFullscreen) {
                        document.documentElement.requestFullscreen();
                    }
                }
            }))
        })
    </script>
</body>
</html>
