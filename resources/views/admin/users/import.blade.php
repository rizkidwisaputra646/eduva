<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                    ['label' => 'Manajemen Pengguna', 'url' => route('admin.users.index')],
                    ['label' => 'Import Pengguna', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Import Pengguna Massal (Excel & CSV)</h1>
                <p class="text-sm text-gray-500 mt-0.5">Daftarkan akun Siswa dan Guru dalam jumlah banyak sekaligus via berkas Microsoft Excel (.xlsx) atau CSV dengan validasi otomatis.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Pengguna
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        @if(!isset($importSession) || empty($importSession))
            <!-- Bagian 1: Panduan & Unduh Template + Form Upload -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- Panduan & Download Template (5 Kolom) -->
                <div class="lg:col-span-5 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-semibold text-primary uppercase tracking-wider mb-1">
                            Langkah 1
                        </div>
                        <h2 class="text-lg font-bold text-gray-900">Unduh Format Template</h2>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                            Gunakan template Excel (.xlsx) resmi agar susunan kolom sesuai standar sistem. Anda dapat langsung mengedit dan mengisinya dengan Microsoft Excel, LibreOffice Calc, atau Google Sheets.
                        </p>

                        <!-- Ketentuan Kolom -->
                        <div class="mt-5 space-y-2.5 text-xs text-gray-600">
                            <div class="flex items-start gap-2.5 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                <span class="font-mono font-bold text-primary shrink-0">nama</span>
                                <div>Nama lengkap pengguna. Wajib diisi.</div>
                            </div>
                            <div class="flex items-start gap-2.5 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                <span class="font-mono font-bold text-primary shrink-0">nomor_identitas</span>
                                <div>NIM untuk siswa atau NIP untuk guru. Harus unik (tidak boleh duplikat).</div>
                            </div>
                            <div class="flex items-start gap-2.5 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                <span class="font-mono font-bold text-primary shrink-0">peran</span>
                                <div>Isi dengan <code class="font-semibold text-gray-900">siswa</code> atau <code class="font-semibold text-gray-900">guru</code>.</div>
                            </div>
                            <div class="flex items-start gap-2.5 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                <span class="font-mono font-bold text-primary shrink-0">email</span>
                                <div>Alamat email pengguna (opsional).</div>
                            </div>
                        </div>

                        <!-- Info Password Otomatis -->
                        <div class="mt-4 p-3 bg-blue-50/70 border border-blue-100 rounded-xl text-xs text-blue-800 leading-relaxed flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Seluruh akun baru otomatis memiliki password default <strong class="font-mono">eduva123@</strong> dan wajib diganti setelah login pertama kali.</span>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100 space-y-2">
                        <!-- Tombol Utama: Excel (.xlsx) -->
                        <a href="{{ route('admin.users.import.template', ['format' => 'xlsx']) }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-colors shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Unduh Template Excel (.xlsx)
                        </a>

                        <!-- Opsi Alternatif: CSV -->
                        <a href="{{ route('admin.users.import.template', ['format' => 'csv']) }}" class="w-full inline-flex items-center justify-center px-4 py-2 text-xs font-medium text-gray-600 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-xl transition-colors">
                            Atau unduh format CSV (.csv)
                        </a>
                    </div>
                </div>

                <!-- Form Upload Berkas (7 Kolom) -->
                <div class="lg:col-span-7 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-semibold text-primary uppercase tracking-wider mb-1">
                            Langkah 2
                        </div>
                        <h2 class="text-lg font-bold text-gray-900">Unggah Berkas Excel atau CSV</h2>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                            Pilih berkas Excel (.xlsx) atau CSV yang telah Anda isi. Sistem akan membaca, memvalidasi nomor identitas duplikat, dan menampilkan pratinjau sebelum disimpan ke database.
                        </p>

                        <form action="{{ route('admin.users.import.preview') }}" method="POST" enctype="multipart/form-data" class="mt-6" id="upload-form">
                            @csrf
                            
                            <div class="border-2 border-dashed border-gray-200 hover:border-emerald-500/50 transition-colors rounded-2xl p-8 text-center bg-gray-50/50 cursor-pointer" onclick="document.getElementById('file-input').click()">
                                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <div class="text-sm font-semibold text-gray-900" id="file-label">
                                    Klik untuk memilih berkas Excel (.xlsx) atau CSV (.csv)
                                </div>
                                <p class="text-xs text-gray-400 mt-1">Mendukung format .xlsx dan .csv (Maksimal 10MB)</p>
                                <input type="file" id="file-input" name="file" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required class="hidden" onchange="document.getElementById('file-label').innerText = this.files[0] ? this.files[0].name : 'Klik untuk memilih berkas';">
                            </div>

                            @error('file')
                                <p class="text-xs text-rose-600 mt-2 font-medium">{{ $message }}</p>
                            @enderror

                            <div class="mt-6">
                                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-white bg-primary hover:bg-primary/90 rounded-xl transition-colors shadow-sm">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                    Validasi & Pratinjau Data
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100 text-xs text-gray-400 flex items-center justify-between">
                        <span>Pemeriksaan otomatis mendeteksi duplikasi NIM/NIP.</span>
                        <span>Engine Excel Terintegrasi</span>
                    </div>
                </div>

            </div>
        @else
            <!-- Bagian 2: Hasil Pratinjau & Validasi Data -->
            <div class="space-y-6">

                <!-- Header Statistik Pratinjau -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 text-xs font-semibold text-primary uppercase tracking-wider mb-1">
                                <span>Hasil Validasi</span>
                                <span>•</span>
                                <span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded font-mono">{{ $importSession['file_type'] ?? 'EXCEL' }}</span>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900">Pratinjau Data Berkas: {{ $importSession['filename'] }}</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Tinjau baris data yang siap disimpan dan periksa baris yang ditolak karena kesalahan atau duplikasi.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <form action="{{ route('admin.users.import.cancel') }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                                    Batalkan
                                </button>
                            </form>

                            @if($importSession['valid_count'] > 0)
                                <form action="{{ route('admin.users.import.confirm') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-primary hover:bg-primary/90 rounded-xl transition-colors shadow-sm inline-flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        Simpan {{ $importSession['valid_count'] }} Akun Valid
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <!-- Cards Ringkasan -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5 pt-4 border-t border-gray-100 text-sm">
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-between">
                            <span class="text-xs text-gray-500 font-medium">Total Baris Terbaca:</span>
                            <span class="font-bold text-gray-900">{{ $importSession['total_rows'] }}</span>
                        </div>
                        <div class="p-3 bg-emerald-50/70 rounded-xl border border-emerald-100 flex items-center justify-between">
                            <span class="text-xs text-emerald-700 font-medium">Valid (Siap Disimpan):</span>
                            <span class="font-bold text-emerald-800">{{ $importSession['valid_count'] }}</span>
                        </div>
                        <div class="p-3 bg-rose-50/70 rounded-xl border border-rose-100 flex items-center justify-between">
                            <span class="text-xs text-rose-700 font-medium">Dilewati (Error / Duplikat):</span>
                            <span class="font-bold text-rose-800">{{ $importSession['invalid_count'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- Tabel Data Gagal / Duplikat (Bila Ada) -->
                @if(!empty($importSession['invalid_rows']))
                    <div class="bg-white rounded-2xl border border-rose-100 shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 bg-rose-50/50 border-b border-rose-100 flex items-center justify-between">
                            <div class="flex items-center gap-2 text-xs font-bold text-rose-800 uppercase tracking-wider">
                                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span>Data Gagal / Duplikat ({{ count($importSession['invalid_rows']) }} Baris - Akan Dilewati)</span>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-500 font-semibold uppercase tracking-wider">
                                        <th class="py-2.5 px-4 w-12">Baris</th>
                                        <th class="py-2.5 px-4">Nama Lengkap</th>
                                        <th class="py-2.5 px-4">Nomor Identitas (NIM/NIP)</th>
                                        <th class="py-2.5 px-4">Peran</th>
                                        <th class="py-2.5 px-4">Alasan Penolakan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($importSession['invalid_rows'] as $invalid)
                                        <tr class="hover:bg-rose-50/20">
                                            <td class="py-2.5 px-4 font-mono text-gray-400">{{ $invalid['row'] }}</td>
                                            <td class="py-2.5 px-4 font-semibold text-gray-900">{{ $invalid['name'] ?: '-' }}</td>
                                            <td class="py-2.5 px-4 font-mono font-medium text-gray-700">{{ $invalid['identity_number'] ?: '-' }}</td>
                                            <td class="py-2.5 px-4 capitalize">{{ $invalid['role'] ?: '-' }}</td>
                                            <td class="py-2.5 px-4 text-rose-700 font-medium">
                                                <ul class="list-disc list-inside space-y-0.5">
                                                    @foreach($invalid['errors'] as $err)
                                                        <li>{{ $err }}</li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Tabel Data Valid (Siap Disimpan) -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 bg-gray-50/75 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-xs font-bold text-emerald-800 uppercase tracking-wider">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Data Valid Siap Diimpor ({{ count($importSession['valid_rows']) }} Baris)</span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50/50 border-b border-gray-100 text-gray-500 font-semibold uppercase tracking-wider">
                                    <th class="py-2.5 px-4 w-12">Baris</th>
                                    <th class="py-2.5 px-4">Nama Pengguna</th>
                                    <th class="py-2.5 px-4">Nomor Identitas (NIM/NIP)</th>
                                    <th class="py-2.5 px-4">Peran Akun</th>
                                    <th class="py-2.5 px-4">Email</th>
                                    <th class="py-2.5 px-4 text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($importSession['valid_rows'] as $valid)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="py-2.5 px-4 font-mono text-gray-400">{{ $valid['row'] }}</td>
                                        <td class="py-2.5 px-4 font-semibold text-gray-900">{{ $valid['name'] }}</td>
                                        <td class="py-2.5 px-4 font-mono font-medium text-gray-700">{{ $valid['identity_number'] }}</td>
                                        <td class="py-2.5 px-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium {{ $valid['role'] === 'guru' ? 'bg-indigo-50 text-indigo-700' : 'bg-blue-50 text-blue-700' }}">
                                                {{ ucfirst($valid['role']) }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-4 text-gray-500">{{ $valid['email'] ?? '-' }}</td>
                                        <td class="py-2.5 px-4 text-right">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Siap Diimpor
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-gray-400">
                                            Tidak ada data valid yang dapat disimpan dari berkas ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        @endif

    </div>
</x-app-layout>
