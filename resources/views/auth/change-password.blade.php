<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <x-breadcrumb :items="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Akun Saya', 'url' => null],
                    ['label' => 'Ganti Password', 'url' => null]
                ]" />
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Ganti Password Akun</h1>
                <p class="text-sm text-gray-500 mt-0.5">Perbarui kata sandi akun Anda secara berkala untuk menjaga kerahasiaan data pembelajaran.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}" class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 transition-colors shadow-2xs">
                    &larr; Kembali ke Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">

        @if($user->must_change_password)
        <!-- Peringatan Password Default / Sementara -->
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 shadow-2xs">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-amber-900">Perhatian: Akun Masih Menggunakan Password Sementara</h2>
                    <p class="text-xs text-amber-700 mt-1 leading-relaxed">
                        Akun Anda didaftarkan oleh administrator dengan password default. Demi keamanan privasi data serta nilai akademik Anda, silakan ubah password Anda dengan kombinasi baru yang hanya Anda ketahui.
                    </p>
                </div>
            </div>
        </div>
        @endif

        @if(session('status') === 'password-updated' || session('success'))
        <!-- Notifikasi Berhasil -->
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 sm:p-5 flex items-center gap-3 shadow-2xs">
            <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-emerald-900">Password Berhasil Diperbarui</h3>
                <p class="text-xs text-emerald-700 mt-0.5">{{ session('success') ?? 'Kata sandi baru Anda telah aktif. Gunakan kata sandi ini saat masuk di sesi berikutnya.' }}</p>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Kolom Kiri: Ringkasan Akun & Keamanan -->
            <div class="md:col-span-1 space-y-4">
                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-2xs">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-3">Informasi Akun Anda</span>
                    
                    <div class="flex items-center gap-3 pb-4 border-b border-gray-100">
                        <div class="w-12 h-12 rounded-xl bg-[#DFEAF9] text-[#2E6C9E] font-bold text-lg flex items-center justify-center shrink-0">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-bold text-sm text-gray-900 truncate">{{ $user->name }}</h3>
                            <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $user->role === 'guru' ? 'bg-blue-50 text-blue-700' : ($user->role === 'siswa' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-700') }}">
                                {{ $user->role === 'guru' ? 'Tenaga Pengajar' : ($user->role === 'siswa' ? 'Siswa' : 'Administrator') }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2.5 text-xs">
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <span class="text-gray-500">{{ $user->role === 'guru' ? 'NIP' : ($user->role === 'siswa' ? 'NIM' : 'Username') }}</span>
                            <span class="font-mono font-semibold text-gray-800">{{ $user->identity_number ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <span class="text-gray-500">Email</span>
                            <span class="font-medium text-gray-800 truncate max-w-[140px]" title="{{ $user->email }}">{{ $user->email }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-500">Status Password</span>
                            @if($user->must_change_password)
                                <span class="font-semibold text-amber-700">Default (Perlu Ganti)</span>
                            @else
                                <span class="font-semibold text-emerald-700">Kustom Aktif</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Tips Kata Sandi Aman -->
                <div class="bg-gray-50 border border-gray-200/80 rounded-2xl p-5 text-xs text-gray-600 space-y-2.5">
                    <h4 class="font-bold text-gray-800 text-xs flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Ketentuan Kata Sandi</span>
                    </h4>
                    <ul class="space-y-1.5 text-[11px] list-disc list-inside text-gray-500">
                        <li>Panjang minimal <strong>8 karakter</strong>.</li>
                        <li>Gunakan variasi huruf besar, kecil, angka, atau simbol.</li>
                        <li>Jangan gunakan tanggal lahir atau NIM/NIP Anda.</li>
                        <li>Jaga kerahasiaan password dan jangan bagikan kepada orang lain.</li>
                    </ul>
                </div>
            </div>

            <!-- Kolom Kanan: Form Ganti Password -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-2xl border border-gray-200 p-6 sm:p-7 shadow-2xs">
                    <form method="POST" action="{{ route('password.update') }}" class="space-y-5" x-data="{
                        currentPasswordShow: false,
                        newPasswordShow: false,
                        confirmPasswordShow: false,
                        newPassword: '',
                        confirmPassword: '',
                        get isLongEnough() { return this.newPassword.length >= 8; },
                        get isMatched() { return this.newPassword.length > 0 && this.newPassword === this.confirmPassword; }
                    }">
                        @csrf
                        @method('put')

                        <!-- 1. Password Lama -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="current_password" class="block text-xs font-bold text-gray-700">
                                    Password Lama Saat Ini <span class="text-rose-600">*</span>
                                </label>
                                @if($user->must_change_password)
                                <span class="text-[11px] text-gray-400">Default: <code class="font-mono text-amber-700 bg-amber-50 px-1 py-0.5 rounded">eduva123@</code></span>
                                @endif
                            </div>
                            <div class="relative">
                                <input
                                    id="current_password"
                                    name="current_password"
                                    :type="currentPasswordShow ? 'text' : 'password'"
                                    class="w-full text-xs rounded-xl border border-gray-300 focus:border-primary focus:ring-1 focus:ring-primary pr-10 py-2.5 transition-colors"
                                    placeholder="Masukkan kata sandi lama Anda"
                                    autocomplete="current-password"
                                    required
                                />
                                <button
                                    type="button"
                                    @click="currentPasswordShow = !currentPasswordShow"
                                    class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 p-0.5"
                                    title="Tampilkan / Sembunyikan"
                                    tabindex="-1"
                                >
                                    <svg x-show="!currentPasswordShow" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    <svg x-show="currentPasswordShow" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1 text-xs text-rose-600" />
                        </div>

                        <!-- 2. Password Baru -->
                        <div>
                            <label for="password" class="block text-xs font-bold text-gray-700 mb-1">
                                Password Baru <span class="text-rose-600">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    id="password"
                                    name="password"
                                    x-model="newPassword"
                                    :type="newPasswordShow ? 'text' : 'password'"
                                    class="w-full text-xs rounded-xl border border-gray-300 focus:border-primary focus:ring-1 focus:ring-primary pr-10 py-2.5 transition-colors"
                                    placeholder="Masukkan minimal 8 karakter kata sandi baru"
                                    autocomplete="new-password"
                                    required
                                />
                                <button
                                    type="button"
                                    @click="newPasswordShow = !newPasswordShow"
                                    class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 p-0.5"
                                    title="Tampilkan / Sembunyikan"
                                    tabindex="-1"
                                >
                                    <svg x-show="!newPasswordShow" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    <svg x-show="newPasswordShow" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1 text-xs text-rose-600" />
                        </div>

                        <!-- 3. Konfirmasi Password Baru -->
                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold text-gray-700 mb-1">
                                Konfirmasi Password Baru <span class="text-rose-600">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    x-model="confirmPassword"
                                    :type="confirmPasswordShow ? 'text' : 'password'"
                                    class="w-full text-xs rounded-xl border border-gray-300 focus:border-primary focus:ring-1 focus:ring-primary pr-10 py-2.5 transition-colors"
                                    placeholder="Ketik ulang password baru Anda"
                                    autocomplete="new-password"
                                    required
                                />
                                <button
                                    type="button"
                                    @click="confirmPasswordShow = !confirmPasswordShow"
                                    class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 p-0.5"
                                    title="Tampilkan / Sembunyikan"
                                    tabindex="-1"
                                >
                                    <svg x-show="!confirmPasswordShow" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    <svg x-show="confirmPasswordShow" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1 text-xs text-rose-600" />
                        </div>

                        <!-- Indikator Validasi Real-time Sederhana -->
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[11px]">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full" :class="isLongEnough ? 'bg-emerald-500' : 'bg-gray-300'"></span>
                                <span :class="isLongEnough ? 'text-emerald-700 font-semibold' : 'text-gray-500'">Minimal 8 karakter</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full" :class="isMatched ? 'bg-emerald-500' : 'bg-gray-300'"></span>
                                <span :class="isMatched ? 'text-emerald-700 font-semibold' : 'text-gray-500'">Konfirmasi password cocok</span>
                            </div>
                        </div>

                        <!-- Tombol Submit -->
                        <div class="pt-2 flex items-center justify-end gap-3 border-t border-gray-100">
                            <a href="{{ route('dashboard') }}" class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-700 font-semibold text-xs hover:bg-gray-50 transition-colors">
                                Batal
                            </a>
                            <button
                                type="submit"
                                class="px-5 py-2.5 rounded-xl bg-primary hover:bg-[#357ebd] text-white font-semibold text-xs shadow-sm transition-all cursor-pointer flex items-center gap-1.5"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span>Simpan Password Baru</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
