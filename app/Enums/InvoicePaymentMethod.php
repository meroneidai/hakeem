<?php

namespace App\Enums;

enum InvoicePaymentMethod: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Card = 'card';
    case Wallet = 'wallet';
    case Other = 'other';

    public function labelAr(): string
    {
        return match ($this) {
            self::Cash => 'نقداً',
            self::BankTransfer => 'تحويل بنكي',
            self::Card => 'بطاقة',
            self::Wallet => 'محفظة',
            self::Other => 'أخرى',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::BankTransfer => 'Bank transfer',
            self::Card => 'Card',
            self::Wallet => 'Wallet',
            self::Other => 'Other',
        };
    }

    public function label(): string
    {
        return app()->getLocale() === 'en' ? $this->labelEn() : $this->labelAr();
    }
}
