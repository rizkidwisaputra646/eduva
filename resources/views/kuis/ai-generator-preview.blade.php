<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('quizzes.ai-generator.show', $quiz->id) }}" class="p-2 text-[#718096] hover:text-[#263445] hover:bg-[#F5F8FC] rounded-lg transition-colors" title="Kembali ke Konfigurasi">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <x-breadcrumb :items="['Kelas & Mata Pelajaran' => route('kelas.index'), ($quiz->course->title ?? 'Kelas') => route('kelas.show', $quiz->course_id), $quiz->title => route('quizzes.show', $quiz->id), 'Pratinjau AI Question Generator' => '']" />
                    <h2 class="font-bold text-xl sm:text-2xl text-[#263445] leading-tight">
                        Pratinjau &amp; Edit Soal Hasil AI
                    </h2>
                    <p class="text-xs text-[#718096] mt-0.5">Ujian: {{ $quiz->title }} &bull; {{ $quiz->course->title ?? '' }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('quizzes.ai-generator.cancel', $quiz->id) }}">
                @csrf
                <button type="submit" class="px-3.5 py-2 border border-[#E5EAF0] bg-white rounded-xl text-xs font-semibold text-[#718096] hover:text-[#263445] hover:bg-[#F5F8FC]">
                    Batal
                </button>
            </form>
        </div>
    </x-slot>

    <div 
        x-data="aiPreviewManager({
            totalGenerated: {{ count($questions) }},
            regenerateUrl: '{{ route('quizzes.ai-generator.regenerate', $quiz->id) }}',
            csrfToken: '{{ csrf_token() }}'
        })"
        class="max-w-5xl mx-auto space-y-5 pb-28"
    >
        <!-- Ringkasan Hasil Generate & Statistik Soal -->
        <div class="bg-white rounded-2xl border border-[#E5EAF0] p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5 min-w-0">
                <div class="w-11 h-11 rounded-xl bg-[#EEE5F8] text-[#895EB5] border border-[#DDCBEF] flex items-center justify-center shrink-0 font-bold text-xs">
                    {{ $fileFormat }}
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-bold text-[#263445] text-sm sm:text-base truncate">{{ $filename }}</h3>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $questionType === 'multiple_choice' ? 'bg-[#DFEAF9] text-[#438FCE] border border-[#C3DAF5]' : 'bg-[#EEE5F8] text-[#895EB5] border border-[#DDCBEF]' }}">
                            {{ $questionType === 'multiple_choice' ? 'Pilihan Ganda' : 'Esai' }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#F5F8FC] text-[#718096] border border-[#E5EAF0] capitalize">
                            Kesulitan: {{ $difficulty }}
                        </span>
                    </div>
                    <p class="text-xs text-[#718096] mt-1">
                        Periksa, edit pertanyaan, opsi, kunci jawaban, pembahasan, atau hapus soal sebelum disimpan ke bank soal ujian.
                    </p>
                </div>
            </div>

            <!-- Penghitung Soal Berhasil Dibuat vs Aktif -->
            <div class="flex items-center gap-3 shrink-0">
                <div class="px-3.5 py-2 rounded-xl bg-[#F8FAFD] border border-[#E5EAF0] text-center">
                    <p class="text-[10px] font-semibold text-[#718096] uppercase">Dihasilkan</p>
                    <p class="text-base font-bold text-[#263445]" x-text="totalGenerated"></p>
                </div>
                <div class="px-3.5 py-2 rounded-xl bg-[#E0F0E6] border border-[#C4E4CF] text-center">
                    <p class="text-[10px] font-semibold text-[#32865B] uppercase">Siap Disimpan</p>
                    <p class="text-base font-bold text-[#32865B]" x-text="activeCount"></p>
                </div>
            </div>
        </div>

        @if(session('info'))
        <div class="bg-[#E0F0E6] text-[#32865B] p-4 rounded-xl border border-[#C4E4CF] text-xs font-medium flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('info') }}</span>
        </div>
        @endif

        @if($errors->any())
        <div class="bg-[#FDF0F0] text-[#C54B4B] p-4 rounded-xl border border-[#F8D5D5] text-xs space-y-1">
            @foreach($errors->all() as $err)
                <p>&bull; {{ $err }}</p>
            @endforeach
        </div>
        @endif

        <!-- Error / Loading State untuk Generate Ulang -->
        <div x-show="regenError" x-cloak class="bg-[#FDF0F0] border border-[#F8D5D5] text-[#C54B4B] p-4 rounded-xl text-xs flex items-center justify-between gap-3">
            <span x-text="regenError"></span>
            <button type="button" @click="regenError = ''" class="font-bold">&times;</button>
        </div>

        <div x-show="isRegenerating" x-cloak class="bg-[#DFEAF9]/60 border border-[#C3DAF5] text-[#2E6C9E] p-4 rounded-xl text-xs font-semibold flex items-center gap-2.5">
            <svg class="w-4 h-4 animate-spin text-[#438FCE]" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Sedang men-generate ulang variasi soal baru dari dokumen Anda...</span>
        </div>

        <!-- Form Edit & Simpan Soal ke Bank Soal -->
        <form method="POST" action="{{ route('quizzes.ai-generator.store', $quiz->id) }}" id="aiSaveForm" class="space-y-4">
            @csrf

            @foreach($questions as $index => $q)
            <div 
                x-data="{ deleted: false, correctKey: '{{ strtoupper($q['correct_answer'] ?? 'A') }}' }"
                class="bg-white rounded-2xl border border-[#E5EAF0] shadow-xs overflow-hidden transition-all"
                :class="deleted ? 'opacity-60 bg-[#F8FAFD]' : ''"
            >
                <input type="hidden" name="questions[{{ $index }}][delete]" :value="deleted ? '1' : '0'">
                <input type="hidden" name="questions[{{ $index }}][type]" value="{{ $q['type'] }}">

                <!-- Header Kartu Soal -->
                <div class="px-5 py-3.5 bg-[#FAFBFD] border-b border-[#E5EAF0] flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span class="w-7 h-7 rounded-lg bg-[#DFEAF9] text-[#2E6C9E] font-bold text-xs flex items-center justify-center border border-[#C3DAF5]">
                            {{ $index + 1 }}
                        </span>
                        <span class="text-xs font-bold text-[#263445]">
                            {{ $q['type'] === 'multiple_choice' ? 'Pilihan Ganda' : 'Soal Esai' }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-white border border-[#E5EAF0] text-[#718096] capitalize">
                            {{ $q['difficulty'] ?? $difficulty }}
                        </span>
                        <span x-show="deleted" x-cloak class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#FDF0F0] text-[#C54B4B] border border-[#F8D5D5]">
                            Dihapus dari daftar simpan
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5" x-show="!deleted">
                            <label class="text-xs font-semibold text-[#718096]">Poin:</label>
                            <input 
                                type="number" 
                                name="questions[{{ $index }}][points]" 
                                value="{{ $q['points'] ?? ($q['type'] === 'multiple_choice' ? 10 : 20) }}" 
                                min="1" 
                                max="100" 
                                :disabled="deleted"
                                required
                                class="w-16 px-2 py-1 text-xs font-bold text-center rounded-lg border-[#E5EAF0] focus:border-[#438FCE] focus:ring-[#438FCE] text-[#263445]"
                            >
                        </div>

                        <button 
                            type="button" 
                            x-show="!deleted"
                            @click="deleted = true; markDeleted()"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-[#C54B4B] hover:bg-[#FDF0F0] border border-transparent hover:border-[#F8D5D5] transition-colors"
                            title="Hapus soal ini"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            <span>Hapus</span>
                        </button>

                        <button 
                            type="button" 
                            x-show="deleted" 
                            x-cloak
                            @click="deleted = false; restoreQuestion()"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-[#438FCE] bg-white border border-[#C3DAF5] hover:bg-[#DFEAF9]/40 transition-colors"
                        >
                            Kembalikan
                        </button>
                    </div>
                </div>

                <!-- Isi Editor Soal -->
                <div x-show="!deleted" class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-[#263445] uppercase tracking-wider mb-1.5">Pertanyaan</label>
                        <textarea 
                            name="questions[{{ $index }}][question_text]" 
                            rows="3" 
                            :required="!deleted"
                            :disabled="deleted"
                            class="w-full text-sm rounded-xl border-[#E5EAF0] focus:border-[#438FCE] focus:ring-[#438FCE] text-[#263445] leading-relaxed"
                        >{{ old("questions.{$index}.question_text", $q['question_text']) }}</textarea>
                    </div>

                    @if($q['type'] === 'multiple_choice')
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-[#263445] uppercase tracking-wider">Pilihan Jawaban &amp; Kunci Benar</label>
                            <span class="text-[11px] text-[#718096]">Pilih radio button untuk menentukan jawaban benar</span>
                        </div>

                        <div class="grid grid-cols-1 gap-2">
                            @foreach(['A', 'B', 'C', 'D'] as $letter)
                            <div 
                                class="flex items-center gap-2.5 p-2 rounded-xl border transition-colors"
                                :class="correctKey === '{{ $letter }}' ? 'bg-[#E0F0E6]/60 border-[#C4E4CF]' : 'bg-[#F8FAFD] border-[#E5EAF0]'"
                            >
                                <label class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-white border border-[#E5EAF0] cursor-pointer shrink-0">
                                    <input 
                                        type="radio" 
                                        name="questions[{{ $index }}][correct_answer]" 
                                        value="{{ $letter }}" 
                                        x-model="correctKey"
                                        :disabled="deleted"
                                        class="text-[#32865B] focus:ring-[#32865B] h-3.5 w-3.5"
                                    >
                                    <span class="text-xs font-bold" :class="correctKey === '{{ $letter }}' ? 'text-[#32865B]' : 'text-[#263445]'">{{ $letter }}</span>
                                </label>
                                <input 
                                    type="text" 
                                    name="questions[{{ $index }}][options][{{ $letter }}]" 
                                    value="{{ old("questions.{$index}.options.{$letter}", $q['options'][$letter] ?? '') }}" 
                                    :required="!deleted"
                                    :disabled="deleted"
                                    placeholder="Teks pilihan {{ $letter }}"
                                    class="flex-1 text-xs rounded-lg border-[#E5EAF0] focus:border-[#438FCE] focus:ring-[#438FCE] bg-white text-[#263445]"
                                >
                                <span x-show="correctKey === '{{ $letter }}'" class="text-[11px] font-bold text-[#32865B] pr-2 hidden sm:inline">Kunci Benar</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#263445] mb-1">Pembahasan Jawaban (Opsional)</label>
                        <textarea 
                            name="questions[{{ $index }}][explanation]" 
                            rows="2" 
                            :disabled="deleted"
                            class="w-full text-xs rounded-xl border-[#E5EAF0] focus:border-[#438FCE] focus:ring-[#438FCE] text-[#263445]"
                            placeholder="Pembahasan atau penjelasan jawaban..."
                        >{{ old("questions.{$index}.explanation", $q['explanation'] ?? '') }}</textarea>
                    </div>
                    @else
                    <!-- Editor Soal Esai -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[#263445] mb-1">Kunci Jawaban / Contoh Jawaban Ideal</label>
                            <textarea 
                                name="questions[{{ $index }}][ideal_answer]" 
                                rows="3" 
                                :disabled="deleted"
                                class="w-full text-xs rounded-xl border-[#E5EAF0] focus:border-[#438FCE] focus:ring-[#438FCE] text-[#263445]"
                                placeholder="Contoh jawaban ideal yang diharapkan..."
                            >{{ old("questions.{$index}.ideal_answer", $q['ideal_answer'] ?? '') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#263445] mb-1">Pedoman Penilaian / Rubrik Sederhana</label>
                            <textarea 
                                name="questions[{{ $index }}][rubric]" 
                                rows="3" 
                                :disabled="deleted"
                                class="w-full text-xs rounded-xl border-[#E5EAF0] focus:border-[#438FCE] focus:ring-[#438FCE] text-[#263445]"
                                placeholder="Kriteria penilaian jawaban siswa..."
                            >{{ old("questions.{$index}.rubric", $q['rubric'] ?? '') }}</textarea>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach

            <!-- Sticky Action Bar Bawah -->
            <div class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-[#E5EAF0] py-3.5 px-4 z-40 shadow-lg">
                <div class="max-w-5xl mx-auto flex flex-wrap items-center justify-between gap-3">
                    <div class="text-xs text-[#718096]">
                        Tersedia <strong class="text-[#263445]" x-text="activeCount"></strong> dari <strong class="text-[#263445]" x-text="totalGenerated"></strong> soal untuk disimpan ke <strong class="text-[#263445]">{{ $quiz->title }}</strong>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <button 
                            type="button" 
                            @click="regenerateQuestions()"
                            :disabled="isRegenerating"
                            class="px-4 py-2 border border-[#DDCBEF] bg-[#EEE5F8]/60 hover:bg-[#EEE5F8] text-[#895EB5] rounded-xl text-xs font-semibold transition-colors inline-flex items-center gap-1.5 disabled:opacity-50"
                        >
                            <svg class="w-3.5 h-3.5" :class="isRegenerating ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <span x-text="isRegenerating ? 'Menggenerate Ulang...' : 'Generate Ulang'"></span>
                        </button>

                        <button 
                            type="button" 
                            onclick="document.getElementById('aiCancelForm').submit();"
                            class="px-4 py-2 border border-[#E5EAF0] bg-white hover:bg-[#F5F8FC] text-[#718096] rounded-xl text-xs font-semibold transition-colors"
                        >
                            Batal
                        </button>

                        <button 
                            type="submit" 
                            :disabled="activeCount < 1 || isRegenerating"
                            class="px-5 py-2.5 bg-[#438FCE] hover:bg-[#387EB8] text-white rounded-xl text-xs font-bold shadow-xs transition-all inline-flex items-center gap-1.5 disabled:opacity-45 disabled:cursor-not-allowed"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Simpan ke Bank Soal (<span x-text="activeCount"></span>)</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Hidden Form Batal -->
        <form id="aiCancelForm" method="POST" action="{{ route('quizzes.ai-generator.cancel', $quiz->id) }}" class="hidden">
            @csrf
        </form>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('aiPreviewManager', (config) => ({
                totalGenerated: config.totalGenerated || 0,
                activeCount: config.totalGenerated || 0,
                regenerateUrl: config.regenerateUrl,
                csrfToken: config.csrfToken,
                isRegenerating: false,
                regenError: '',

                markDeleted() {
                    if (this.activeCount > 0) {
                        this.activeCount--;
                    }
                },

                restoreQuestion() {
                    if (this.activeCount < this.totalGenerated) {
                        this.activeCount++;
                    }
                },

                async regenerateQuestions() {
                    if (this.isRegenerating) return;
                    this.isRegenerating = true;
                    this.regenError = '';

                    try {
                        const res = await fetch(this.regenerateUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken
                            },
                            body: JSON.stringify({})
                        });

                        const data = await res.json();
                        if (!res.ok || !data.success) {
                            throw new Error(data.error || 'Gagal melakukan generate ulang.');
                        }

                        window.location.href = data.redirect_url;
                    } catch (err) {
                        this.regenError = err.message || 'Gagal menghubungi AI saat generate ulang.';
                        this.isRegenerating = false;
                    }
                }
            }));
        });
    </script>
</x-app-layout>

