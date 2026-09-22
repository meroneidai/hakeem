<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lab_order_id', 'item_type', 'lab_test_id', 'lab_package_id', 'qty', 'unit_price', 'line_total',
])]
class LabOrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class, 'lab_order_id');
    }

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }

    public function labPackage(): BelongsTo
    {
        return $this->belongsTo(LabPackage::class);
    }

    public function catalogItem(): LabTest|LabPackage|null
    {
        return $this->item_type === 'package' ? $this->labPackage : $this->labTest;
    }
}
