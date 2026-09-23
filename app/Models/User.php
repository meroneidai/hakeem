<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Models\Concerns\HasRoles;
use App\Services\LoyaltyProgram;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'phone', 'country_code', 'name', 'email', 'password', 'preferred_language',
    'date_of_birth', 'gender', 'city_id', 'insurance_provider_id', 'is_active', 'auth_provider',
    'provider_id', 'firebase_uid', 'notify_email', 'notify_sms', 'notify_push',
    'app_installed_at', 'last_app_seen_at', 'phone_verified_at', 'email_verified_at',
    'referred_by_user_id',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'date_of_birth' => 'date',
            'app_installed_at' => 'datetime',
            'last_app_seen_at' => 'datetime',
            'is_active' => 'boolean',
            'notify_email' => 'boolean',
            'notify_sms' => 'boolean',
            'notify_push' => 'boolean',
            'password' => 'hashed',
            'wallet_balance' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (blank($user->referral_code)) {
                $user->referral_code = LoyaltyProgram::uniqueReferralCode();
            }
        });
    }

    public function ensureReferralCode(): string
    {
        if (filled($this->referral_code)) {
            return $this->referral_code;
        }

        $this->forceFill(['referral_code' => LoyaltyProgram::uniqueReferralCode()])->save();

        return $this->referral_code;
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class, 'opened_by_user_id');
    }

    public function assignedTickets()
    {
        return $this->hasMany(SupportTicket::class, 'assigned_agent_id');
    }

    /**
     * Clinics this user owns outright.
     */
    public function ownedClinics(): HasMany
    {
        return $this->hasMany(Clinic::class, 'owner_user_id');
    }

    /**
     * The doctor profile attached to this login, if any.
     */
    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'patient_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(PatientAddress::class);
    }

    public function careDocuments(): HasMany
    {
        return $this->hasMany(CareDocument::class, 'patient_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function inAppNotifications(): HasMany
    {
        return $this->hasMany(InAppNotification::class);
    }

    public function walletLedgers(): HasMany
    {
        return $this->hasMany(WalletLedger::class);
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StaffAttendance::class);
    }

    public function openAttendance(): ?StaffAttendance
    {
        return $this->attendances()->open()->latest('clocked_in_at')->first();
    }

    public function hasInstalledApp(): bool
    {
        return $this->app_installed_at !== null;
    }

    public function isPhoneVerified(): bool
    {
        return filled($this->phone) && $this->phone_verified_at !== null;
    }

    public function isEmailVerified(): bool
    {
        return filled($this->email) && $this->email_verified_at !== null;
    }

    /** @var Collection<int, Clinic>|null */
    private ?Collection $accessibleClinicsCache = null;

    /**
     * Every clinic this user can act within — owned, or granted through a
     * clinic-scoped role such as doctor or reception.
     *
     * @return Collection<int, Clinic>
     */
    public function accessibleClinics(): Collection
    {
        if ($this->accessibleClinicsCache !== null) {
            return $this->accessibleClinicsCache;
        }

        $this->loadMissing('roles');

        $scopedIds = $this->roles
            ->pluck('pivot.clinic_id')
            ->filter()
            ->unique();

        return $this->accessibleClinicsCache = Clinic::query()
            ->where(function ($query) use ($scopedIds) {
                $query->where('owner_user_id', $this->id)
                    ->orWhereIn('id', $scopedIds);
            })
            ->orderBy('name_ar')
            ->get();
    }

    public function isClinicStaff(): bool
    {
        return $this->hasAnyRole(RoleName::ClinicOwner, RoleName::Doctor, RoleName::Reception)
            || $this->ownedClinics()->exists();
    }

    /**
     * The clinic this user lands in when opening the clinic dashboard.
     */
    public function defaultClinic(): ?Clinic
    {
        return $this->accessibleClinics()->first();
    }

    public function belongsToClinic(Clinic|int $clinic): bool
    {
        $id = $clinic instanceof Clinic ? $clinic->id : $clinic;

        return $this->accessibleClinics()->contains('id', $id);
    }

    /**
     * Normalises Egyptian numbers to a single stored shape: 201XXXXXXXXX.
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            $digits = '20'.substr($digits, 1);
        }

        if (str_starts_with($digits, '1') && strlen($digits) === 10) {
            $digits = '20'.$digits;
        }

        return $digits;
    }

    public function dialUrl(): ?string
    {
        if (! filled($this->phone)) {
            return null;
        }

        return 'tel:+'.ltrim(static::normalizePhone((string) $this->phone), '+');
    }

    public function whatsappChatUrl(): ?string
    {
        if (! filled($this->phone)) {
            return null;
        }

        return 'https://wa.me/'.static::normalizePhone((string) $this->phone);
    }
}
