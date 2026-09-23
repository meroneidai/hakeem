<?php

namespace App\Enums;

enum PaymentMode: string
{
    case Online = 'online';
    case AtClinic = 'at_clinic';
    case AfterService = 'after_service';

    public function label(): string
    {
        return __('booking.payment_mode.'.$this->value);
    }

    public function hint(): string
    {
        return __('booking.payment_hint.'.$this->value);
    }
}
