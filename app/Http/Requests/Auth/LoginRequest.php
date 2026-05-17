<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Maximum failed attempts before the account is locked.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * How long the account stays locked after MAX_ATTEMPTS (minutes).
     */
    private const LOCK_MINUTES = 15;

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
            'email' => ['required', 'string', 'email'],
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

        $email = strtolower((string) $this->string('email'));
        $user = User::query()->where('email', $email)->first();

        $this->ensureAccountIsNotLocked($user);

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            $this->trackFailedAttempt($user);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        $this->onSuccessfulLogin();
    }

    /**
     * Ensure the login request is not IP-rate-limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));
        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    /**
     * Refuse to even attempt authentication when the account is currently locked
     * via the `users.locked_until` column (objective A6).
     */
    private function ensureAccountIsNotLocked(?User $user): void
    {
        if (! $user || ! $user->locked_until) {
            return;
        }

        if ($user->locked_until->isFuture()) {
            $minutes = max(1, (int) ceil(now()->diffInSeconds($user->locked_until) / 60));

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', ['seconds' => $user->locked_until->diffInSeconds(), 'minutes' => $minutes]),
            ]);
        }

        // Lock window expired: reset counters silently
        $user->forceFill(['login_attempts' => 0, 'locked_until' => null])->save();
    }

    /**
     * Increment the user's login_attempts and lock the account when it crosses MAX_ATTEMPTS.
     */
    private function trackFailedAttempt(?User $user): void
    {
        if (! $user) {
            return;
        }

        $user->forceFill([
            'login_attempts' => ((int) $user->login_attempts) + 1,
        ])->save();

        if ($user->login_attempts >= self::MAX_ATTEMPTS) {
            $user->forceFill([
                'locked_until' => Carbon::now()->addMinutes(self::LOCK_MINUTES),
            ])->save();
            event(new Lockout($this));
        }
    }

    private function onSuccessfulLogin(): void
    {
        $user = Auth::user();
        if ($user) {
            $user->forceFill([
                'login_attempts' => 0,
                'locked_until' => null,
                'last_login_at' => now(),
                'last_login_ip' => $this->ip(),
            ])->save();
        }
    }
}
