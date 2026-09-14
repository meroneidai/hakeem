<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function labelAr(): string
    {
        return match ($this) {
            self::Active => 'نشط',
            self::PastDue => 'متأخر السداد',
            self::Cancelled => 'ملغي',
            self::Expired => 'منتهي',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::PastDue => 'Past due',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
        };
    }

    public function label(): string
    {
        return app()->getLocale() === 'en' ? $this->labelEn() : $this->labelAr();
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::PastDue => 'warning',
            self::Cancelled, self::Expired => 'danger',
        };
    }

    /**
     * Whether this status still entitles the clinic to its plan's features.
     */
    public function grantsAccess(): bool
    {
        return in_array($this, [self::Active, self::PastDue], true);
    }
}
