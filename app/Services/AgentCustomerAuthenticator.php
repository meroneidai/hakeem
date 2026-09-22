<?php

namespace App\Services;

use App\Models\User;
use App\Support\AccountIdentifier;
use App\Support\Branding;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AgentCustomerAuthenticator
{
    public function __construct(
        private Branding $branding,
        private LoyaltyProgram $loyalty,
    ) {}

    public function optional(Request $request): ?User
    {
        $user = Auth::guard('sanctum')->user();

        if ($user instanceof User && $user->is_active) {
            return $user;
        }

        if ($this->hasCredentials($request)) {
            return $this->attempt($request);
        }

        return null;
    }

    public function require(Request $request): User
    {
        $user = $this->optional($request);

        if ($user) {
            return $user;
        }

        throw new HttpResponseException(response()->json([
            'message' => __('agent.api.credentials_required'),
            'code' => 'customer_credentials_required',
            'ask_for' => ['identifier', 'password'],
        ], 401));
    }

    public function attempt(Request $request): User
    {
        $identifier = AccountIdentifier::fromRequest(
            $request->input('identifier'),
            $request->input('phone'),
            $request->input('email'),
        );

        $throttleKey = $identifier->throttleKey('agent-customer', $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'identifier' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey)]),
            ]);
        }

        $user = $identifier->findUser();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages(['identifier' => __('auth.failed')]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['identifier' => __('auth.inactive')]);
        }

        RateLimiter::clear($throttleKey);

        return $user;
    }

    public function issueToken(User $user, string $deviceName = 'hermes'): string
    {
        $user->forceFill(['last_login_at' => now()])->save();

        return $user->createToken($deviceName)->plainTextToken;
    }

    public function usedPassword(Request $request): bool
    {
        return $this->hasCredentials($request) && Auth::guard('sanctum')->user() === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function profile(User $user): array
    {
        $relations = ['roles'];

        if (method_exists($user, 'city')) {
            $relations[] = 'city';
        }

        if (method_exists($user, 'insuranceProvider')) {
            $relations[] = 'insuranceProvider';
        }

        $user->loadMissing($relations);

        $insurance = method_exists($user, 'insuranceProvider') ? $user->insuranceProvider : null;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'preferred_language' => $user->preferred_language,
            'phone_verified' => method_exists($user, 'isPhoneVerified')
                ? $user->isPhoneVerified()
                : filled($user->phone_verified_at),
            'email_verified' => method_exists($user, 'isEmailVerified')
                ? $user->isEmailVerified()
                : filled($user->email_verified_at),
            'profile_complete' => $this->branding->profileComplete($user),
            'wallet_balance' => (string) ($user->wallet_balance ?? '0.00'),
            'referral_url' => $this->loyalty->referralUrl($user),
            'app_installed' => method_exists($user, 'hasInstalledApp') && $user->hasInstalledApp(),
            'city_id' => $user->city_id ?? null,
            'insurance_provider_id' => $user->insurance_provider_id ?? null,
            'insurance_provider' => $insurance === null ? null : [
                'id' => $insurance->id,
                'name' => $insurance->name,
            ],
            'roles' => $user->roles->pluck('name'),
            'notify' => [
                'email' => (bool) ($user->notify_email ?? true),
                'sms' => (bool) ($user->notify_sms ?? true),
                'push' => (bool) ($user->notify_push ?? true),
            ],
        ];
    }

    private function hasCredentials(Request $request): bool
    {
        return filled($request->input('password'))
            && (filled($request->input('identifier'))
                || filled($request->input('phone'))
                || filled($request->input('email')));
    }
}
