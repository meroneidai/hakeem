<?php

namespace App\Enums;

enum CollectionMode: string
{
    case Clinic = 'clinic';
    case Home = 'home';

    public function label(): string
    {
        return __('labs.collection.'.$this->value);
    }
}
