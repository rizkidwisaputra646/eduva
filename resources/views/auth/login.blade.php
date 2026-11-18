<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-8 text-center">
        <h2 class="text-2xl font-bold text-gray-900">Masuk ke Akun Anda</h2>
        <p class="text-sm text-gray-500 mt-2">Selamat datang kembali! Silakan masukkan detail login Anda.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- NIM / NIP / ID Pengguna -->
        <div>
            <label for="identity_number" class="block text-sm font-semibold text-gray-700 mb-1">NIM / NIP / ID Pengguna</label>
            <input id="identity_number" class="block w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 transition-all text-sm font-medium" type="text" name="identity_number" :value="old('identity_number')" required autofocus autocomplete="username" placeholder="Masukkan NIM (Siswa), NIP (Guru), atau 'admin'" />
            <x-input-error :messages="$errors->get('identity_number')" class="mt-2 text-sm text-red-600" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex justify-between mb-1">
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                @if (Route::has('password.request'))
                    <a class="text-sm font-medium text-primary hover:text-indigo-700" href="{{ route('password.request') }}">
                        Lupa password?
                    </a>
                @endif
            </div>
            <input id="password" class="block w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 transition-all text-sm" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-red-600" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <input id="remember_me" type="checkbox" class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2" name="remember">
            <label for="remember_me" class="ml-2 text-sm text-gray-600">Ingat Saya</label>
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-primary hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all">
                Masuk
            </button>
        </div>
        
        <div class="text-center mt-6">
            <p class="text-xs text-gray-500 bg-gray-50 py-2.5 px-3 rounded-lg border border-gray-100">
                Pendaftaran akun siswa dan guru dikelola langsung oleh <strong>Administrator Sekolah</strong>.
            </p>
        </div>
    </form>
</x-guest-layout>
