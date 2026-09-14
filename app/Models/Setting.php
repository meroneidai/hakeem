<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

#[Fillable(['key', 'group', 'value', 'is_encrypted'])]
class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'value' => 'json',
            'is_encrypted' => 'boolean',
        ];
    }

    public function decodedValue(): mixed
    {
        if (! $this->is_encrypted || blank($this->value)) {
            return $this->value;
        }

        try {
            return Crypt::decryptString((string) $this->value);
        } catch (\Throwable) {
            return null;
        }
    }
}
