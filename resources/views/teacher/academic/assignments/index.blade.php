<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Rekapitulasi Akademik', 'url' => null],
                    ['label' => 'Rekap Tugas', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Rekapitulasi Tugas Pembelajaran</h1>
                <p class="text-sm text-gray-500 mt-0.5">Pantau status pengumpulan tugas siswa, ketepatan waktu, dan penilaian.</p>
            </div>
            @if($selectedAssignment)
            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.academic.assignments.export', ['assignment_id' => $selectedAssignment->id, 'format' => 'xlsx']) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition-colors shadow-2xs">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Unduh Excel</span>
                </a>
                <a href="{{ route('teacher.academic.assignments.export', ['assignment_id' => $selectedAssignment->id, 'format' => 'print']) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 transition-colors shadow-2xs">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span>Cetak / PDF</span>
                </a>
            </div>
            @endif
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filter Mata Pelajaran & Pilihan Tugas -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs p-4">
            <form action="{{ route('teacher.academic.assignments.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Mata Pelajaran</label>
                    <select name="course_id" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs font-semibold rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ (isset($selectedCourse) && $selectedCourse->id === $c->id) ? 'selected' : '' }}>
                                {{ $c->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Pilih Tugas</label>
                    <select name="assignment_id" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs font-semibold rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        @forelse($assignments as $a)
                            <option value="{{ $a->id }}" {{ (isset($selectedAssignment) && $selectedAssignment->id === $a->id) ? 'selected' : '' }}>
                                {{ $a->title }}
                            </option>
                        @empty
                            <option value="">-- Belum ada tugas pada kelas ini --</option>
                        @endforelse
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">Status Pengumpulan</label>
                    <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary">
                        <option value="">Semua Status</option>
                        <option value="belum" {{ request('status') === 'belum' ? 'selected' : '' }}>Belum Mengumpulkan</option>
                        <option value="sudah" {{ request('status') === 'sudah' ? 'selected' : '' }}>Sudah Mengumpulkan</option>
                        <option value="terlambat" {{ request('status') === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                        <option value="dinilai" {{ request('status') === 'dinilai' ? 'selected' : '' }}>Sudah Dinilai</option>
                    </select>
                </div>
            </form>
        </div>

        @if($selectedAssignment)
        <!-- Info Tugas Terpilih -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-primary">Detail Penugasan</span>
                <h2 class="text-lg font-bold text-gray-900 mt-0.5">{{ $selectedAssignment->title }}</h2>
                <p class="text-xs text-gray-500 mt-1">
                    Batas Waktu: <strong>{{ $selectedAssignment->deadline ? $selectedAssignment->deadline->format('d M Y, H:i') . ' WIB' : 'Tidak Ada Batas Waktu' }}</strong>
                </p>
            </div>
            <div class="flex items-center gap-2 text-xs font-semibold">
                <span class="px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 border border-blue-100">
                    {{ $submissionRows->where('status', '!=', 'belum')->count() }} / {{ $submissionRows->count() }} Mengumpulkan
                </span>
            </div>
        </div>

        <!-- Tabel Pengumpulan Tugas Siswa -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-200 text-gray-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4 w-12">No</th>
                            <th class="py-3 px-4">Nama Siswa</th>
                            <th class="py-3 px-4">NIM</th>
                            <th class="py-3 px-4">Status Pengumpulan</th>
                            <th class="py-3 px-4">Waktu Pengumpulan</th>
                            <th class="py-3 px-4 text-center">Nilai</th>
                            <th class="py-3 px-4">Feedback / Catatan</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($submissionRows as $idx => $row)
                            @php
                                $student = $row['student'];
                                $sub = $row['submission'];
                                $st = $row['status'];
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3.5 px-4 text-gray-400 font-mono">{{ $idx + 1 }}</td>
                                <td class="py-3.5 px-4 font-semibold text-gray-900">{{ $student->name }}</td>
                                <td class="py-3.5 px-4 font-mono font-medium text-gray-700">{{ $student->identity_number }}</td>
                                <td class="py-3.5 px-4">
                                    @if($st === 'dinilai')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                            Sudah Dinilai
                                        </span>
                                    @elseif($st === 'terlambat')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-100">
                                            Terlambat
                                        </span>
                                    @elseif($st === 'sudah')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                            Sudah Mengumpulkan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-600">
                                            Belum Mengumpulkan
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-gray-600">
                                    {{ $sub ? $sub->created_at->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-sm text-gray-900">
                                    {{ ($sub && !is_null($sub->score)) ? $sub->score : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-gray-500 max-w-xs truncate">
                                    {{ $sub?->feedback ?: '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5" x-data>
                                        <button type="button" @click="$dispatch('open-modal', 'grade-modal-{{ $student->id }}')" class="px-2.5 py-1 text-[11px] font-semibold text-primary bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors cursor-pointer">
                                            {{ ($sub && !is_null($sub->score)) ? 'Koreksi Nilai' : 'Beri Nilai' }}
                                        </button>

                                        <!-- Modal Beri Nilai / Koreksi -->
                                        <x-modal name="grade-modal-{{ $student->id }}" maxWidth="sm" focusable>
                                            <form method="POST" action="{{ route('teacher.academic.grades.update-score') }}" class="p-5 text-left text-xs">
                                                @csrf
                                                <input type="hidden" name="quiz_id" value="{{ $selectedAssignment->id }}">
                                                <input type="hidden" name="user_id" value="{{ $student->id }}">

                                                <h3 class="text-sm font-bold text-gray-900 pb-2 border-b border-gray-100 mb-3">
                                                    Penilaian Tugas: {{ $student->name }}
                                                </h3>

                                                @if($sub && $sub->file_path)
                                                <div class="mb-3 p-2.5 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-between">
                                                    <span class="text-gray-600">Berkas Jawaban:</span>
                                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($sub->file_path) }}" target="_blank" class="text-primary font-semibold hover:underline">
                                                        Unduh Berkas
                                                    </a>
                                                </div>
                                                @endif

                                                <div class="space-y-3">
                                                    <div>
                                                        <label class="block font-semibold text-gray-700 mb-1">Nilai (0 – 100) <span class="text-rose-500">*</span></label>
                                                        <input type="number" step="0.1" min="0" max="100" name="score" value="{{ $sub?->score }}" required class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs">
                                                    </div>

                                                    <div>
                                                        <label class="block font-semibold text-gray-700 mb-1">Feedback / Catatan Pengajar</label>
                                                        <textarea name="feedback" rows="3" placeholder="Tuliskan evaluasi, saran, atau masukan untuk siswa..." class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs">{{ $sub?->feedback }}</textarea>
                                                    </div>
                                                </div>

                                                <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
                                                    <button type="button" @click="$dispatch('close-modal', 'grade-modal-{{ $student->id }}')" class="px-3 py-1.5 bg-gray-100 text-gray-700 font-semibold rounded-lg">
                                                        Batal
                                                    </button>
                                                    <button type="submit" class="px-3 py-1.5 bg-primary text-white font-semibold rounded-lg hover:bg-[#357ebd]">
                                                        Simpan Nilai
                                                    </button>
                                                </div>
                                            </form>
                                        </x-modal>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-gray-400">
                                    Tidak ada data siswa ditemukan untuk kriteria filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center text-gray-400">
            Pilih mata pelajaran yang memiliki aktivitas tugas untuk melihat rekapitulasi pengumpulan.
        </div>
        @endif
    </div>
</x-app-layout>
