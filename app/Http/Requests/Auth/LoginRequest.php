<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'identity_number' => ['required_without:email', 'nullable', 'string'],
            'email' => ['required_without:identity_number', 'nullable', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $identifier = (string) ($this->input('identity_number') ?: $this->input('email'));
        $password = (string) $this->input('password');

        // Cek login via identity_number (NIM/NIP/Admin)
        $authenticated = Auth::attempt([
            'identity_number' => $identifier,
            'password' => $password,
        ], $this->boolean('remember'));

        // Fallback login via email untuk fleksibilitas
        if (! $authenticated) {
            $authenticated = Auth::attempt([
                'email' => $identifier,
                'password' => $password,
            ], $this->boolean('remember'));
        }

        if (! $authenticated) {
            \App\Models\ActivityLog::record('auth.failed', "Percobaan login gagal untuk identitas: {$identifier}");
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'identity_number' => 'NIM/NIP atau password yang Anda masukkan tidak sesuai.',
            ]);
        }

        $user = Auth::user();

        // Pemeriksaan status akun aktif/nonaktif
        if ($user && isset($user->is_active) && !$user->is_active) {
            Auth::logout();
            $this->session()->invalidate();
            $this->session()->regenerateToken();

            \App\Models\ActivityLog::record('auth.blocked', "Upaya login ditolak karena akun dinonaktifkan: {$user->name} ({$user->identity_number})", $user);

            throw ValidationException::withMessages([
                'identity_number' => 'Akun Anda sedang dinonaktifkan oleh Administrator. Silakan hubungi admin sistem.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        \App\Models\ActivityLog::record('auth.login', "Pengguna {$user->name} ({$user->identity_number}) berhasil masuk ke sistem.", $user);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'identity_number' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        $id = $this->input('identity_number') ?: $this->input('email');
        return Str::transliterate(Str::lower($id).'|'.$this->ip());
    }
}
