<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('quizzes.show', $quiz->id) }}" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" title="Batal & Kembali">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                        Pratinjau Import Soal Dokumen Word
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">Ujian: {{ $quiz->title }} &bull; {{ $quiz->course->title ?? '' }}</p>
                </div>
            </div>
            <div>
                <a href="{{ route('quizzes.show', $quiz->id) }}" class="px-3.5 py-1.5 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50">
                    Batal Import
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto space-y-6 pb-20">
        <!-- Banner Dokumen & Ringkasan -->
        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0 border border-blue-100">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm1 7V3.5L18.5 9H15z"/></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-gray-900 text-sm md:text-base">{{ $filename }}</h3>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $type === 'multiple_choice' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                            {{ $type === 'multiple_choice' ? 'Pilihan Ganda' : 'Esai' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        Sistem mendeteksi <strong>{{ count($questions) }} soal</strong> dari berkas Word. Anda dapat mengedit teks soal, opsi jawaban, kunci, atau menghapus nomor soal yang tidak diinginkan sebelum disimpan.
                    </p>
                </div>
            </div>
        </div>

        @if(!empty($warningNotes))
        <div class="bg-amber-50 rounded-xl border border-amber-200 p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div>
                    <h4 class="text-xs font-bold text-amber-800 uppercase tracking-wider mb-1">Perhatian: Ditemukan Catatan Format pada Beberapa Soal</h4>
                    <p class="text-xs text-amber-700 leading-relaxed mb-2">Beberapa soal memerlukan perbaikan pada pilihan jawaban atau kunci sebelum dikonfirmasi:</p>
                    <ul class="text-xs text-amber-800 list-disc list-inside space-y-0.5">
                        @foreach($warningNotes as $err)
                        <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        @if(session('info'))
        <div class="bg-emerald-50 text-emerald-800 p-4 rounded-xl border border-emerald-200 text-xs flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('info') }}</span>
        </div>
        @endif

        @if($errors->has('general'))
        <div class="bg-red-50 text-red-800 p-4 rounded-xl border border-red-200 text-xs">
            {{ $errors->first('general') }}
        </div>
        @endif

        <!-- Form Konfirmasi & Edit Soal -->
        <form method="POST" action="{{ route('quizzes.import.confirm', $quiz->id) }}" id="importConfirmForm">
            @csrf

            <div class="space-y-5" x-data="{ totalQuestions: {{ count($questions) }} }">
                @foreach($questions as $index => $q)
                <div x-data="{ isDeleted: false }" x-show="!isDeleted" class="bg-white rounded-2xl border transition-all p-5 shadow-xs {{ !empty($q['has_error']) ? 'border-amber-300 ring-2 ring-amber-100' : 'border-gray-200' }}">
                    <input type="hidden" name="questions[{{ $index }}][delete]" :value="isDeleted ? '1' : '0'">
                    <input type="hidden" name="questions[{{ $index }}][type]" value="{{ $q['type'] }}">

                    <!-- Card Header -->
                    <div class="flex items-start justify-between gap-3 pb-3 border-b border-gray-100 mb-4">
                        <div class="flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-800 font-bold text-xs flex items-center justify-center border border-slate-200">
                                {{ $index + 1 }}
                            </span>
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                                {{ $q['type'] === 'multiple_choice' ? 'Soal Pilihan Ganda' : 'Soal Esai' }}
                            </span>
                            @if(!empty($q['has_error']))
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                Perlu Penyesuaian
                            </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1.5">
                                <label class="text-xs font-semibold text-gray-600">Bobot Poin:</label>
                                <input type="number" name="questions[{{ $index }}][points]" value="{{ $q['points'] ?? 10 }}" min="1" max="100" required class="w-16 px-2 py-1 text-xs font-bold text-center rounded-lg border-gray-300 focus:border-primary focus:ring-primary">
                            </div>
                            <button type="button" @click="isDeleted = true; totalQuestions--" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus soal ini dari import">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>

                    @if(!empty($q['has_error']))
                    <div class="mb-3 p-2.5 bg-amber-50 rounded-lg border border-amber-200 text-xs text-amber-800 flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>{{ $q['error_message'] }}</span>
                    </div>
                    @endif

                    <!-- Question Text -->
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Pertanyaan / Soal</label>
                            <textarea name="questions[{{ $index }}][question_text]" rows="3" required class="w-full text-sm rounded-xl border-gray-300 focus:border-primary focus:ring-primary leading-relaxed">{{ $q['question_text'] }}</textarea>
                        </div>

                        @if($q['type'] === 'multiple_choice')
                        <!-- Options & Correct Answer -->
                        <div class="pt-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Pilihan Jawaban & Kunci Benar</label>
                            <div class="space-y-2">
                                @php
                                    $options = $q['options'] ?? [];
                                    $letters = ['A', 'B', 'C', 'D', 'E'];
                                    $correctKey = strtoupper($q['correct_answer'] ?? 'A');
                                @endphp

                                @foreach($letters as $letter)
                                @php $optValue = $options[$letter] ?? ''; @endphp
                                <div class="flex items-center gap-2.5 p-2 rounded-xl border {{ $correctKey === $letter ? 'bg-indigo-50/50 border-indigo-200' : 'bg-gray-50/50 border-gray-200' }}">
                                    <label class="flex items-center gap-1.5 px-2 py-1 rounded bg-white border border-gray-200 cursor-pointer">
                                        <input type="radio" name="questions[{{ $index }}][correct_answer]" value="{{ $letter }}" {{ $correctKey === $letter ? 'checked' : '' }} class="text-primary focus:ring-primary h-3.5 w-3.5">
                                        <span class="text-xs font-bold {{ $correctKey === $letter ? 'text-primary' : 'text-gray-700' }}">{{ $letter }}</span>
                                    </label>
                                    <input type="text" name="questions[{{ $index }}][options][{{ $letter }}]" value="{{ $optValue }}" placeholder="Teks pilihan {{ $letter }} (opsional)" class="flex-1 text-xs rounded-lg border-gray-300 focus:border-primary focus:ring-primary bg-white">
                                    @if($correctKey === $letter)
                                    <span class="text-[11px] font-bold text-indigo-700 pr-2">Kunci Benar</span>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Pembahasan -->
                        <div class="pt-2">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Pembahasan Soal (Opsional)</label>
                            <textarea name="questions[{{ $index }}][explanation]" rows="2" class="w-full text-xs rounded-lg border-gray-300 focus:border-primary text-gray-700" placeholder="Ketikkan pembahasan untuk siswa...">{{ $q['explanation'] ?? '' }}</textarea>
                        </div>

                        @else
                        <!-- Esai: Pedoman Jawaban -->
                        <div class="pt-2">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Pedoman Jawaban / Rubrik Penilaian (Opsional)</label>
                            <textarea name="questions[{{ $index }}][explanation]" rows="3" class="w-full text-xs rounded-lg border-gray-300 focus:border-primary text-gray-700" placeholder="Kriteria penilaian atau kunci jawaban esai...">{{ $q['explanation'] ?? '' }}</textarea>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Sticky Bottom Action Bar -->
            <div class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-gray-200 py-3.5 px-4 z-40 shadow-lg">
                <div class="max-w-5xl mx-auto flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-xs text-gray-600">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Siap mengimpor soal ke ujian <strong>{{ $quiz->title }}</strong></span>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('quizzes.show', $quiz->id) }}" class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            Batalkan
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition-all inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Konfirmasi & Simpan ke Ujian</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
