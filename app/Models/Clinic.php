<?php

namespace App\Models;

use App\Enums\PlanFeature;
use App\Enums\VerificationStatus;
use App\Models\Concerns\HasTranslatedAttributes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'owner_user_id', 'name_ar', 'name_en', 'slug', 'description_ar', 'description_en',
    'logo_path', 'email', 'phone', 'is_single_doctor', 'subscription_plan_id',
    'verification_status', 'rejection_reason', 'verified_at', 'is_active',
])]
class Clinic extends Model
{
    use HasFactory, HasTranslatedAttributes;

    protected function casts(): array
    {
        return [
            'is_single_doctor' => 'boolean',
            'is_active' => 'boolean',
            'verified_at' => 'datetime',
            'verification_status' => VerificationStatus::class,
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class)->withTimestamps();
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(ClinicAddress::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(ClinicService::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ClinicSubscription::class);
    }

    /**
     * The subscription currently governing this clinic's entitlements.
     */
    public function currentSubscription(): HasOne
    {
        return $this->hasOne(ClinicSubscription::class)->latestOfMany();
    }

    public function primaryAddress(): HasOne
    {
        return $this->hasOne(ClinicAddress::class)->where('is_primary', true);
    }

    /**
     * Staff members scoped to this clinic through role_user.clinic_id.
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user', 'clinic_id', 'user_id')
            ->withPivot('role_id')
            ->distinct();
    }

    /**
     * Feature entitlement resolves through the plan, but only while the
     * subscription is in a status that still grants access.
     */
    public function allows(PlanFeature|string $feature): bool
    {
        $subscription = $this->currentSubscription;

        if ($subscription && ! $subscription->status->grantsAccess()) {
            return false;
        }

        return (bool) $this->plan?->allows($feature);
    }

    public function isVerified(): bool
    {
        return $this->verification_status === VerificationStatus::Verified;
    }

    /**
     * Whether another doctor may be added without exceeding the plan's cap.
     */
    public function canAddDoctor(): bool
    {
        $count = $this->doctors()->count();

        if (! $this->allows(PlanFeature::MultipleDoctors) && $count >= 1) {
            return false;
        }

        $cap = $this->plan?->doctor_cap;

        return $cap === null || $count < $cap;
    }

    /**
     * Whether another branch may be added without exceeding the plan's cap.
     */
    public function canAddAddress(): bool
    {
        $count = $this->addresses()->count();

        if (! $this->allows(PlanFeature::MultipleAddresses) && $count >= 1) {
            return false;
        }

        $cap = $this->plan?->address_cap;

        return $cap === null || $count < $cap;
    }

    /**
     * Onboarding steps still outstanding, driving the dashboard checklist.
     *
     * @return list<string>
     */
    public function pendingSetupSteps(): array
    {
        $steps = [];

        if ($this->addresses()->count() === 0) {
            $steps[] = 'address';
        }

        if ($this->doctors()->count() === 0) {
            $steps[] = 'doctor';
        }

        if ($this->services()->where('is_active', true)->count() === 0) {
            $steps[] = 'service';
        }

        if (! $this->hasAnySchedule()) {
            $steps[] = 'schedule';
        }

        return $steps;
    }

    public function hasAnySchedule(): bool
    {
        return AddressSchedule::query()
            ->whereIn('clinic_address_id', $this->addresses()->select('id'))
            ->where('is_closed', false)
            ->exists();
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('verification_status', VerificationStatus::Verified->value);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Clinics that may appear in patient-facing discovery.
     */
    public function scopeListable(Builder $query): Builder
    {
        return $query->verified()->active();
    }
}
