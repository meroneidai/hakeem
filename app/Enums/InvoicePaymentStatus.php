<?php

namespace App\Enums;

enum InvoicePaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

    public function labelAr(): string
    {
        return match ($this) {
            self::Unpaid => 'غير مدفوعة',
            self::Pending => 'معلّقة',
            self::Paid => 'مدفوعة',
            self::Overdue => 'متأخرة',
            self::Refunded => 'مستردة',
            self::Cancelled => 'ملغاة',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
            self::Refunded => 'Refunded',
            self::Cancelled => 'Cancelled',
        };
    }

    public function label(): string
    {
        return app()->getLocale() === 'en' ? $this->labelEn() : $this->labelAr();
    }

    public function tone(): string
    {
        return match ($this) {
            self::Paid, self::Refunded => 'success',
            self::Pending, self::Unpaid => 'warning',
            self::Overdue, self::Cancelled => 'danger',
        };
    }
}
