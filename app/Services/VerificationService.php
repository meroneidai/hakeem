<?php

namespace App\Services;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class VerificationService
{
    public function __construct(private SmsGateway $sms) {}

    public function sendAfterRegister(User $user): void
    {
        if (filled($user->phone) && $user->phone_verified_at === null) {
            $this->sendPhoneCode($user);
        }

        if (filled($user->email) && $user->email_verified_at === null) {
            $this->sendEmailLink($user);
        }
    }

    public function sendPhoneCode(User $user): string
    {
        if (blank($user->phone)) {
            throw ValidationException::withMessages([
                'phone' => __('auth.invalid_phone'),
            ]);
        }

        $key = 'phone-otp-send:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'code' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        RateLimiter::hit($key, 600);

        $code = app()->runningUnitTests() ? '123456' : (string) random_int(100000, 999999);

        Cache::put($this->phoneCacheKey($user), Hash::make($code), now()->addMinutes(10));

        $this->sms->send($user->phone, __('auth.verify_sms', ['code' => $code]));

        return $code;
    }

    public function confirmPhone(User $user, string $code): void
    {
        $hash = Cache::get($this->phoneCacheKey($user));

        if (! is_string($hash) || ! Hash::check($code, $hash)) {
            throw ValidationException::withMessages([
                'code' => __('auth.invalid_code'),
            ]);
        }

        $user->forceFill(['phone_verified_at' => now()])->save();
        Cache::forget($this->phoneCacheKey($user));
    }

    public function sendEmailLink(User $user): void
    {
        if (blank($user->email)) {
            throw ValidationException::withMessages([
                'email' => __('auth.invalid_email'),
            ]);
        }

        Mail::to($user->email)->send(new VerifyEmailMail($user, $this->emailUrl($user)));
    }

    public function confirmEmail(User $user, string $hash): void
    {
        if (blank($user->email) || ! hash_equals(sha1($user->email), $hash)) {
            throw ValidationException::withMessages([
                'email' => __('auth.invalid_email_link'),
            ]);
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
    }

    public function emailUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addHours(24),
            ['id' => $user->id, 'hash' => sha1((string) $user->email)],
        );
    }

    private function phoneCacheKey(User $user): string
    {
        return 'phone-otp:'.$user->id;
    }
}
