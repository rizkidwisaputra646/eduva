<x-app-layout>
    <x-slot name="header">
        <div>
            <x-breadcrumb :items="['Kelas & Mata Pelajaran' => route('kelas.index'), ($quiz->course->title ?? 'Kelas') => route('kelas.show', $quiz->course_id), ('Pengumpulan ' . $quiz->title) => '']" />
            <div class="flex items-center gap-3">
                <a href="{{ route('kelas.show', $quiz->course_id) }}" class="text-gray-400 hover:text-gray-600 transition-colors" title="Kembali ke Kelas">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div class="flex items-center gap-2">
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                        Pengumpulan: {{ $quiz->title }}
                    </h2>
                    @if($quiz->type === 'quiz')
                        @if($quiz->detect_tab_switch)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Deteksi Pindah Tab: Aktif
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                            Deteksi Pindah Tab: Nonaktif
                        </span>
                        @endif
                    @endif
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-1">{{ $quiz->course->title ?? '' }}</p>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto space-y-6">
        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-800 p-4 rounded-xl border border-emerald-200 flex items-center gap-3 shadow-sm">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif

        @php
            $totalSubmissions = $submissions->count();
            $gradedCount = $submissions->whereNotNull('score')->count();
            $ungradedCount = $totalSubmissions - $gradedCount;
            $avgScore = $gradedCount > 0 ? round($submissions->whereNotNull('score')->avg('score'), 1) : '-';
        @endphp

        <!-- Ringkasan Metrik Pengumpulan -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Terkumpul</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalSubmissions }} <span class="text-xs font-normal text-gray-500">siswa</span></p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Sudah Dinilai</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $gradedCount }} <span class="text-xs font-normal text-gray-500">siswa</span></p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Perlu Dinilai</p>
                <p class="text-2xl font-bold text-amber-600 mt-1">{{ $ungradedCount }} <span class="text-xs font-normal text-gray-500">siswa</span></p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">Rata-Rata Nilai</p>
                <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $avgScore }} <span class="text-xs font-normal text-gray-500">{{ $gradedCount > 0 ? '/ 100' : '' }}</span></p>
            </div>
        </div>

        <!-- Daftar Kartu Pengumpulan & Penilaian -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900">Pengumpulan Siswa</h3>
                <span class="text-xs text-gray-500">Tenggat: {{ $quiz->deadline ? $quiz->deadline->format('d M Y, H:i') : 'Tanpa Batas Waktu' }}</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($submissions as $sub)
                @php
                    $studentLogs = isset($examLogs) ? $examLogs->get($sub->user_id, collect()) : collect();
                @endphp
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow p-5 flex flex-col justify-between">
                    <!-- Header Siswa & Status Waktu -->
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-sm border border-slate-200">
                                    {{ strtoupper(substr($sub->user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900 text-sm line-clamp-1">{{ $sub->user->name }}</h4>
                                    <p class="text-xs text-gray-500 line-clamp-1">{{ $sub->user->email }}</p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1.5">
                                @if($quiz->deadline && $sub->updated_at > $quiz->deadline)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        Terlambat
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Tepat Waktu
                                    </span>
                                @endif

                                @if($quiz->type === 'quiz' && $quiz->detect_tab_switch)
                                    @if($studentLogs->count() > 0)
                                    <button x-data @click="$dispatch('open-modal', 'logs-student-{{ $sub->user_id }}')" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300 hover:bg-amber-200 transition-colors shadow-2xs cursor-pointer" title="Klik untuk melihat catatan waktu pelanggaran">
                                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        {{ $studentLogs->count() }}x Pindah Tab
                                    </button>
                                    @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        0x Pindah Tab
                                    </span>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <!-- Info Berkas & Waktu Pengumpulan -->
                        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-600">
                            <span class="flex items-center gap-1.5 text-gray-500">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                {{ $sub->updated_at->format('d M, H:i') }}
                            </span>
                            @if($sub->file_path)
                            <a href="{{ Storage::url($sub->file_path) }}" target="_blank" class="inline-flex items-center gap-1 text-primary hover:text-primary-dark font-medium hover:underline bg-indigo-50/60 px-2 py-1 rounded">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Unduh Berkas
                            </a>
                            @else
                            <span class="text-gray-400 italic">Pilihan Ganda / Teks</span>
                            @endif
                        </div>

                        @if($quiz->type === 'quiz' || !empty($sub->answers))
                        <!-- Tombol Buka Lembar Jawaban Siswa -->
                        <div class="mt-3">
                            <button type="button" x-data @click="$dispatch('open-modal', 'answers-student-{{ $sub->id }}')" class="w-full flex items-center justify-center gap-2 py-2 px-3 text-xs font-semibold text-primary bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200 transition-colors shadow-2xs">
                                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span>Lihat Lembar Jawaban Siswa</span>
                            </button>
                        </div>
                        @endif

                        <!-- Nilai & Feedback Box -->
                        <div class="mt-4 bg-gray-50 rounded-lg p-3 border border-gray-100 flex items-center justify-between">
                            <div>
                                <span class="text-xs text-gray-500 block">Nilai Siswa</span>
                                @if($sub->score !== null)
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-2xl font-extrabold text-gray-900">{{ $sub->score }}</span>
                                        <span class="text-xs text-gray-400 font-semibold">/ 100</span>
                                    </div>
                                @else
                                    <span class="inline-block mt-0.5 text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                        Belum Dinilai
                                    </span>
                                @endif
                            </div>
                            
                            <button x-data @click="$dispatch('open-modal', 'grade-submission-{{ $sub->id }}')" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold {{ $sub->score !== null ? 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' : 'bg-primary text-white hover:bg-primary-dark shadow-sm' }} transition-colors">
                                {{ $sub->score !== null ? 'Ubah Nilai' : 'Beri Nilai' }}
                            </button>
                        </div>

                        @if($sub->feedback)
                        <div class="mt-2 text-xs text-gray-600 bg-white p-2.5 rounded-lg border border-gray-100 flex items-start gap-2">
                            <svg class="w-4 h-4 text-indigo-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                            <span class="italic text-gray-700">"{{ $sub->feedback }}"</span>
                        </div>
                        @endif
                    </div>

                    <!-- Modal Beri Nilai Siswa -->
                    <x-modal name="grade-submission-{{ $sub->id }}" focusable>
                        <form method="post" action="{{ route('submissions.grade', $sub->id) }}" class="p-6">
                            @csrf
                            @method('PUT')
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                                <h2 class="text-lg font-bold text-gray-900">Form Penilaian</h2>
                                <span class="text-xs text-gray-500">{{ $quiz->title }}</span>
                            </div>

                            <div class="p-3 bg-gray-50 rounded-xl mb-4 flex items-center justify-between border border-gray-100">
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ $sub->user->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $sub->user->email }}</p>
                                </div>
                                @if($sub->file_path)
                                <a href="{{ Storage::url($sub->file_path) }}" target="_blank" class="px-2.5 py-1 text-xs font-semibold bg-white border border-gray-200 text-primary hover:bg-indigo-50 rounded-lg inline-flex items-center gap-1 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                    Lihat Berkas
                                </a>
                                @endif
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Skor / Nilai (0 - 100)</label>
                                    <div class="relative">
                                        <input type="number" name="score" min="0" max="100" step="1" value="{{ $sub->score ?? 85 }}" required class="w-full pl-4 pr-12 py-2.5 rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 font-extrabold text-xl text-gray-900">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">/ 100</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Catatan / Ulasan Guru (Opsional)</label>
                                    <textarea name="feedback" rows="3" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 text-sm" placeholder="Berikan komentar konstruktif atau catatan evaluasi untuk siswa...">{{ $sub->feedback }}</textarea>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-gray-100">
                                <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm font-medium">Batal</button>
                                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm font-medium shadow-sm">Simpan Nilai</button>
                            </div>
                        </form>
                    </x-modal>

                    @if($quiz->type === 'quiz' && $quiz->detect_tab_switch && $studentLogs->count() > 0)
                    <!-- Modal Riwayat Deteksi Pindah Tab -->
                    <x-modal name="logs-student-{{ $sub->user_id }}" focusable>
                        <div class="p-6">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                                <div>
                                    <h3 class="text-base font-bold text-gray-900">Riwayat Deteksi Pindah Tab</h3>
                                    <p class="text-xs text-gray-500">Siswa: {{ $sub->user->name }} ({{ $sub->user->email }})</p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                    Total: {{ $studentLogs->count() }}x Pindah Tab
                                </span>
                            </div>

                            <div class="p-3.5 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800 mb-4 leading-relaxed">
                                <strong>Catatan untuk Guru:</strong> Sistem mencatat setiap kali siswa berpindah tab peramban atau meninggalkan jendela ujian. Kejadian ini tidak otomatis menggagalkan ujian; tindak lanjut atau kebijakan penilaian ditentukan secara mandiri oleh guru.
                            </div>

                            <div class="overflow-hidden border border-gray-200 rounded-xl max-h-72 overflow-y-auto">
                                <table class="w-full text-left text-xs text-gray-600">
                                    <thead class="bg-gray-50 text-gray-700 font-bold border-b border-gray-200 sticky top-0">
                                        <tr>
                                            <th class="py-2.5 px-3 w-12 text-center">No</th>
                                            <th class="py-2.5 px-3">Waktu Kejadian</th>
                                            <th class="py-2.5 px-3">Keterangan Aktivitas</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach($studentLogs as $lIndex => $log)
                                        <tr class="hover:bg-gray-50/50">
                                            <td class="py-2.5 px-3 text-center font-bold text-gray-500">{{ $lIndex + 1 }}</td>
                                            <td class="py-2.5 px-3 font-mono font-medium text-gray-900 whitespace-nowrap">
                                                {{ $log->created_at->format('d M Y, H:i:s') }} WIB
                                            </td>
                                            <td class="py-2.5 px-3 text-gray-700">
                                                {{ $log->details ?? 'Berpindah tab atau meninggalkan jendela browser' }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </x-modal>
                    @endif

                    <!-- Modal Detail Lembar Jawaban Siswa -->
                    <x-modal name="answers-student-{{ $sub->id }}" maxWidth="3xl" focusable>
                        <div class="flex flex-col max-h-[85vh]">
                            <!-- Header Modal -->
                            <div class="p-5 sm:p-6 border-b border-gray-100 flex items-start justify-between bg-white shrink-0">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-lg font-bold text-gray-900">Lembar Jawaban Siswa</h3>
                                        @if($sub->score !== null)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                Nilai: {{ $sub->score }} / 100
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                Belum Dinilai
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <strong class="text-gray-700">{{ $sub->user->name }}</strong> ({{ $sub->user->email }}) · Diserahkan {{ $sub->updated_at->format('d M Y, H:i') }} WIB
                                    </p>
                                </div>
                                <button type="button" x-on:click="$dispatch('close')" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>

                            <!-- Body Modal (Scrollable) -->
                            <div class="p-5 sm:p-6 overflow-y-auto space-y-6">
                                @php
                                    $studentAnswers = $sub->answers ?? [];
                                    $quizQuestions = $quiz->questions ?? collect();
                                    $mcqQuestions = $quizQuestions->where('type', 'multiple_choice');
                                    $essayQuestions = $quizQuestions->where('type', 'essay');
                                    
                                    $correctCount = 0;
                                    foreach ($mcqQuestions as $mcqQ) {
                                        if (!empty($studentAnswers[$mcqQ->id]['is_correct'])) {
                                            $correctCount++;
                                        }
                                    }
                                @endphp

                                <!-- Ringkasan Singkat -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-gray-50/80 p-3.5 rounded-xl border border-gray-200 text-xs">
                                    <div>
                                        <span class="text-gray-400 block text-[11px]">Total Soal</span>
                                        <span class="font-bold text-gray-800 text-sm">{{ $quizQuestions->count() }} Soal</span>
                                    </div>
                                    @if($mcqQuestions->count() > 0)
                                    <div>
                                        <span class="text-gray-400 block text-[11px]">PG Benar</span>
                                        <span class="font-bold text-emerald-600 text-sm">{{ $correctCount }} / {{ $mcqQuestions->count() }}</span>
                                    </div>
                                    @endif
                                    @if($essayQuestions->count() > 0)
                                    <div>
                                        <span class="text-gray-400 block text-[11px]">Soal Esai</span>
                                        <span class="font-bold text-purple-700 text-sm">{{ $essayQuestions->count() }} Soal</span>
                                    </div>
                                    @endif
                                    <div>
                                        <span class="text-gray-400 block text-[11px]">Pindah Tab</span>
                                        <span class="font-bold {{ $studentLogs->count() > 0 ? 'text-amber-600' : 'text-emerald-600' }} text-sm">{{ $studentLogs->count() }} Kali</span>
                                    </div>
                                </div>

                                @if(empty($studentAnswers) && $quizQuestions->count() > 0)
                                <div class="p-4 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800 leading-relaxed">
                                    <strong>Informasi:</strong> Siswa ini menyelesaikan kuis sebelum sistem pencatatan lembar jawaban per butir soal diterapkan. Anda tetap dapat memberikan nilai dan ulasan melalui formulir di bawah.
                                </div>
                                @endif

                                <!-- Rincian Soal & Jawaban -->
                                <div class="space-y-4">
                                    @forelse($quizQuestions as $qIndex => $q)
                                    @php
                                        $ans = $studentAnswers[$q->id] ?? null;
                                    @endphp
                                    <div class="p-4 sm:p-5 rounded-xl border border-gray-200 bg-white shadow-2xs">
                                        <!-- Header Soal -->
                                        <div class="flex items-center justify-between gap-3 mb-3 pb-2.5 border-b border-gray-100">
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                    Soal #{{ $qIndex + 1 }}
                                                </span>
                                                <span class="text-xs text-gray-500 font-medium">
                                                    {{ $q->type === 'multiple_choice' ? 'Pilihan Ganda' : 'Esai' }} · {{ $q->points }} Poin
                                                </span>
                                            </div>
                                            <div>
                                                @if($q->type === 'multiple_choice')
                                                    @if(isset($ans['is_correct']) && $ans['is_correct'])
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                            Benar (+{{ $q->points }} Poin)
                                                        </span>
                                                    @elseif(isset($ans['selected_option_id']) && $ans['selected_option_id'] !== null)
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                            Salah (0 Poin)
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">
                                                            Tidak Dijawab
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">
                                                        Perlu Dinilai Guru
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Pertanyaan -->
                                        <p class="text-sm font-medium text-gray-900 leading-relaxed whitespace-pre-line mb-3">
                                            {{ $q->question_text }}
                                        </p>

                                        <!-- Pilihan Ganda -->
                                        @if($q->type === 'multiple_choice')
                                        <div class="space-y-2 mt-2">
                                            @foreach($q->options as $optIndex => $opt)
                                            @php
                                                $isSelected = isset($ans['selected_option_id']) && $ans['selected_option_id'] == $opt->id;
                                                $isCorrectOption = (bool)$opt->is_correct;
                                            @endphp
                                            <div class="flex items-center justify-between p-3 rounded-xl border text-xs {{ $isSelected && $isCorrectOption ? 'bg-emerald-50/80 border-emerald-400 text-emerald-900 font-semibold' : ($isSelected && !$isCorrectOption ? 'bg-rose-50/80 border-rose-300 text-rose-900 font-medium' : ($isCorrectOption ? 'bg-emerald-50/40 border-dashed border-emerald-300 text-emerald-800' : 'bg-gray-50/50 border-gray-200 text-gray-700')) }}">
                                                <div class="flex items-center gap-2.5">
                                                    <span class="w-5 h-5 rounded-full flex items-center justify-center font-bold text-[11px] {{ $isSelected ? ($isCorrectOption ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white') : ($isCorrectOption ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600') }}">
                                                        {{ chr(65 + $optIndex) }}
                                                    </span>
                                                    <span>{{ $opt->option_text }}</span>
                                                </div>
                                                <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                                    @if($isSelected && $isCorrectOption)
                                                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 font-bold text-[10px] rounded-md border border-emerald-200">Jawaban Siswa (Kunci Benar)</span>
                                                    @elseif($isSelected && !$isCorrectOption)
                                                        <span class="px-2 py-0.5 bg-rose-100 text-rose-800 font-bold text-[10px] rounded-md border border-rose-200">Jawaban Siswa</span>
                                                    @elseif($isCorrectOption)
                                                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 font-medium text-[10px] rounded-md border border-emerald-200">Kunci Jawaban</span>
                                                    @endif
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                        @else
                                        <!-- Esai -->
                                        <div class="space-y-3 mt-3">
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Jawaban Uraian Siswa:</label>
                                                @if(!empty($ans['text_answer']))
                                                    <div class="p-3.5 bg-gray-50 rounded-xl border border-gray-200 text-xs text-gray-800 leading-relaxed whitespace-pre-line font-sans">
                                                        {{ $ans['text_answer'] }}
                                                    </div>
                                                @else
                                                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 text-xs text-gray-400 italic">
                                                        Siswa tidak menuliskan teks uraian.
                                                    </div>
                                                @endif
                                            </div>

                                            @if(!empty($ans['file_path']))
                                            <div class="flex items-center gap-2">
                                                <a href="{{ Storage::url($ans['file_path']) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-primary bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                    Unduh Lampiran Jawaban Soal Ini
                                                </a>
                                            </div>
                                            @endif

                                            @if($q->explanation)
                                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700">
                                                <strong class="text-slate-900 block mb-0.5">Panduan / Pembahasan Guru:</strong>
                                                <p class="leading-relaxed">{{ $q->explanation }}</p>
                                            </div>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                    @empty
                                    <div class="text-center py-6 text-xs text-gray-400">
                                        Tidak ada butir soal dalam kuis ini.
                                    </div>
                                    @endforelse
                                </div>

                                <!-- Form Penilaian Cepat Langsung di Lembar Jawaban -->
                                <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100">
                                    <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-3">Penilaian & Catatan Guru</h4>
                                    <form method="post" action="{{ route('submissions.grade', $sub->id) }}" class="space-y-3">
                                        @csrf
                                        @method('PUT')
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div class="sm:col-span-1">
                                                <label class="block text-xs font-semibold text-gray-700 mb-1">Skor / Nilai (0 - 100)</label>
                                                <input type="number" name="score" min="0" max="100" step="1" value="{{ $sub->score ?? 0 }}" required class="w-full text-sm font-bold rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20">
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="block text-xs font-semibold text-gray-700 mb-1">Catatan / Ulasan untuk Siswa</label>
                                                <input type="text" name="feedback" value="{{ $sub->feedback }}" placeholder="Contoh: Kerja bagus, perhatikan nomor 3..." class="w-full text-xs rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20">
                                            </div>
                                        </div>
                                        <div class="flex justify-end pt-1">
                                            <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-dark text-white font-semibold text-xs rounded-lg shadow-2xs transition-colors">
                                                Simpan Nilai dari Lembar Jawaban
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Footer Modal -->
                            <div class="p-4 border-t border-gray-100 flex justify-end bg-gray-50 rounded-b-2xl shrink-0">
                                <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-xs font-semibold rounded-lg shadow-2xs">
                                    Tutup Lembar Jawaban
                                </button>
                            </div>
                        </div>
                    </x-modal>
                </div>
                @empty
                <div class="col-span-full bg-white p-12 text-center rounded-2xl border border-gray-200 text-gray-500">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-800 mb-1">Belum Ada Pengumpulan</h3>
                    <p class="text-sm text-gray-500">Belum ada siswa yang mengumpulkan tugas atau menyelesaikan kuis ini.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
