<x-app-layout>
    <a class="block p-4 text-blue-700" href="{{ url('/guru/ujian/'.$quiz->id.'/monitoring') }}">Live Monitoring Ujian</a>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <x-breadcrumb :items="['Kelas & Mata Pelajaran' => route('kelas.index'), ($quiz->course->title ?? 'Kelas') => route('kelas.show', $quiz->course_id), ('Kelola Soal ' . $quiz->title) => '']" />
                <h2 class="font-bold text-2xl text-gray-900 leading-tight flex items-center">
                    Kelola Kuis/Tugas: {{ $quiz->title }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Mata Pelajaran: {{ $quiz->course->title }}</p>
            </div>
            <a href="{{ route('kelas.show', $quiz->course_id) }}" class="text-xs font-semibold text-gray-500 hover:text-gray-700">
                &larr; Kembali ke Kelas
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="bg-[#E0F0E6] text-[#32865B] p-4 rounded-xl border border-[#C4E4CF] text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if(session('info'))
            <div class="bg-[#DFEAF9] text-[#2E6C9E] p-4 rounded-xl border border-[#C3DAF5] text-sm font-medium">
                {{ session('info') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-xs border border-[#E5EAF0] p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex items-center flex-wrap gap-2 mb-1">
                    <h3 class="font-bold text-lg text-[#263445]">Total Soal: {{ $quiz->questions->count() }}</h3>
                    @if($quiz->detect_tab_switch)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#FDF5EB] text-[#B8873E] border border-[#F7E1C8]">
                        <svg class="w-3.5 h-3.5 text-[#B8873E]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Deteksi Pindah Tab: Aktif
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#F5F8FC] text-[#718096] border border-[#E5EAF0]">
                        Deteksi Pindah Tab: Nonaktif
                    </span>
                    @endif

                    @if($quiz->duration)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#DFEAF9] text-[#438FCE] border border-[#C3DAF5]">
                        <svg class="w-3.5 h-3.5 text-[#438FCE]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Durasi: {{ $quiz->formatted_duration }}
                    </span>
                    @endif
                </div>
                <p class="text-sm text-[#718096]">Total Poin Maksimal: {{ $quiz->questions->sum('points') }}</p>
            </div>
        </div>

        <!-- Dua Metode Pembuatan Soal: Manual vs AI Question Generator -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Metode 1: Buat Soal Manual -->
            <div class="bg-white rounded-xl border border-[#E5EAF0] p-5 shadow-xs flex flex-col justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5 mb-1.5">
                        <div class="w-8 h-8 rounded-lg bg-[#DFEAF9] text-[#438FCE] flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </div>
                        <h4 class="font-bold text-sm text-[#263445]">Buat Soal Manual &amp; Template</h4>
                    </div>
                    <p class="text-xs text-[#718096] leading-relaxed">
                        Tambahkan soal pilihan ganda atau esai satu per satu, atau impor massal menggunakan template dokumen Word (.docx).
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <button x-data @click="$dispatch('open-modal', 'add-mcq')" class="px-3.5 py-2 bg-[#DFEAF9] text-[#2E6C9E] rounded-lg font-semibold text-xs hover:bg-[#C3DAF5]/70 transition-colors">
                        + Pilihan Ganda
                    </button>
                    <button x-data @click="$dispatch('open-modal', 'add-essay')" class="px-3.5 py-2 bg-[#438FCE] text-white rounded-lg font-semibold text-xs hover:bg-[#387EB8] transition-colors shadow-2xs">
                        + Esai
                    </button>
                    <button x-data @click="$dispatch('open-modal', 'import-word-modal')" class="px-3.5 py-2 bg-[#E0F0E6] text-[#32865B] hover:bg-[#D4EBDC] border border-[#C4E4CF] rounded-lg font-semibold text-xs transition-colors inline-flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-[#32865B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Import Word (.docx)
                    </button>
                </div>
            </div>

            <!-- Metode 2: AI Question Generator -->
            <div class="bg-white rounded-xl border border-[#E5EAF0] p-5 shadow-xs flex flex-col justify-between gap-4">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-1.5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-[#EEE5F8] text-[#895EB5] flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            <h4 class="font-bold text-sm text-[#263445]">AI Question Generator</h4>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#EEE5F8] text-[#895EB5] border border-[#DDCBEF]">Otomatis dari Dokumen</span>
                    </div>
                    <p class="text-xs text-[#718096] leading-relaxed">
                        Unggah materi PDF, Word, PowerPoint, atau TXT agar AI menyusun soal pilihan ganda atau esai siap periksa &amp; edit.
                    </p>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <a href="{{ route('quizzes.ai-generator.show', $quiz->id) }}" class="px-4 py-2 bg-[#895EB5] hover:bg-[#784FA3] text-white rounded-lg font-semibold text-xs transition-colors shadow-2xs inline-flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <span>Buat Soal dengan AI</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            @forelse($quiz->questions as $index => $question)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-start mb-4">
                    <h4 class="font-bold text-lg text-gray-900">
                        <span class="text-gray-400 mr-1">{{ $index + 1 }}.</span> {{ $question->question_text }}
                    </h4>
                    <div class="flex items-center gap-2">
                        <span class="inline-block px-2.5 py-1 bg-gray-100 text-gray-700 rounded text-xs font-semibold">
                            {{ $question->points }} Poin
                        </span>
                        <button type="button" x-data @click="$dispatch('open-modal', 'edit-question-{{ $question->id }}')" class="p-1 text-gray-400 hover:text-indigo-600 rounded hover:bg-indigo-50 transition-colors" title="Edit Soal">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        <form method="POST" action="{{ route('questions.destroy', $question->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus soal ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1 text-gray-400 hover:text-red-600 rounded hover:bg-red-50 transition-colors" title="Hapus Soal">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </div>
                </div>
                
                @if($question->type === 'multiple_choice')
                    <div class="space-y-2 mt-4 pl-6 border-l-2 border-gray-100">
                        @foreach($question->options as $optIndex => $option)
                        <div class="flex items-center p-2 rounded {{ $option->is_correct ? 'bg-emerald-50 border border-emerald-200' : 'bg-gray-50 border border-transparent' }}">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold mr-3 {{ $option->is_correct ? 'bg-emerald-500 text-white' : 'bg-gray-200 text-gray-700' }}">
                                {{ chr(65 + $optIndex) }}
                            </span>
                            <span class="{{ $option->is_correct ? 'text-emerald-900 font-medium' : 'text-gray-700' }}">
                                {{ $option->option_text }}
                            </span>
                            @if($option->is_correct)
                                <svg class="w-5 h-5 ml-auto text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4 pl-6 border-l-2 border-gray-100">
                        <p class="text-sm text-gray-500 italic">Tipe: Soal Esai (Penilaian manual atau bantuan AI Tutor)</p>
                    </div>
                @endif

                @if($question->explanation)
                <div class="mt-3.5 p-3 bg-slate-50 rounded-lg border border-slate-200/80 text-xs text-slate-700 flex items-start gap-2">
                    <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <span class="font-bold text-slate-800">{{ $question->type === 'multiple_choice' ? 'Pembahasan:' : 'Pedoman Jawaban:' }}</span>
                        <span>{{ $question->explanation }}</span>
                    </div>
                </div>
                @endif

                <!-- Modal Edit Soal -->
                <x-modal name="edit-question-{{ $question->id }}" focusable>
                    <form method="POST" action="{{ route('questions.update', $question->id) }}" class="p-6">
                        @csrf
                        @method('PUT')
                        
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                            <h2 class="text-lg font-bold text-gray-900">
                                Edit {{ $question->type === 'multiple_choice' ? 'Soal Pilihan Ganda' : 'Soal Esai' }} #{{ $index + 1 }}
                            </h2>
                            <span class="text-xs px-2.5 py-0.5 rounded-full font-bold {{ $question->type === 'multiple_choice' ? 'bg-indigo-50 text-indigo-700' : 'bg-emerald-50 text-emerald-700' }}">
                                {{ $question->type === 'multiple_choice' ? 'Pilihan Ganda' : 'Esai' }}
                            </span>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Pertanyaan / Soal</label>
                                <textarea name="question_text" required rows="3" class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary leading-relaxed">{{ $question->question_text }}</textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Bobot Poin Nilai</label>
                                <input type="number" name="points" value="{{ $question->points }}" min="1" max="100" required class="w-full text-sm rounded-lg border-gray-300 focus:border-primary focus:ring-primary">
                            </div>

                            @if($question->type === 'multiple_choice')
                                @php
                                    $opts = $question->options->values();
                                    $letters = ['A', 'B', 'C', 'D', 'E'];
                                    $correctIndex = 0;
                                    foreach($opts as $i => $o) {
                                        if ($o->is_correct) { $correctIndex = $i; break; }
                                    }
                                @endphp
                                <div class="pt-3 border-t border-gray-100 space-y-2.5">
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Pilihan Jawaban & Kunci Benar</label>
                                        <span class="text-[11px] text-gray-400">Pilih salah satu radio sebagai kunci</span>
                                    </div>

                                    @foreach($letters as $lIdx => $letter)
                                    @php
                                        $optText = isset($opts[$lIdx]) ? $opts[$lIdx]->option_text : '';
                                    @endphp
                                    <div class="flex gap-2 items-center">
                                        <span class="w-7 font-bold text-xs text-gray-600 text-center">{{ $letter }}</span>
                                        <input type="text" name="options[{{ $lIdx }}]" value="{{ $optText }}" placeholder="Teks pilihan {{ $letter }} {{ $lIdx >= 4 ? '(opsional)' : '' }}" {{ $lIdx < 2 ? 'required' : '' }} class="flex-1 text-sm rounded-lg border-gray-300 focus:border-primary">
                                        <label class="flex items-center text-xs font-medium text-gray-700 cursor-pointer">
                                            <input type="radio" name="correct_option" value="{{ $lIdx }}" {{ $correctIndex == $lIdx ? 'checked' : '' }} class="mr-1 text-primary focus:ring-primary">
                                            Benar
                                        </label>
                                    </div>
                                    @endforeach
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Pembahasan Soal (Opsional)</label>
                                    <textarea name="explanation" rows="2" class="w-full text-sm rounded-lg border-gray-300 focus:border-primary text-gray-700" placeholder="Tuliskan pembahasan atau rumus untuk siswa...">{{ $question->explanation }}</textarea>
                                </div>
                            @else
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Pedoman Jawaban / Rubrik Penilaian (Opsional)</label>
                                    <textarea name="explanation" rows="3" class="w-full text-sm rounded-lg border-gray-300 focus:border-primary text-gray-700" placeholder="Poin penting atau kriteria yang harus dijawab siswa...">{{ $question->explanation }}</textarea>
                                </div>
                            @endif
                        </div>

                        <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-gray-100">
                            <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-medium">Batal</button>
                            <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-lg hover:bg-primary-dark text-xs font-semibold shadow-xs">Simpan Perubahan Soal</button>
                        </div>
                    </form>
                </x-modal>
            </div>
            @empty
            <div class="bg-white p-8 text-center rounded-xl border border-gray-200 text-gray-500">
                Belum ada soal. Silakan tambahkan soal pilihan ganda atau esai.
            </div>
            @endforelse
        </div>

        <!-- Modal Pilihan Ganda -->
        <x-modal name="add-mcq" focusable>
            <form method="post" action="{{ route('questions.store', $quiz->id) }}" class="p-6">
                @csrf
                <input type="hidden" name="type" value="multiple_choice">
                
                <h2 class="text-lg font-bold text-gray-900 mb-4">Tambah Soal Pilihan Ganda</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pertanyaan</label>
                        <textarea name="question_text" required rows="3" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Poin / Bobot Nilai</label>
                        <input type="number" name="points" value="10" min="1" required class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20">
                    </div>
                    
                    <div class="pt-4 border-t border-gray-100 space-y-3">
                        <p class="text-sm font-medium text-gray-700 mb-2">Pilihan Jawaban</p>
                        
                        <div class="flex gap-2 items-center">
                            <span class="w-8 font-bold text-gray-500 text-center">A</span>
                            <input type="text" name="options[]" required class="flex-1 rounded-lg border-gray-300 focus:border-primary">
                            <label class="flex items-center text-sm"><input type="radio" name="correct_option" value="0" required class="mr-1 text-primary"> Benar</label>
                        </div>
                        <div class="flex gap-2 items-center">
                            <span class="w-8 font-bold text-gray-500 text-center">B</span>
                            <input type="text" name="options[]" required class="flex-1 rounded-lg border-gray-300 focus:border-primary">
                            <label class="flex items-center text-sm"><input type="radio" name="correct_option" value="1" required class="mr-1 text-primary"> Benar</label>
                        </div>
                        <div class="flex gap-2 items-center">
                            <span class="w-8 font-bold text-gray-500 text-center">C</span>
                            <input type="text" name="options[]" required class="flex-1 rounded-lg border-gray-300 focus:border-primary">
                            <label class="flex items-center text-sm"><input type="radio" name="correct_option" value="2" required class="mr-1 text-primary"> Benar</label>
                        </div>
                        <div class="flex gap-2 items-center">
                            <span class="w-8 font-bold text-gray-500 text-center">D</span>
                            <input type="text" name="options[]" required class="flex-1 rounded-lg border-gray-300 focus:border-primary">
                            <label class="flex items-center text-sm"><input type="radio" name="correct_option" value="3" required class="mr-1 text-primary"> Benar</label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pembahasan / Penjelasan Jawaban (Opsional)</label>
                        <textarea name="explanation" rows="2" class="w-full rounded-lg border-gray-300 focus:border-primary text-sm" placeholder="Tuliskan pembahasan untuk membantu pemahaman siswa..."></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark font-medium">Simpan Soal</button>
                </div>
            </form>
        </x-modal>

        <!-- Modal Esai -->
        <x-modal name="add-essay" focusable>
            <form method="post" action="{{ route('questions.store', $quiz->id) }}" class="p-6">
                @csrf
                <input type="hidden" name="type" value="essay">
                
                <h2 class="text-lg font-bold text-gray-900 mb-4">Tambah Soal Esai</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pertanyaan Esai</label>
                        <textarea name="question_text" required rows="4" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20" placeholder="Ketikkan instruksi atau pertanyaan esai..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Poin / Bobot Nilai</label>
                        <input type="number" name="points" value="20" min="1" required class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pedoman Jawaban / Rubrik Penilaian (Opsional)</label>
                        <textarea name="explanation" rows="2" class="w-full rounded-lg border-gray-300 focus:border-primary text-sm" placeholder="Poin penting atau kriteria yang harus dijawab siswa..."></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark font-medium">Simpan Soal</button>
                </div>
            </form>
        </x-modal>

        <!-- Modal Import Soal Word (.docx) -->
        <x-modal name="import-word-modal" focusable>
            <div x-data="{ qType: 'multiple_choice' }" class="p-6">
                <div class="flex items-center gap-3 pb-4 border-b border-gray-100 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Import Soal dari Microsoft Word</h2>
                        <p class="text-xs text-gray-500">Unggah berkas .docx yang berisi daftar soal pilihan ganda atau esai.</p>
                    </div>
                </div>

                <!-- Step 1: Download Template -->
                <div class="mb-6 p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-800 text-[11px] font-bold flex items-center justify-center">1</span>
                            Unduh Template Dokumen Word
                        </span>
                    </div>
                    <p class="text-xs text-slate-600 mb-3">
                        Gunakan template resmi Eduva agar format tabel nomor, pertanyaan, pilihan jawaban, kunci, dan bobot terbaca sempurna oleh sistem.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <a href="{{ route('quizzes.templates.download', 'multiple_choice') }}" class="flex items-center justify-between p-3 bg-white hover:bg-blue-50/70 border border-slate-200 hover:border-blue-300 rounded-lg text-xs font-semibold text-slate-800 transition-colors shadow-xs group">
                            <div class="flex items-center gap-2">
                                <span class="p-1.5 rounded bg-blue-100 text-blue-700 group-hover:bg-blue-200">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm1 7V3.5L18.5 9H15z"/></svg>
                                </span>
                                <span>Template Pilihan Ganda</span>
                            </div>
                            <span class="text-[11px] text-blue-600 font-bold group-hover:translate-x-0.5 transition-transform">&darr; .docx</span>
                        </a>
                        <a href="{{ route('quizzes.templates.download', 'essay') }}" class="flex items-center justify-between p-3 bg-white hover:bg-emerald-50/70 border border-slate-200 hover:border-emerald-300 rounded-lg text-xs font-semibold text-slate-800 transition-colors shadow-xs group">
                            <div class="flex items-center gap-2">
                                <span class="p-1.5 rounded bg-emerald-100 text-emerald-700 group-hover:bg-emerald-200">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm1 7V3.5L18.5 9H15z"/></svg>
                                </span>
                                <span>Template Soal Esai</span>
                            </div>
                            <span class="text-[11px] text-emerald-600 font-bold group-hover:translate-x-0.5 transition-transform">&darr; .docx</span>
                        </a>
                    </div>
                </div>

                <!-- Step 2: Upload File & Select Type -->
                <form method="POST" action="{{ route('quizzes.import.upload', $quiz->id) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="space-y-4">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-800 text-[11px] font-bold flex items-center justify-center">2</span>
                            Unggah Dokumen Word yang Sudah Diisi
                        </span>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Pilih Jenis Soal pada Dokumen</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="border rounded-xl p-3 flex items-center gap-3 cursor-pointer transition-all" :class="qType === 'multiple_choice' ? 'border-primary ring-2 ring-primary/20 bg-indigo-50/50' : 'border-gray-200 hover:bg-gray-50'">
                                    <input type="radio" name="question_type" value="multiple_choice" x-model="qType" class="sr-only">
                                    <span class="w-4 h-4 rounded-full border flex items-center justify-center" :class="qType === 'multiple_choice' ? 'border-primary bg-primary' : 'border-gray-300'">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white" x-show="qType === 'multiple_choice'"></span>
                                    </span>
                                    <div>
                                        <p class="text-xs font-bold text-gray-900">Pilihan Ganda</p>
                                        <p class="text-[10px] text-gray-500">Opsi A-E, Kunci, Pembahasan</p>
                                    </div>
                                </label>

                                <label class="border rounded-xl p-3 flex items-center gap-3 cursor-pointer transition-all" :class="qType === 'essay' ? 'border-primary ring-2 ring-primary/20 bg-indigo-50/50' : 'border-gray-200 hover:bg-gray-50'">
                                    <input type="radio" name="question_type" value="essay" x-model="qType" class="sr-only">
                                    <span class="w-4 h-4 rounded-full border flex items-center justify-center" :class="qType === 'essay' ? 'border-primary bg-primary' : 'border-gray-300'">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white" x-show="qType === 'essay'"></span>
                                    </span>
                                    <div>
                                        <p class="text-xs font-bold text-gray-900">Soal Esai</p>
                                        <p class="text-[10px] text-gray-500">Pertanyaan, Pedoman, Bobot</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Berkas Dokumen Word (.docx)</label>
                            <input type="file" name="document" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required class="w-full text-xs text-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-colors border border-gray-300 rounded-xl p-1.5">
                            <p class="text-[11px] text-gray-500 mt-1">Pastikan berkas berformat .docx (maksimal 10MB). Anda akan melihat pratinjau terlebih dahulu sebelum soal disimpan.</p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-medium">Batal</button>
                        <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-lg text-xs font-bold shadow-sm inline-flex items-center gap-1.5">
                            <span>Proses & Pratinjau Soal</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>

    </div>
</x-app-layout>
