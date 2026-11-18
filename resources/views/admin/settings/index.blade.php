<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                    ['label' => 'Pengaturan Sistem', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Pengaturan & Konfigurasi Sistem</h1>
                <p class="text-sm text-gray-500 mt-0.5">Atur parameter global LMS Eduva, identitas institusi, tahun ajaran, dan kebijakan kredensial.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gray-100 text-xs font-semibold text-gray-700">
                    Konfigurasi Global
                </span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Card 1: Identitas Platform & Institusi -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="pb-4 border-b border-gray-100">
                    <h2 class="text-base font-bold text-gray-900">Identitas Platform & Lembaga</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Informasi penamaan yang ditampilkan pada judul halaman, sertifikat, dan header aplikasi.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5 text-sm">
                    <!-- Nama Aplikasi -->
                    <div>
                        <label for="app_name" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Nama Platform LMS *</label>
                        <input type="text" id="app_name" name="app_name" value="{{ old('app_name', $settings['app_name']) }}" required class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="text-[11px] text-gray-400 mt-1">Nama branding sistem pembelajaran.</p>
                    </div>

                    <!-- Nama Institusi -->
                    <div>
                        <label for="institution_name" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Nama Institusi / Lembaga Pendidikan *</label>
                        <input type="text" id="institution_name" name="institution_name" value="{{ old('institution_name', $settings['institution_name']) }}" required class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="text-[11px] text-gray-400 mt-1">Nama sekolah, madrasah, atau universitas pengelola.</p>
                    </div>
                </div>
            </div>

            <!-- Card 2: Periode Akademik Berjalan -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="pb-4 border-b border-gray-100">
                    <h2 class="text-base font-bold text-gray-900">Periode Akademik Aktif</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Parameter kalender pembelajaran untuk pengelompokan kelas dan pelaporan.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5 text-sm">
                    <!-- Tahun Ajaran -->
                    <div>
                        <label for="academic_year" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Tahun Ajaran Aktif *</label>
                        <input type="text" id="academic_year" name="academic_year" value="{{ old('academic_year', $settings['academic_year']) }}" required placeholder="Contoh: 2026/2027" class="w-full px-3.5 py-2.5 text-sm font-mono bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>

                    <!-- Semester -->
                    <div>
                        <label for="academic_semester" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Semester Akademik *</label>
                        <select id="academic_semester" name="academic_semester" required class="w-full px-3.5 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="Ganjil" {{ old('academic_semester', $settings['academic_semester']) === 'Ganjil' ? 'selected' : '' }}>Semester Ganjil</option>
                            <option value="Genap" {{ old('academic_semester', $settings['academic_semester']) === 'Genap' ? 'selected' : '' }}>Semester Genap</option>
                            <option value="Pendek" {{ old('academic_semester', $settings['academic_semester']) === 'Pendek' ? 'selected' : '' }}>Semester Antara / Pendek</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Card 3: Kebijakan Keamanan & Kredensial -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="pb-4 border-b border-gray-100">
                    <h2 class="text-base font-bold text-gray-900">Kebijakan Kredensial & Hak Akses</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Konfigurasi nilai bawaan pembuatan akun dan izin operasional mandiri siswa.</p>
                </div>

                <div class="space-y-5 mt-5 text-sm">
                    <!-- Password Default -->
                    <div>
                        <label for="default_password" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Password Default Akun Baru & Reset *</label>
                        <input type="text" id="default_password" name="default_password" value="{{ old('default_password', $settings['default_password']) }}" required class="w-full max-w-md px-3.5 py-2.5 text-sm font-mono bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <p class="text-[11px] text-gray-400 mt-1">Diterapkan secara otomatis saat admin menambahkan pengguna baru, melakukan import CSV, atau mereset password.</p>
                    </div>

                    <!-- Izinkan Siswa Keluar Kelas Mandiri -->
                    <div class="pt-3 border-t border-gray-100">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="allow_student_leave_course" value="1" {{ old('allow_student_leave_course', $settings['allow_student_leave_course']) == '1' ? 'checked' : '' }} class="mt-0.5 text-primary focus:ring-primary rounded border-gray-300 h-4 w-4">
                            <div>
                                <span class="font-semibold text-gray-900 block text-xs uppercase tracking-wider">Izinkan Siswa Keluar dari Kelas Secara Mandiri</span>
                                <span class="text-xs text-gray-500 block mt-0.5">Bila diaktifkan, tombol "Keluar dari Kelas" dapat diakses oleh siswa di halaman kelas. Riwayat nilai tugas dan kuis siswa tetap tersimpan untuk arsip guru.</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary/90 rounded-xl transition-colors shadow-sm inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Simpan Seluruh Pengaturan
                </button>
            </div>

        </form>

    </div>
</x-app-layout>

