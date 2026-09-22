<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Services\LoyaltyProgram;
use App\Services\VerificationService;
use App\Support\AccountIdentifier;
use App\Support\Branding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request, LoyaltyProgram $loyalty): View
    {
        $user = $request->user()->load('city');

        return view('account.profile', [
            'user' => $user,
            'cities' => City::query()->active()->ordered()->with('governorate')->get(),
            'referralUrl' => $loyalty->referralUrl($user),
            'ledgers' => $user->walletLedgers()->latest('id')->limit(20)->get(),
            'campaign' => $loyalty->activeSignupCampaign(),
            'appointments' => $user->bookings()->with(['clinic', 'doctor', 'serviceType'])->latest('scheduled_at')->limit(5)->get(),
            'complete' => app(Branding::class)->profileComplete($user),
        ]);
    }

    public function update(Request $request, VerificationService $verification): RedirectResponse
    {
        $user = $request->user();
        $wasComplete = app(Branding::class)->profileComplete($user);

        $request->merge([
            'date_of_birth' => $this->dateOfBirthFromRequest($request),
        ]);

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

        $email = filled($data['email'] ?? null) ? strtolower((string) $data['email']) : null;
        $phoneInput = trim((string) ($data['phone'] ?? ''));
        $phone = $user->phone;

        if ($phoneInput !== '') {
            $parsed = AccountIdentifier::from($phoneInput);

            if ($parsed->channel !== 'phone') {
                throw ValidationException::withMessages([
                    'phone' => __('auth.invalid_phone'),
                ]);
            }

            $parsed->assertAvailable($user->id);
            $phone = $parsed->phone;
        } elseif ($request->exists('phone')) {
            $phone = null;
        }

        if ($phone === null && $email === null) {
            throw ValidationException::withMessages([
                'identifier' => __('auth.identifier_required'),
            ]);
        }

        $emailChanged = $email !== $user->email;
        $phoneChanged = $phone !== $user->phone;

        $user->forceFill([
            'name' => $data['name'],
            'email' => $email,
            'phone' => $phone,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'city_id' => filled($data['city_id'] ?? null) ? (int) $data['city_id'] : null,
            'preferred_language' => $data['preferred_language'],
            'notify_email' => $request->boolean('notify_email'),
            'notify_sms' => $request->boolean('notify_sms'),
            'notify_push' => $request->boolean('notify_push'),
            'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
            'phone_verified_at' => $phoneChanged ? null : $user->phone_verified_at,
        ])->save();

        if ($emailChanged && filled($email)) {
            $verification->sendEmailLink($user->fresh());
        }

        if ($phoneChanged && filled($phone)) {
            $verification->sendPhoneCode($user->fresh());
        }

        $user = $user->fresh();
        $complete = app(Branding::class)->profileComplete($user);

        if (! $wasComplete && $complete) {
            return back()->with('status', __('account.complete'))->with('profile_complete', true);
        }

        return back()->with('status', __('account.saved'));
    }

    public function sendPhoneCode(Request $request, VerificationService $verification): RedirectResponse
    {
        $verification->sendPhoneCode($request->user());

        return back()->with('status', __('auth.verify_sent_phone'));
    }

    public function verifyPhone(Request $request, VerificationService $verification): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:8'],
        ]);

        $user = $request->user();
        $wasComplete = app(Branding::class)->profileComplete($user);
        $verification->confirmPhone($user, $validated['code']);

        if (! $wasComplete && app(Branding::class)->profileComplete($user->fresh())) {
            return back()->with('status', __('account.complete'))->with('profile_complete', true);
        }

        return back()->with('status', __('auth.phone_verified'));
    }

    public function sendEmailLink(Request $request, VerificationService $verification): RedirectResponse
    {
        $verification->sendEmailLink($request->user());

        return back()->with('status', __('auth.verify_sent_email'));
    }

    public function verifyEmail(Request $request, int $id, string $hash, VerificationService $verification): RedirectResponse
    {
        abort_unless($request->user()->id === $id, 403);

        $user = $request->user();
        $wasComplete = app(Branding::class)->profileComplete($user);
        $verification->confirmEmail($user, $hash);

        if (! $wasComplete && app(Branding::class)->profileComplete($user->fresh())) {
            return redirect()->route('account.edit')->with('status', __('account.complete'))->with('profile_complete', true);
        }

        return redirect()->route('account.edit')->with('status', __('auth.email_verified'));
    }

    private function dateOfBirthFromRequest(Request $request): ?string
    {
        $year = $request->input('birth_year');
        $month = $request->input('birth_month');
        $day = $request->input('birth_day');

        if (filled($year) || filled($month) || filled($day)) {
            if (! filled($year) || ! filled($month) || ! filled($day)) {
                return 'invalid';
            }

            $year = (int) $year;
            $month = (int) $month;
            $day = (int) $day;

            if (! checkdate($month, $day, $year) || $year < 1900) {
                return 'invalid';
            }

            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        return $this->normalizeDateString($request->input('date_of_birth'));
    }

    private function normalizeDateString(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = strtr(trim($value), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $parts) === 1) {
            $year = (int) $parts[1];
            $month = (int) $parts[2];
            $day = (int) $parts[3];

            return checkdate($month, $day, $year)
                ? sprintf('%04d-%02d-%02d', $year, $month, $day)
                : $value;
        }

        if (preg_match('/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/', $value, $parts) === 1) {
            $day = (int) $parts[1];
            $month = (int) $parts[2];
            $year = (int) $parts[3];

            return checkdate($month, $day, $year)
                ? sprintf('%04d-%02d-%02d', $year, $month, $day)
                : $value;
        }

        return $value;
    }
}
