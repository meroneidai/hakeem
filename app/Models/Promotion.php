<?php

namespace App\Models;

use App\Enums\OfferApprovalStatus;
use App\Enums\OfferCategory;
use App\Models\Concerns\HasTranslatedAttributes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'clinic_id', 'title_ar', 'title_en', 'slug', 'category', 'description_ar', 'description_en',
    'includes_ar', 'includes_en', 'conditions_ar', 'conditions_en',
    'discount_type', 'discount_value', 'discount_details', 'banner_image_path',
    'original_price', 'offer_price', 'session_count', 'specialty_id', 'service_type_id', 'starts_at', 'ends_at',
    'is_featured', 'is_active', 'approval_status', 'reviewed_by_user_id', 'reviewed_at', 'rejection_reason',
    'views_count', 'created_by_user_id',
])]
class Promotion extends Model
{
    use HasTranslatedAttributes;

    protected function casts(): array
    {
        return [
            'category' => OfferCategory::class,
            'approval_status' => OfferApprovalStatus::class,
            'discount_value' => 'decimal:2',
            'original_price' => 'decimal:2',
            'offer_price' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function getIncludesAttribute(): ?string
    {
        return $this->translated('includes');
    }

    public function getConditionsAttribute(): ?string
    {
        return $this->translated('conditions');
    }

    public function savings(): float
    {
        if ($this->original_price === null || $this->offer_price === null) {
            return 0;
        }

        return max(0, (float) $this->original_price - (float) $this->offer_price);
    }

    public function discountPercent(): int
    {
        if (! $this->original_price || (float) $this->original_price <= 0) {
            return (int) ($this->discount_type === 'percentage' ? $this->discount_value : 0);
        }

        return (int) round(($this->savings() / (float) $this->original_price) * 100);
    }

    public function isPlatformWide(): bool
    {
        return $this->clinic_id === null;
    }

    public function isApproved(): bool
    {
        return ($this->approval_status ?? OfferApprovalStatus::Approved)->isLive();
    }

    public function isRunning(): bool
    {
        return $this->isApproved()
            && $this->is_active
            && $this->starts_at->isPast()
            && $this->ends_at->isFuture();
    }

    public function status(): string
    {
        $approval = $this->approval_status ?? OfferApprovalStatus::Approved;

        if ($approval === OfferApprovalStatus::Pending) {
            return 'pending_approval';
        }

        if ($approval === OfferApprovalStatus::Rejected) {
            return 'rejected';
        }

        if (! $this->is_active) {
            return 'inactive';
        }

        if ($this->starts_at->isFuture()) {
            return 'scheduled';
        }

        return $this->ends_at->isPast() ? 'expired' : 'running';
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('approval_status', OfferApprovalStatus::Approved)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('approval_status', OfferApprovalStatus::Pending);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
