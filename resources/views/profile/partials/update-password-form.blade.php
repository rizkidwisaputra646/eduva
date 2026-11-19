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

    <form method="post" action="{{ route('password.update') }}" class="mt-5 space-y-4 text-xs" x-data="{
        currentPasswordShow: false,
        newPasswordShow: false,
        confirmPasswordShow: false
    }">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block font-semibold text-gray-700 mb-1">Password Lama Saat Ini</label>
            <div class="relative">
                <input id="update_password_current_password" name="current_password" :type="currentPasswordShow ? 'text' : 'password'" class="w-full text-xs rounded-xl border border-gray-300 focus:border-primary focus:ring-1 focus:ring-primary pr-10 py-2.5" autocomplete="current-password" placeholder="Masukkan password yang sedang digunakan" required />
                <button type="button" @click="currentPasswordShow = !currentPasswordShow" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 p-0.5" tabindex="-1">
                    <svg x-show="!currentPasswordShow" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    <svg x-show="currentPasswordShow" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1 text-xs text-red-600" />
        </div>

        <div>
            <label for="update_password_password" class="block font-semibold text-gray-700 mb-1">Password Baru</label>
            <div class="relative">
                <input id="update_password_password" name="password" :type="newPasswordShow ? 'text' : 'password'" class="w-full text-xs rounded-xl border border-gray-300 focus:border-primary focus:ring-1 focus:ring-primary pr-10 py-2.5" autocomplete="new-password" placeholder="Minimal 8 karakter" required />
                <button type="button" @click="newPasswordShow = !newPasswordShow" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 p-0.5" tabindex="-1">
                    <svg x-show="!newPasswordShow" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    <svg x-show="newPasswordShow" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1 text-xs text-red-600" />
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block font-semibold text-gray-700 mb-1">Konfirmasi Password Baru</label>
            <div class="relative">
                <input id="update_password_password_confirmation" name="password_confirmation" :type="confirmPasswordShow ? 'text' : 'password'" class="w-full text-xs rounded-xl border border-gray-300 focus:border-primary focus:ring-1 focus:ring-primary pr-10 py-2.5" autocomplete="new-password" placeholder="Ketik ulang password baru Anda" required />
                <button type="button" @click="confirmPasswordShow = !confirmPasswordShow" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 p-0.5" tabindex="-1">
                    <svg x-show="!confirmPasswordShow" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    <svg x-show="confirmPasswordShow" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                </button>
            </div>
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
