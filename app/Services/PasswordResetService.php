<?php

namespace App\Services;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use App\Support\AccountIdentifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    public function __construct(private SmsGateway $sms) {}

    public function send(AccountIdentifier $identifier): string
    {
        $user = $identifier->findUser();

        if (! $user || ! $user->is_active) {
            return $identifier->channel;
        }

        if ($identifier->channel === 'email' && filled($user->email)) {
            $token = Str::random(64);
            $this->storeToken($user->email, $token);
            Mail::to($user->email)->send(new ResetPasswordMail($user, $this->resetUrl($user->email, $token)));

            return 'email';
        }

        if (filled($user->phone)) {
            $code = app()->runningUnitTests() ? '123456' : (string) random_int(100000, 999999);
            $this->storeToken($user->phone, $code);
            Cache::put('password-otp:'.$user->id, Hash::make($code), now()->addMinutes(15));
            $this->sms->send($user->phone, __('auth.reset_sms', ['code' => $code]));

            return 'phone';
        }

        if (filled($user->email)) {
            $token = Str::random(64);
            $this->storeToken($user->email, $token);
            Mail::to($user->email)->send(new ResetPasswordMail($user, $this->resetUrl($user->email, $token)));

            return 'email';
        }

        return $identifier->channel;
    }

    public function reset(AccountIdentifier $identifier, string $token, string $password): User
    {
        $user = $identifier->findUser();

        if (! $user || ! $user->is_active) {
            throw ValidationException::withMessages([
                'identifier' => __('auth.failed'),
            ]);
        }

        $key = $identifier->channel === 'phone' ? $user->phone : ($user->email ?: $user->phone);
        $row = DB::table('password_reset_tokens')->where('email', $key)->first();

        if (! $row || ! Hash::check($token, $row->token)) {
            throw ValidationException::withMessages([
                'token' => __('auth.invalid_code'),
            ]);
        }

        if ($row->created_at && Carbon::parse($row->created_at)->lt(now()->subMinutes(30))) {
            throw ValidationException::withMessages([
                'token' => __('auth.invalid_code'),
            ]);
        }

        $user->forceFill(['password' => $password])->save();
        DB::table('password_reset_tokens')->where('email', $key)->delete();
        Cache::forget('password-otp:'.$user->id);

        return $user;
    }

    private function storeToken(string $key, string $plain): void
    {
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $key],
            ['token' => Hash::make($plain), 'created_at' => now()],
        );
    }

    private function resetUrl(string $email, string $token): string
    {
        return url('/password/reset?'.http_build_query([
            'identifier' => $email,
            'token' => $token,
        ]));
    }
}
