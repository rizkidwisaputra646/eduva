<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight flex items-center">
                    Kelola Kuis/Tugas: {{ $quiz->title }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Mata Pelajaran: {{ $quiz->course->title }}</p>
            </div>
            <a href="{{ route('kelas.show', $quiz->course_id) }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                &larr; Kembali ke Kelas
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-800 p-4 rounded-lg border border-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h3 class="font-bold text-lg text-gray-900">Total Soal: {{ $quiz->questions->count() }}</h3>
                <p class="text-sm text-gray-500">Total Poin Maksimal: {{ $quiz->questions->sum('points') }}</p>
            </div>
            <div class="flex gap-2">
                <button x-data @click="$dispatch('open-modal', 'add-mcq')" class="px-4 py-2 bg-indigo-50 text-primary rounded-lg font-medium text-sm hover:bg-indigo-100 transition-colors shadow-sm">
                    + Pilihan Ganda
                </button>
                <button x-data @click="$dispatch('open-modal', 'add-essay')" class="px-4 py-2 bg-primary text-white rounded-lg font-medium text-sm hover:bg-primary-dark transition-colors shadow-sm">
                    + Esai
                </button>
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
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark font-medium">Simpan Soal</button>
                </div>
            </form>
        </x-modal>

    </div>
</x-app-layout>
