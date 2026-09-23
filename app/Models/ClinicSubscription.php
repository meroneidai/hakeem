<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\InvoicePaymentMethod;
use App\Enums\InvoicePaymentStatus;
use App\Enums\SubscriptionStatus;
use Database\Factories\ClinicSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'clinic_id', 'subscription_plan_id', 'discount_code_id', 'billing_cycle',
    'status', 'amount', 'current_period_start', 'current_period_end', 'cancelled_at',
    'invoice_number', 'payment_status', 'payment_method', 'payment_reference',
    'paid_at', 'due_at', 'notes', 'invoice_sent_at', 'reminder_sent_at',
])]
class ClinicSubscription extends Model
{
    /** @use HasFactory<ClinicSubscriptionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'billing_cycle' => BillingCycle::class,
            'status' => SubscriptionStatus::class,
            'payment_status' => InvoicePaymentStatus::class,
            'payment_method' => InvoicePaymentMethod::class,
            'amount' => 'decimal:2',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancelled_at' => 'datetime',
            'paid_at' => 'datetime',
            'due_at' => 'datetime',
            'invoice_sent_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ClinicSubscription $subscription): void {
            if (blank($subscription->invoice_number)) {
                $subscription->invoice_number = static::nextInvoiceNumber();
            }

            if ($subscription->payment_status === null) {
                $subscription->payment_status = (float) $subscription->amount <= 0
                    ? InvoicePaymentStatus::Paid
                    : InvoicePaymentStatus::Pending;
            }

            if ($subscription->payment_status === InvoicePaymentStatus::Paid && $subscription->paid_at === null) {
                $subscription->paid_at = now();
            }

            $subscription->due_at ??= $subscription->current_period_end;
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function isFree(): bool
    {
        return (float) $this->amount === 0.0;
    }

    /**
     * Free plans have no renewal date, so they never read as expiring.
     */
    public function isExpiring(int $withinDays = 7): bool
    {
        if ($this->isFree() || $this->current_period_end === null) {
            return false;
        }

        return $this->current_period_end->isBefore(now()->addDays($withinDays));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Active->value);
    }

    public function localInput(?Carbon $value): ?string
    {
        return $value?->copy()->timezone(config('hakeem.display_timezone'))->format('Y-m-d\TH:i');
    }

    public static function nextInvoiceNumber(): string
    {
        $year = now()->year;
        $prefix = 'INV-'.$year.'-';
        $last = static::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');
        $sequence = $last ? ((int) substr((string) $last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
