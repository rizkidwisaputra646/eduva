<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('quizzes.show', $quiz->id) }}" class="p-2 text-[#718096] hover:text-[#263445] hover:bg-[#F5F8FC] rounded-lg transition-colors" title="Kembali ke Kelola Soal">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <x-breadcrumb :items="['Kelas & Mata Pelajaran' => route('kelas.index'), ($quiz->course->title ?? 'Kelas') => route('kelas.show', $quiz->course_id), $quiz->title => route('quizzes.show', $quiz->id), 'AI Question Generator' => '']" />
                    <h2 class="font-bold text-xl sm:text-2xl text-[#263445] leading-tight">
                        AI Question Generator
                    </h2>
                    <p class="text-xs text-[#718096] mt-0.5">Kuis: {{ $quiz->title }} &bull; {{ $quiz->course->title ?? '' }}</p>
                </div>
            </div>
            <a href="{{ route('quizzes.show', $quiz->id) }}" class="px-3.5 py-2 border border-[#E5EAF0] bg-white rounded-xl text-xs font-semibold text-[#718096] hover:text-[#263445] hover:bg-[#F5F8FC] transition-colors self-start sm:self-auto">
                Kembali ke Soal
            </a>
        </div>
    </x-slot>

    <div 
        x-data="aiGeneratorForm({
            generateUrl: '{{ route('quizzes.ai-generator.generate', $quiz->id) }}',
            csrfToken: '{{ csrf_token() }}',
            maxQuestions: {{ $maxQuestions }},
            maxFileSizeMb: {{ $maxFileSizeMb }},
            initialType: '{{ old('question_type', 'multiple_choice') }}',
            initialCount: {{ (int) old('question_count', 5) }},
            initialDifficulty: '{{ old('difficulty', 'sedang') }}'
        })"
        class="max-w-3xl mx-auto space-y-5 pb-12"
    >
        @if($hasPreviewSession)
        <div class="bg-[#DFEAF9]/70 border border-[#C3DAF5] rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="text-xs text-[#2E6C9E]">
                <p class="font-bold">Pratinjau Soal Sebelumnya Masih Tersedia</p>
                <p class="mt-0.5">Anda masih memiliki hasil generate yang belum disimpan untuk kuis ini.</p>
            </div>
            <a href="{{ route('quizzes.ai-generator.preview', $quiz->id) }}" class="px-3.5 py-2 bg-[#438FCE] hover:bg-[#387EB8] text-white rounded-lg text-xs font-semibold transition-colors shrink-0 text-center">
                Buka Pratinjau &rarr;
            </a>
        </div>
        @endif

        @if(session('error'))
        <div class="bg-[#FDF0F0] border border-[#F8D5D5] text-[#C54B4B] p-4 rounded-xl text-xs font-medium">
            {{ session('error') }}
        </div>
        @endif

        @if($errors->any())
        <div class="bg-[#FDF0F0] border border-[#F8D5D5] text-[#C54B4B] p-4 rounded-xl text-xs space-y-1">
            @foreach($errors->all() as $err)
                <p>&bull; {{ $err }}</p>
            @endforeach
        </div>
        @endif

        <!-- Error Alert dari Proses AJAX / Ekstraksi -->
        <div x-show="errorMessage" x-cloak class="bg-[#FDF0F0] border border-[#F8D5D5] rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start gap-2.5 text-xs text-[#C54B4B]">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div>
                    <p class="font-bold">Gagal Memproses Dokumen</p>
                    <p class="mt-0.5 leading-relaxed" x-text="errorMessage"></p>
                </div>
            </div>
            <button type="button" @click="submitForm()" x-show="selectedFile && !isGenerating" class="px-3 py-1.5 bg-white border border-[#F8D5D5] text-[#C54B4B] hover:bg-red-50 rounded-lg text-xs font-semibold shrink-0">
                Coba Lagi
            </button>
        </div>

        <form @submit.prevent="submitForm()" action="{{ route('quizzes.ai-generator.generate', $quiz->id) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl border border-[#E5EAF0] shadow-xs overflow-hidden">
            @csrf

            <div class="p-6 space-y-6">
                <!-- A. Upload Dokumen Pembelajaran -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-bold text-[#263445]">1. Dokumen Materi Pembelajaran</label>
                        <span class="text-[11px] text-[#718096]">Maks. {{ $maxFileSizeMb }} MB &bull; PDF, DOC, DOCX, PPT, PPTX, TXT</span>
                    </div>

                    <!-- Drag & Drop Zone -->
                    <div 
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleDrop($event)"
                        :class="isDragging ? 'border-[#438FCE] bg-[#DFEAF9]/40' : (selectedFile ? 'border-[#C4E4CF] bg-[#E0F0E6]/25' : 'border-[#E5EAF0] bg-[#F8FAFD] hover:border-[#438FCE]/60 hover:bg-white')"
                        class="relative border-2 border-dashed rounded-2xl p-6 text-center transition-all"
                    >
                        <input 
                            x-ref="fileInput"
                            type="file" 
                            name="document" 
                            accept=".pdf,.doc,.docx,.ppt,.pptx,.txt"
                            @change="handleFileSelect($event)"
                            class="hidden"
                        />

                        <!-- Belum Ada File -->
                        <div x-show="!selectedFile" class="space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-[#DFEAF9] text-[#438FCE] flex items-center justify-center mx-auto">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-[#263445]">Seret dan lepaskan dokumen di sini</p>
                                <p class="text-xs text-[#718096] mt-1">Atau klik tombol di bawah untuk memilih file dari perangkat Anda</p>
                            </div>
                            <button 
                                type="button" 
                                @click="$refs.fileInput.click()" 
                                class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-[#E5EAF0] hover:border-[#438FCE] text-[#263445] hover:text-[#438FCE] rounded-xl text-xs font-semibold shadow-2xs transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                Pilih File Dokumen
                            </button>
                        </div>

                        <!-- File Terpilih -->
                        <div x-show="selectedFile" x-cloak class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-left bg-white border border-[#E5EAF0] rounded-xl p-4 shadow-2xs">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-[#E0F0E6] text-[#32865B] border border-[#C4E4CF] flex items-center justify-center font-bold text-[11px] shrink-0" x-text="fileInfo.format"></div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-[#263445] truncate" x-text="fileInfo.name"></p>
                                    <div class="flex items-center gap-2 mt-0.5 text-[11px] text-[#718096] flex-wrap">
                                        <span>Format: <strong class="text-[#263445]" x-text="fileInfo.format"></strong></span>
                                        <span>&bull;</span>
                                        <span>Ukuran: <strong class="text-[#263445]" x-text="fileInfo.sizeFormatted"></strong></span>
                                        <span>&bull;</span>
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-[#E0F0E6] text-[#32865B] font-semibold">
                                            Siap Diproses
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <button 
                                    type="button" 
                                    @click="$refs.fileInput.click()" 
                                    :disabled="isGenerating"
                                    class="px-3 py-1.5 border border-[#E5EAF0] rounded-lg text-xs font-semibold text-[#263445] hover:bg-[#F5F8FC] disabled:opacity-50"
                                >
                                    Ganti File
                                </button>
                                <button 
                                    type="button" 
                                    @click="clearFile()" 
                                    :disabled="isGenerating"
                                    class="px-3 py-1.5 border border-[#F8D5D5] bg-[#FDF0F0] text-[#C54B4B] hover:bg-red-100 rounded-lg text-xs font-semibold disabled:opacity-50"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- B. Jenis Soal & C. Jumlah Soal -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Pilihan Jenis Soal (Satu Jenis per Generate) -->
                    <div>
                        <label class="block text-sm font-bold text-[#263445] mb-2">2. Jenis Soal</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label 
                                class="border rounded-xl p-3.5 cursor-pointer transition-all flex items-start gap-2.5"
                                :class="questionType === 'multiple_choice' ? 'border-[#438FCE] bg-[#DFEAF9]/50 ring-2 ring-[#438FCE]/15' : 'border-[#E5EAF0] hover:bg-[#F8FAFD]'"
                            >
                                <input type="radio" name="question_type" value="multiple_choice" x-model="questionType" class="mt-0.5 text-[#438FCE] focus:ring-[#438FCE]">
                                <div>
                                    <p class="text-xs font-bold text-[#263445]">Pilihan Ganda</p>
                                    <p class="text-[11px] text-[#718096] mt-0.5">Opsi A&ndash;D &amp; kunci</p>
                                </div>
                            </label>

                            <label 
                                class="border rounded-xl p-3.5 cursor-pointer transition-all flex items-start gap-2.5"
                                :class="questionType === 'essay' ? 'border-[#895EB5] bg-[#EEE5F8]/50 ring-2 ring-[#895EB5]/15' : 'border-[#E5EAF0] hover:bg-[#F8FAFD]'"
                            >
                                <input type="radio" name="question_type" value="essay" x-model="questionType" class="mt-0.5 text-[#895EB5] focus:ring-[#895EB5]">
                                <div>
                                    <p class="text-xs font-bold text-[#263445]">Esai</p>
                                    <p class="text-[11px] text-[#718096] mt-0.5">Uraian &amp; rubrik</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Jumlah Soal (Input Angka + Tombol Minus & Plus) -->
                    <div>
                        <label for="question_count" class="block text-sm font-bold text-[#263445] mb-2">3. Jumlah Soal</label>
                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                @click="decrementCount()" 
                                :disabled="questionCount <= 1 || isGenerating"
                                class="w-10 h-10 rounded-xl border border-[#E5EAF0] bg-[#F8FAFD] hover:bg-white text-[#263445] font-bold text-base flex items-center justify-center transition-colors disabled:opacity-40"
                                title="Kurangi jumlah soal"
                            >&minus;</button>
                            <input 
                                id="question_count"
                                type="number" 
                                name="question_count" 
                                x-model.number="questionCount"
                                @blur="normalizeCount()"
                                min="1" 
                                :max="maxQuestions" 
                                required
                                class="w-24 h-10 text-center font-bold text-sm rounded-xl border-[#E5EAF0] focus:border-[#438FCE] focus:ring-[#438FCE] text-[#263445]"
                            />
                            <button 
                                type="button" 
                                @click="incrementCount()" 
                                :disabled="questionCount >= maxQuestions || isGenerating"
                                class="w-10 h-10 rounded-xl border border-[#E5EAF0] bg-[#F8FAFD] hover:bg-white text-[#263445] font-bold text-base flex items-center justify-center transition-colors disabled:opacity-40"
                                title="Tambah jumlah soal"
                            >+</button>
                        </div>
                        <p class="text-xs text-[#718096] mt-2">
                            Akan dihasilkan <strong class="text-[#263445]" x-text="displayCountText"></strong>
                            <span x-text="questionType === 'multiple_choice' ? 'soal Pilihan Ganda' : 'soal Esai'"></span>
                            <span class="text-[11px]">(1 &ndash; {{ $maxQuestions }} soal).</span>
                        </p>
                    </div>
                </div>

                <!-- D. Tingkat Kesulitan -->
                <div>
                    <label class="block text-sm font-bold text-[#263445] mb-2">4. Tingkat Kesulitan</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        @foreach(['mudah' => ['Mudah', 'Pemahaman dasar'], 'sedang' => ['Sedang', 'Penerapan konsep'], 'sulit' => ['Sulit', 'Analisis & HOTS'], 'campuran' => ['Campuran', 'Kombinasi merata']] as $val => $meta)
                        <label 
                            class="border rounded-xl p-3 cursor-pointer transition-all text-left"
                            :class="difficulty === '{{ $val }}' ? 'border-[#438FCE] bg-[#DFEAF9]/50 ring-2 ring-[#438FCE]/15' : 'border-[#E5EAF0] hover:bg-[#F8FAFD]'"
                        >
                            <input type="radio" name="difficulty" value="{{ $val }}" x-model="difficulty" class="sr-only">
                            <p class="text-xs font-bold text-[#263445]">{{ $meta[0] }}</p>
                            <p class="text-[11px] text-[#718096] mt-0.5">{{ $meta[1] }}</p>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- Loading State & Progress Informatif -->
                <div x-show="isGenerating" x-cloak class="bg-[#F8FAFD] border border-[#C3DAF5] rounded-2xl p-4 space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2 font-bold text-[#2E6C9E]">
                            <svg class="w-4 h-4 animate-spin text-[#438FCE]" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="progressStageLabel"></span>
                        </div>
                        <span class="font-semibold text-[#438FCE]" x-text="progressPercent + '%'"></span>
                    </div>
                    <div class="w-full h-2 bg-[#E5EAF0] rounded-full overflow-hidden">
                        <div class="h-full bg-[#438FCE] transition-all duration-500 rounded-full" :style="'width: ' + progressPercent + '%'"></div>
                    </div>
                    <p class="text-[11px] text-[#718096]">
                        Mohon tunggu, AI sedang membaca isi dokumen dan menyusun soal sesuai konfigurasi. Anda akan melihat halaman pratinjau sebelum soal disimpan.
                    </p>
                </div>
            </div>

            <!-- Form Footer -->
            <div class="px-6 py-4 bg-[#FAFBFD] border-t border-[#E5EAF0] flex flex-col-reverse sm:flex-row sm:items-center justify-between gap-3">
                <a href="{{ route('quizzes.show', $quiz->id) }}" class="px-4 py-2.5 border border-[#E5EAF0] bg-white rounded-xl text-xs font-semibold text-[#718096] hover:text-[#263445] hover:bg-[#F5F8FC] text-center">
                    Batal
                </a>

                <button 
                    type="submit" 
                    :disabled="isGenerating || !selectedFile"
                    class="px-5 py-2.5 bg-[#895EB5] hover:bg-[#784FA3] text-white rounded-xl text-xs font-bold shadow-xs transition-all inline-flex items-center justify-center gap-2 disabled:opacity-45 disabled:cursor-not-allowed"
                >
                    <svg x-show="!isGenerating" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span x-text="isGenerating ? 'Sedang Memproses Soal...' : 'Generate Soal dengan AI'"></span>
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('aiGeneratorForm', (config) => ({
                generateUrl: config.generateUrl,
                csrfToken: config.csrfToken,
                maxQuestions: config.maxQuestions || 20,
                maxFileSizeMb: config.maxFileSizeMb || 15,
                allowedExtensions: ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt'],
                selectedFile: null,
                fileInfo: { name: '', format: '', sizeFormatted: '' },
                isDragging: false,
                questionType: config.initialType || 'multiple_choice',
                questionCount: config.initialCount || 5,
                difficulty: config.initialDifficulty || 'sedang',
                isGenerating: false,
                progressPercent: 0,
                progressStageLabel: '',
                progressTimer: null,
                errorMessage: '',

                get displayCountText() {
                    const n = parseInt(this.questionCount, 10);
                    return (!n || n < 1) ? '1' : String(Math.min(this.maxQuestions, n));
                },

                handleFileSelect(event) {
                    const file = event.target.files?.[0];
                    if (file) {
                        this.attachFile(file);
                    }
                },

                handleDrop(event) {
                    this.isDragging = false;
                    const file = event.dataTransfer?.files?.[0];
                    if (file) {
                        this.attachFile(file);
                    }
                },

                attachFile(file) {
                    this.errorMessage = '';
                    const parts = file.name.split('.');
                    const ext = parts.length > 1 ? parts.pop().toLowerCase() : '';

                    if (!this.allowedExtensions.includes(ext)) {
                        this.errorMessage = 'Format berkas tidak didukung. Gunakan file PDF, DOC, DOCX, PPT, PPTX, atau TXT.';
                        this.clearFile();
                        return;
                    }

                    if (file.size === 0) {
                        this.errorMessage = 'Berkas kosong (0 byte). Pilih dokumen yang berisi materi pembelajaran.';
                        this.clearFile();
                        return;
                    }

                    const maxBytes = this.maxFileSizeMb * 1024 * 1024;
                    if (file.size > maxBytes) {
                        this.errorMessage = `Ukuran berkas melebihi batas maksimal ${this.maxFileSizeMb} MB.`;
                        this.clearFile();
                        return;
                    }

                    this.selectedFile = file;
                    this.fileInfo = {
                        name: file.name,
                        format: ext.toUpperCase(),
                        sizeFormatted: this.formatBytes(file.size)
                    };
                },

                clearFile() {
                    this.selectedFile = null;
                    this.fileInfo = { name: '', format: '', sizeFormatted: '' };
                    if (this.$refs.fileInput) {
                        this.$refs.fileInput.value = '';
                    }
                },

                formatBytes(bytes) {
                    if (bytes < 1024) return bytes + ' B';
                    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
                },

                incrementCount() {
                    this.normalizeCount();
                    if (this.questionCount < this.maxQuestions) {
                        this.questionCount++;
                    }
                },

                decrementCount() {
                    this.normalizeCount();
                    if (this.questionCount > 1) {
                        this.questionCount--;
                    }
                },

                normalizeCount() {
                    let val = parseInt(this.questionCount, 10);
                    if (isNaN(val) || val < 1) val = 1;
                    if (val > this.maxQuestions) val = this.maxQuestions;
                    this.questionCount = val;
                },

                startProgressAnimation() {
                    this.progressPercent = 18;
                    this.progressStageLabel = 'Mengunggah & mengekstrak teks dokumen...';

                    this.progressTimer = setInterval(() => {
                        if (this.progressPercent < 45) {
                            this.progressPercent += 7;
                            this.progressStageLabel = 'Menganalisis isi materi pembelajaran...';
                        } else if (this.progressPercent < 88) {
                            this.progressPercent += 5;
                            this.progressStageLabel = `Menyusun ${this.questionCount} soal terstruktur...`;
                        }
                    }, 900);
                },

                stopProgressAnimation() {
                    if (this.progressTimer) {
                        clearInterval(this.progressTimer);
                        this.progressTimer = null;
                    }
                },

                async submitForm() {
                    this.errorMessage = '';
                    this.normalizeCount();

                    if (!this.selectedFile) {
                        this.errorMessage = 'Silakan pilih atau seret dokumen pembelajaran terlebih dahulu.';
                        return;
                    }

                    const formData = new FormData();
                    formData.append('document', this.selectedFile);
                    formData.append('question_type', this.questionType);
                    formData.append('question_count', this.questionCount);
                    formData.append('difficulty', this.difficulty);

                    this.isGenerating = true;
                    this.startProgressAnimation();

                    try {
                        const res = await fetch(this.generateUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken
                            },
                            body: formData
                        });

                        const data = await res.json();
                        if (!res.ok || !data.success) {
                            throw new Error(data.error || 'Terjadi kendala saat membuat soal dari dokumen.');
                        }

                        this.progressPercent = 100;
                        this.progressStageLabel = 'Soal berhasil disusun! Membuka pratinjau...';
                        window.location.href = data.redirect_url;
                    } catch (err) {
                        this.errorMessage = err.message || 'Gagal menghubungi layanan AI. Silakan coba lagi.';
                        this.isGenerating = false;
                        this.stopProgressAnimation();
                    }
                }
            }));
        });
    </script>
</x-app-layout>

