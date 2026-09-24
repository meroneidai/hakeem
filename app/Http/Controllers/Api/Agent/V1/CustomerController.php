<?php

namespace App\Http\Controllers\Api\Agent\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\AgentCustomerAuthenticator;
use App\Services\PasswordResetService;
use App\Services\PatientRegistrar;
use App\Services\VerificationService;
use App\Support\AccountIdentifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    public function __construct(private AgentCustomerAuthenticator $customers) {}

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
            'token' => $this->customers->issueToken($user, $validated['device_name'] ?? 'hermes'),
            'user' => $this->customers->profile($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'string', 'max:190'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $user = $this->customers->attempt($request);

        return response()->json([
            'token' => $this->customers->issueToken($user, $request->input('device_name') ?? 'hermes'),
            'user' => $this->customers->profile($user),
        ]);
    }

    public function forgotPassword(Request $request, PasswordResetService $resets): JsonResponse
    {
        $request->validate([
            'identifier' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'string', 'max:190'],
        ]);

        $identifier = AccountIdentifier::fromRequest(
            $request->input('identifier'),
            $request->input('phone'),
            $request->input('email'),
        );

        $user = $identifier->findUser();

        if (! $user || ! $user->is_active) {
            throw ValidationException::withMessages([
                'identifier' => __('auth.identifier_not_found'),
            ]);
        }

        $throttleKey = $identifier->throttleKey('agent-password-forgot', $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'identifier' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey)]),
            ]);
        }

        RateLimiter::hit($throttleKey, 600);
        $channel = $resets->send($identifier);

        return response()->json([
            'ok' => true,
            'channel' => $channel,
            'status' => $channel === 'phone' ? __('auth.reset_sent_phone') : __('auth.reset_sent_email'),
        ]);
    }

    public function resetPassword(Request $request, PasswordResetService $resets): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:190'],
            'token' => ['required', 'string', 'max:255'],
            'password' => ['required', Password::min(8)],
        ]);

        $identifier = AccountIdentifier::from($validated['identifier']);
        $user = $resets->reset($identifier, $validated['token'], $validated['password']);

        return response()->json([
            'token' => $this->customers->issueToken($user, 'hermes'),
            'user' => $this->customers->profile($user),
            'status' => __('auth.password_updated'),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $user = $this->customers->require($request);
        $payload = ['user' => $this->customers->profile($user)];

        if ($this->customers->usedPassword($request)) {
            $payload['token'] = $this->customers->issueToken($user, $request->input('device_name') ?? 'hermes');
        }

        return response()->json($payload);
    }

    public function update(Request $request, VerificationService $verification): JsonResponse
    {
        $user = $this->customers->require($request);
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

        return response()->json([
            'user' => $this->customers->profile($user->fresh()),
        ]);
    }

    public function bookings(Request $request): JsonResponse
    {
        $user = $this->customers->require($request);

        $bookings = Booking::query()
            ->whereBelongsTo($user, 'patient')
            ->with(['clinic', 'doctor.specialty', 'address.city', 'serviceType'])
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'status' => $booking->status->value,
                'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
                'clinic' => $booking->clinic?->name,
                'doctor' => $booking->doctor?->name,
                'service' => $booking->serviceType?->name,
                'home_address' => $booking->patient_home_address,
            ]);

        return response()->json(['data' => $bookings]);
    }
}
