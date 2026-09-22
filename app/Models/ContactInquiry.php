<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'phone', 'email', 'audience', 'subject', 'message'])]
class ContactInquiry extends Model
{
    /**
     * @return list<string>
     */
    public static function audiences(): array
    {
        return ['patient', 'doctor', 'clinic', 'partner', 'other'];
    }
}
