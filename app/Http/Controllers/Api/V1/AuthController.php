<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LoyaltyProgram;
use App\Services\PatientRegistrar;
use App\Services\VerificationService;
use App\Support\AccountIdentifier;
use App\Support\Branding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'string', 'max:190'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $identifier = AccountIdentifier::fromRequest(
            $request->input('identifier'),
            $request->input('phone'),
            $request->input('email'),
        );

        $throttleKey = $identifier->throttleKey('api-login', $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'identifier' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey)]),
            ]);
        }

        $user = $identifier->findUser();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages(['identifier' => __('auth.failed')]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['identifier' => __('auth.inactive')]);
        }

        RateLimiter::clear($throttleKey);
        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'token' => $user->createToken($request->input('device_name') ?? 'mobile')->plainTextToken,
            'user' => $this->userPayload($user->load('city', 'roles')),
        ]);
    }

    public function register(Request $request, PatientRegistrar $registrar): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'identifier' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'string', 'max:190'],
            'password' => ['required', Password::min(8)],
            'ref' => ['nullable', 'string', 'max:16'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $identifier = AccountIdentifier::fromRequest(
            $validated['identifier'] ?? null,
            $validated['phone'] ?? null,
            $validated['email'] ?? null,
        );

        $user = $registrar->register(
            $validated['name'],
            $identifier,
            $validated['password'],
            $validated['ref'] ?? null,
        );

        return response()->json([
            'token' => $user->createToken($validated['device_name'] ?? 'mobile')->plainTextToken,
            'user' => $this->userPayload($user->load('city', 'roles')),
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json($this->userPayload($request->user()->load('city', 'roles')));
    }

    public function update(Request $request, VerificationService $verification): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:32'],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'notify_email' => ['boolean'],
            'notify_sms' => ['boolean'],
            'notify_push' => ['boolean'],
            'preferred_language' => ['required', Rule::in(array_keys(config('hakeem.locales')))],
        ]);

        $email = filled($data['email'] ?? null) ? strtolower((string) $data['email']) : $user->email;
        $phone = $user->phone;

        if (array_key_exists('phone', $data) && filled($data['phone'])) {
            $parsed = AccountIdentifier::from($data['phone']);

            if ($parsed->channel !== 'phone') {
                throw ValidationException::withMessages([
                    'phone' => __('auth.invalid_phone'),
                ]);
            }

            $parsed->assertAvailable($user->id);
            $phone = $parsed->phone;
        }

        $emailChanged = $email !== $user->email;
        $phoneChanged = $phone !== $user->phone;

        $user->forceFill([
            ...collect($data)->except(['email', 'phone', 'notify_email', 'notify_sms', 'notify_push'])->all(),
            'email' => $email,
            'phone' => $phone,
            'notify_email' => $request->boolean('notify_email', $user->notify_email),
            'notify_sms' => $request->boolean('notify_sms', $user->notify_sms),
            'notify_push' => $request->boolean('notify_push', $user->notify_push),
            'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
            'phone_verified_at' => $phoneChanged ? null : $user->phone_verified_at,
        ])->save();

        if ($emailChanged && filled($email)) {
            $verification->sendEmailLink($user->fresh());
        }

        if ($phoneChanged && filled($phone)) {
            $verification->sendPhoneCode($user->fresh());
        }

        return response()->json($this->userPayload($user->fresh()->load('city', 'roles')));
    }

    public function sendPhoneCode(Request $request, VerificationService $verification): JsonResponse
    {
        $verification->sendPhoneCode($request->user());

        return response()->json(['ok' => true, 'status' => __('auth.verify_sent_phone')]);
    }

    public function verifyPhone(Request $request, VerificationService $verification): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:8'],
        ]);

        $verification->confirmPhone($request->user(), $validated['code']);

        return response()->json($this->userPayload($request->user()->fresh()->load('city', 'roles')));
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'preferred_language' => $user->preferred_language,
            'phone_verified' => $user->isPhoneVerified(),
            'email_verified' => $user->isEmailVerified(),
            'profile_complete' => app(Branding::class)->profileComplete($user),
            'wallet_balance' => (string) $user->wallet_balance,
            'referral_url' => app(LoyaltyProgram::class)->referralUrl($user),
            'app_installed' => $user->hasInstalledApp(),
            'city_id' => $user->city_id,
            'roles' => $user->roles->pluck('name'),
            'notify' => [
                'email' => (bool) $user->notify_email,
                'sms' => (bool) $user->notify_sms,
                'push' => (bool) $user->notify_push,
            ],
        ];
    }
}
