<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\User;
use App\Support\AccountIdentifier;
use Illuminate\Support\Facades\Auth;

class PatientRegistrar
{
    public function __construct(
        private LoyaltyProgram $loyalty,
        private VerificationService $verification,
    ) {}

    public function register(string $name, AccountIdentifier $identifier, string $password, ?string $referralCode = null): User
    {
        $identifier->assertAvailable();

        $user = User::create([
            'name' => $name,
            'phone' => $identifier->phone,
            'email' => $identifier->email,
            'password' => $password,
            'preferred_language' => app()->getLocale(),
        ]);

        $user->assignRole(RoleName::Patient);

        $this->loyalty->attachReferrer(
            $user,
            $referralCode,
        );
        $this->loyalty->grantSignupBonus($user->fresh());
        $this->loyalty->notifyReferralJoined($user->fresh());
        $this->verification->sendAfterRegister($user->fresh());

        return $user->fresh();
    }

    public function registerAndLogin(string $name, AccountIdentifier $identifier, string $password, ?string $referralCode = null): User
    {
        $user = $this->register($name, $identifier, $password, $referralCode);

        Auth::login($user, true);

        return $user;
    }
}
