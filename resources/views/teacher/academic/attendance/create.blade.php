<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Absensi Siswa', 'url' => route('teacher.academic.attendance.index')],
                    ['label' => 'Buka Sesi Presensi', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Buka Sesi Presensi Baru</h1>
                <p class="text-sm text-gray-500 mt-0.5">Tentukan mata pelajaran, pertemuan, dan durasi aktif presensi siswa.</p>
            </div>
            <a href="{{ route('teacher.academic.attendance.index') }}" class="px-3.5 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-2xs">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <form method="POST" action="{{ route('teacher.academic.attendance.store') }}" class="bg-white rounded-2xl border border-gray-200 shadow-2xs p-6 space-y-5 text-xs">
            @csrf

            <!-- Mata Pelajaran -->
            <div>
                <label class="block font-semibold text-gray-700 mb-1.5">Mata Pelajaran <span class="text-rose-500">*</span></label>
                <select name="course_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs">
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ (isset($selectedCourse) && $selectedCourse->id === $c->id) ? 'selected' : '' }}>
                            {{ $c->title }} ({{ $c->students->count() }} Siswa)
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Pertemuan / Topik -->
            <div>
                <label class="block font-semibold text-gray-700 mb-1.5">Pertemuan / Topik Pembelajaran <span class="text-rose-500">*</span></label>
                <input type="text" name="topic" required placeholder="Contoh: Pertemuan 01 — Pengenalan Pemrograman" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs">
            </div>

            <!-- Judul Sesi -->
            <div>
                <label class="block font-semibold text-gray-700 mb-1.5">Judul Sesi Presensi <span class="text-rose-500">*</span></label>
                <input type="text" name="title" required placeholder="Contoh: Presensi Kehadiran Pertemuan 1" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs">
            </div>

            <!-- Waktu Mulai & Waktu Selesai (2 Kolom) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1.5">Waktu Mulai Presensi <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" name="start_time" value="{{ now()->format('Y-m-d\TH:i') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs">
                </div>
                <div>
                    <label class="block font-semibold text-gray-700 mb-1.5">Waktu Selesai Presensi <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" name="end_time" value="{{ now()->addHours(2)->format('Y-m-d\TH:i') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs">
                </div>
            </div>

            <!-- Deskripsi / Catatan Tambahan -->
            <div>
                <label class="block font-semibold text-gray-700 mb-1.5">Petunjuk / Catatan untuk Siswa (Opsional)</label>
                <textarea name="description" rows="3" placeholder="Tuliskan instruksi presensi mandiri atau informasi sesi pembelajaran..." class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs"></textarea>
            </div>

            <!-- Opsi Inisialisasi Presensi Seluruh Siswa -->
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-200/80 space-y-2">
                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" name="auto_populate" value="1" checked class="rounded border-gray-300 text-primary focus:ring-primary mt-0.5">
                    <div>
                        <span class="font-semibold text-gray-800">Inisialisasi daftar kehadiran otomatis</span>
                        <p class="text-gray-500 text-[11px] mt-0.5">Sistem akan otomatis mencatat seluruh siswa terdaftar pada kelas ini dengan status awal.</p>
                    </div>
                </label>

                <div class="pl-6 pt-1 flex items-center gap-4">
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="initial_status" value="hadir" checked class="text-primary focus:ring-primary">
                        <span class="text-gray-700">Set Awal: Hadir (Lalu tandai yang izin/sakit/alpa)</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="initial_status" value="alpa" class="text-primary focus:ring-primary">
                        <span class="text-gray-700">Set Awal: Alpa (Mandiri diisi siswa)</span>
                    </label>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-2">
                <a href="{{ route('teacher.academic.attendance.index') }}" class="px-4 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 bg-primary text-white font-semibold rounded-xl hover:bg-[#357ebd] transition-colors shadow-sm">
                    Buat Sesi Presensi
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
