<?php

namespace App\Enums;

/**
 * Moderation state of a clinic. Clinics are usable while pending so onboarding is
 * never blocked on staff review, but only verified clinics surface in discovery.
 */
enum VerificationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function labelAr(): string
    {
        return match ($this) {
            self::Pending => 'قيد المراجعة',
            self::Verified => 'موثّقة',
            self::Rejected => 'مرفوضة',
            self::Suspended => 'موقوفة',
        };
    }

    public function label(): string
    {
        return app()->getLocale() === 'en' ? $this->labelEn() : $this->labelAr();
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Pending => 'Pending review',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
            self::Suspended => 'Suspended',
        };
    }

    /**
     * Badge tone understood by the <x-badge> component.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Verified => 'success',
            self::Rejected, self::Suspended => 'danger',
        };
    }

    /**
     * Whether the clinic may appear in patient-facing search and booking.
     */
    public function isPubliclyListable(): bool
    {
        return $this === self::Verified;
    }
}
