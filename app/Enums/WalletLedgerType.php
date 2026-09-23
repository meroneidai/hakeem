<?php

namespace App\Enums;

enum WalletLedgerType: string
{
    case SignupBonus = 'signup_bonus';
    case ReferralReward = 'referral_reward';
    case AdminAdjustment = 'admin_adjustment';

    public function label(): string
    {
        return __('account.loyalty.types.'.$this->value);
    }
}
