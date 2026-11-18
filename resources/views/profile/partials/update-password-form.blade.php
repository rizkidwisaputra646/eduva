<section>
    <header class="pb-3 border-b border-gray-100">
        <h2 class="text-base font-bold text-gray-900">
            {{ __('Ubah Password Akun') }}
        </h2>

        <p class="mt-1 text-xs text-gray-500 leading-relaxed">
            Perbarui password akun Anda secara berkala untuk menjaga keamanan data pembelajaran.
        </p>

        @if(Auth::user()->must_change_password)
        <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-start gap-2">
            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div>
                <strong>Perhatian Keamanan:</strong> Akun Anda saat ini masih menggunakan password default dari administrator. Sangat disarankan untuk segera menggantinya dengan password baru.
            </div>
        </div>
        @endif
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-5 space-y-4 text-xs">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block font-semibold text-gray-700 mb-1">Password Lama Saat Ini</label>
            <input id="update_password_current_password" name="current_password" type="password" class="w-full text-xs rounded-xl border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20" autocomplete="current-password" placeholder="Masukkan password yang sedang digunakan" required />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1 text-xs text-red-600" />
        </div>

        <div>
            <label for="update_password_password" class="block font-semibold text-gray-700 mb-1">Password Baru</label>
            <input id="update_password_password" name="password" type="password" class="w-full text-xs rounded-xl border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20" autocomplete="new-password" placeholder="Minimal 8 karakter" required />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1 text-xs text-red-600" />
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block font-semibold text-gray-700 mb-1">Konfirmasi Password Baru</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="w-full text-xs rounded-xl border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20" autocomplete="new-password" placeholder="Ketik ulang password baru Anda" required />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1 text-xs text-red-600" />
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-xl font-semibold text-xs shadow-2xs transition-colors cursor-pointer">
                Simpan Password Baru
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 3000)"
                    class="text-xs text-emerald-600 font-semibold"
                >Password berhasil diperbarui!</p>
            @endif
        </div>
    </form>
</section>
