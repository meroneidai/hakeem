<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Models\Concerns\HasRoles;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['phone', 'name', 'email', 'password', 'preferred_language', 'is_active'])]
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
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
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
}
