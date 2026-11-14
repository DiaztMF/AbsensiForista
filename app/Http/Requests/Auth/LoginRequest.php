<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            'username' => Str::lower(trim($this->string('username')->toString())),
            'password' => $this->string('password')->toString(),
        ];

        $user = User::where('username', $credentials['username'])->first();

        if ($user && ! $user->is_active) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => 'Akun Anda telah dinonaktifkan. Silakan hubungi Admin FORISTA.',
            ]);
        }

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => 'Username atau kata sandi tidak sesuai.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        // F1: Throttling default 5 percobaan per menit per username dan IP
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw new ThrottleRequestsException(
            "Terlalu banyak percobaan masuk. Silakan coba lagi dalam {$seconds} detik.",
            headers: ['Retry-After' => $seconds],
        );
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')->toString()).'|'.$this->ip());
    }
}
