<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Services\LoyaltyProgram;
use App\Support\Audit;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class LoyaltySettingsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManagePaymentSettings->value];
    }

    public function edit(LoyaltyProgram $loyalty): View
    {
        $user = request()->user();

        return view('admin.loyalty.edit', [
            'values' => $loyalty->settingsValues(),
            'campaign' => $loyalty->activeSignupCampaign(),
            'referralUrl' => $user ? $loyalty->referralUrl($user) : null,
            'balance' => $user?->wallet_balance,
        ]);
    }

    public function update(Request $request, Settings $settings): RedirectResponse
    {
        $data = $request->validate([
            'loyalty.referral_enabled' => ['boolean'],
            'loyalty.referral_reward_amount' => ['required', 'numeric', 'min:0', 'max:100000'],
            'loyalty.signup_bonus_enabled' => ['boolean'],
            'loyalty.signup_banner_enabled' => ['boolean'],
            'loyalty.signup_bonus_amount' => ['required', 'numeric', 'min:0', 'max:100000'],
            'loyalty.signup_bonus_starts_at' => ['nullable', 'date'],
            'loyalty.signup_bonus_ends_at' => ['nullable', 'date', 'after_or_equal:loyalty.signup_bonus_starts_at'],
            'loyalty.signup_headline_ar' => ['nullable', 'string', 'max:190'],
            'loyalty.signup_headline_en' => ['nullable', 'string', 'max:190'],
            'loyalty.signup_body_ar' => ['nullable', 'string', 'max:500'],
            'loyalty.signup_body_en' => ['nullable', 'string', 'max:500'],
        ]);

        $loyalty = $data['loyalty'] ?? [];

        $settings->setMany([
            'loyalty.referral_enabled' => $request->boolean('loyalty.referral_enabled'),
            'loyalty.referral_reward_amount' => $loyalty['referral_reward_amount'] ?? 0,
            'loyalty.signup_bonus_enabled' => $request->boolean('loyalty.signup_bonus_enabled'),
            'loyalty.signup_banner_enabled' => $request->boolean('loyalty.signup_banner_enabled'),
            'loyalty.signup_bonus_amount' => $loyalty['signup_bonus_amount'] ?? 0,
            'loyalty.signup_bonus_starts_at' => $loyalty['signup_bonus_starts_at'] ?? null,
            'loyalty.signup_bonus_ends_at' => $loyalty['signup_bonus_ends_at'] ?? null,
            'loyalty.signup_headline_ar' => $loyalty['signup_headline_ar'] ?? null,
            'loyalty.signup_headline_en' => $loyalty['signup_headline_en'] ?? null,
            'loyalty.signup_body_ar' => $loyalty['signup_body_ar'] ?? null,
            'loyalty.signup_body_en' => $loyalty['signup_body_en'] ?? null,
        ], 'loyalty');

        Audit::log('settings.loyalty_updated', changes: $loyalty);

        return back()->with('status', __('admin.loyalty.saved'));
    }
}
