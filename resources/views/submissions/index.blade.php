<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('kelas.show', $quiz->course_id) }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    Pengumpulan: {{ $quiz->title }}
                </h2>
                <p class="text-sm text-gray-500">{{ $quiz->course->title ?? '' }}</p>
            </div>
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
                            <div>
                                @if($quiz->deadline && $sub->updated_at > $quiz->deadline)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        Terlambat
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Tepat Waktu
                                    </span>
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
