<?php

namespace App\Services;

use App\Enums\WalletLedgerType;
use App\Models\User;
use App\Models\WalletLedger;
use App\Support\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoyaltyProgram
{
    public function __construct(
        private Settings $settings,
        private NotificationDispatcher $notifications,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function defaultSettings(): array
    {
        return [
            'loyalty.referral_enabled' => true,
            'loyalty.referral_reward_amount' => 100,
            'loyalty.signup_bonus_enabled' => false,
            'loyalty.signup_bonus_amount' => 50,
            'loyalty.signup_bonus_starts_at' => null,
            'loyalty.signup_bonus_ends_at' => null,
            'loyalty.signup_headline_ar' => 'سجّل الآن واحصل على رصيد مجاني في محفظتك',
            'loyalty.signup_headline_en' => 'Sign up now and get free wallet credit',
            'loyalty.signup_body_ar' => 'أنشئ حسابك خلال فترة العرض واحصل على :amount ج.م لاستخدامها في حجوزاتك.',
            'loyalty.signup_body_en' => 'Create your account during the campaign and receive :amount EGP toward your bookings.',
        ];
    }

    public function attachReferrer(User $user, ?string $code): void
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '' || $user->referred_by_user_id) {
            return;
        }

        $referrer = User::query()
            ->where('referral_code', $code)
            ->where('id', '!=', $user->id)
            ->where('is_active', true)
            ->first();

        if (! $referrer) {
            return;
        }

        $user->forceFill(['referred_by_user_id' => $referrer->id])->save();
    }

    public function grantSignupBonus(User $user): void
    {
        $campaign = $this->activeSignupCampaign();
        $amount = $campaign['amount'] ?? 0;

        if ($campaign === null && $user->referred_by_user_id) {
            $amount = (float) $this->settings->get('loyalty.signup_bonus_amount', 50);
        }

        if ($amount <= 0) {
            return;
        }

        $this->creditOnce(
            $user,
            WalletLedgerType::SignupBonus,
            (float) $amount,
            $user,
            __('account.loyalty.signup_credit', ['amount' => $amount]),
        );
    }

    public function notifyReferralJoined(User $user): void
    {
        if (! $user->referred_by_user_id) {
            return;
        }

        $this->notifications->send('referral_joined', [
            'user_id' => $user->referred_by_user_id,
            'body' => __('account.loyalty.friend_joined', ['name' => $user->name]),
            'url' => route('account.edit'),
        ]);
    }

    public function rewardCompletedService(User $patient): void
    {
        if (! $this->settings->bool('loyalty.referral_enabled', true)) {
            return;
        }

        $amount = (float) $this->settings->get('loyalty.referral_reward_amount', 100);

        if ($amount <= 0 || ! $patient->referred_by_user_id) {
            return;
        }

        $referrer = User::query()->find($patient->referred_by_user_id);

        if (! $referrer || ! $referrer->is_active) {
            return;
        }

        $this->creditOnce(
            $referrer,
            WalletLedgerType::ReferralReward,
            $amount,
            $patient,
            __('account.loyalty.referral_credit', [
                'amount' => $amount,
                'name' => $patient->name,
            ]),
        );
    }

    public function adjust(User $user, float $amount, User $actor, ?string $note = null): WalletLedger
    {
        if ($amount == 0.0) {
            throw ValidationException::withMessages([
                'amount' => __('admin.loyalty.amount_required'),
            ]);
        }

        return $this->credit($user, WalletLedgerType::AdminAdjustment, $amount, $user, $note, $actor);
    }

    /**
     * @return array{amount: float, starts_at: ?Carbon, ends_at: ?Carbon, headline: string, body: string}|null
     */
    public function activeSignupCampaign(): ?array
    {
        if (! $this->settings->bool('loyalty.signup_bonus_enabled', false)) {
            return null;
        }

        $amount = (float) $this->settings->get('loyalty.signup_bonus_amount', 0);

        if ($amount <= 0) {
            return null;
        }

        $starts = $this->parseDate($this->settings->get('loyalty.signup_bonus_starts_at'));
        $ends = $this->parseDate($this->settings->get('loyalty.signup_bonus_ends_at'));
        $now = now();

        if ($starts && $now->lt($starts)) {
            return null;
        }

        if ($ends && $now->gt($ends)) {
            return null;
        }

        $locale = app()->getLocale() === 'en' ? 'en' : 'ar';

        return [
            'amount' => $amount,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'headline' => (string) ($this->settings->get('loyalty.signup_headline_'.$locale)
                ?: $this->settings->get('loyalty.signup_headline_ar')
                ?: self::defaultSettings()['loyalty.signup_headline_ar']),
            'body' => str_replace(
                ':amount',
                (string) $amount,
                (string) ($this->settings->get('loyalty.signup_body_'.$locale)
                    ?: $this->settings->get('loyalty.signup_body_ar')
                    ?: self::defaultSettings()['loyalty.signup_body_ar']),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function settingsValues(): array
    {
        $values = [];

        foreach (self::defaultSettings() as $key => $default) {
            $stored = $this->settings->get($key, $default);
            $values[$key] = $stored ?? $default;
        }

        return $values;
    }

    public function referralUrl(User $user): string
    {
        return url('/register?ref='.$user->ensureReferralCode());
    }

    private function creditOnce(
        User $user,
        WalletLedgerType $type,
        float $amount,
        User $source,
        string $note,
    ): ?WalletLedger {
        return DB::transaction(function () use ($user, $type, $amount, $source, $note) {
            $exists = WalletLedger::query()
                ->where('type', $type)
                ->where('source_type', $source->getMorphClass())
                ->where('source_id', $source->id)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                return null;
            }

            return $this->credit($user, $type, $amount, $source, $note);
        });
    }

    private function credit(
        User $user,
        WalletLedgerType $type,
        float $amount,
        User $source,
        ?string $note,
        ?User $actor = null,
    ): WalletLedger {
        return DB::transaction(function () use ($user, $type, $amount, $source, $note, $actor) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ((float) $locked->wallet_balance + $amount < 0) {
                throw ValidationException::withMessages([
                    'amount' => __('admin.loyalty.insufficient'),
                ]);
            }

            $locked->increment('wallet_balance', $amount);

            return WalletLedger::query()->create([
                'user_id' => $locked->id,
                'type' => $type,
                'amount' => $amount,
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->id,
                'note' => $note,
                'created_by_user_id' => $actor?->id,
            ]);
        });
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function uniqueReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::query()->where('referral_code', $code)->exists());

        return $code;
    }
}
