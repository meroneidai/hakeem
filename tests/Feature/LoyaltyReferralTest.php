<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\User;
use App\Services\BookingManager;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyReferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_with_a_referral_code_attaches_the_referrer(): void
    {
        $this->seedRoles();

        $referrer = User::factory()->create();

        $this->get('/register?ref='.$referrer->referral_code)->assertOk();

        $this->post('/register', [
            'name' => 'ضيف الدعوة',
            'phone' => '01033334444',
            'password' => 'password12',
            'password_confirmation' => 'password12',
        ])->assertRedirect();

        $friend = User::query()->where('phone', '201033334444')->first();

        $this->assertNotNull($friend);
        $this->assertSame($referrer->id, $friend->referred_by_user_id);
        $this->assertSame('50.00', $friend->wallet_balance);
    }

    public function test_completed_booking_credits_the_referrer_once(): void
    {
        $this->seedRoles();

        $referrer = User::factory()->create();
        $patient = User::factory()->create(['referred_by_user_id' => $referrer->id]);
        $provider = $this->seedListableProvider();
        $actor = User::factory()->create();
        $manager = app(BookingManager::class);

        $booking = Booking::factory()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
            'status' => BookingStatus::Pending,
        ]);

        $manager->transition($booking, BookingStatus::Confirmed, $actor);
        $manager->transition($booking->fresh(), BookingStatus::InProgress, $actor);
        $manager->transition($booking->fresh(), BookingStatus::Completed, $actor);

        $this->assertSame('100.00', $referrer->fresh()->wallet_balance);

        $second = Booking::factory()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
            'status' => BookingStatus::Pending,
        ]);

        $manager->transition($second, BookingStatus::Confirmed, $actor);
        $manager->transition($second->fresh(), BookingStatus::InProgress, $actor);
        $manager->transition($second->fresh(), BookingStatus::Completed, $actor);

        $this->assertSame('100.00', $referrer->fresh()->wallet_balance);
        $this->assertSame(1, $referrer->walletLedgers()->count());
    }

    public function test_signup_campaign_credits_new_users_and_shows_on_the_homepage(): void
    {
        $this->seedRoles();
        $this->travelTo('2026-09-15 12:00:00');

        app(Settings::class)->setMany([
            'loyalty.signup_bonus_enabled' => true,
            'loyalty.signup_banner_enabled' => true,
            'loyalty.signup_bonus_amount' => 50,
            'loyalty.signup_bonus_starts_at' => '2026-09-01 00:00:00',
            'loyalty.signup_bonus_ends_at' => '2026-09-20 23:59:00',
            'loyalty.signup_headline_ar' => 'رصيد ترحيبي للتجربة',
        ], 'loyalty');

        $this->get('/')->assertOk()->assertSee('رصيد ترحيبي للتجربة');

        $this->post('/register', [
            'name' => 'مستخدم العرض',
            'phone' => '01055556666',
            'password' => 'password12',
            'password_confirmation' => 'password12',
        ])->assertRedirect();

        $user = User::query()->where('phone', '201055556666')->first();

        $this->assertSame('50.00', $user->wallet_balance);

        $this->travelTo('2026-09-21 00:00:00');

        $this->post('/logout');

        $this->get('/')->assertOk()->assertDontSee('رصيد ترحيبي للتجربة');
    }

    public function test_signup_banner_can_be_hidden_while_credit_still_applies(): void
    {
        $this->seedRoles();
        $this->travelTo('2026-09-15 12:00:00');

        app(Settings::class)->setMany([
            'loyalty.signup_bonus_enabled' => true,
            'loyalty.signup_banner_enabled' => false,
            'loyalty.signup_bonus_amount' => 50,
            'loyalty.signup_bonus_starts_at' => '2026-09-01 00:00:00',
            'loyalty.signup_bonus_ends_at' => '2026-09-20 23:59:00',
            'loyalty.signup_headline_ar' => 'رصيد ترحيبي للتجربة',
        ], 'loyalty');

        $this->get('/')->assertOk()->assertDontSee('رصيد ترحيبي للتجربة');

        $this->post('/register', [
            'name' => 'مستخدم صامت',
            'phone' => '01055557777',
            'password' => 'password12',
            'password_confirmation' => 'password12',
        ])->assertRedirect();

        $user = User::query()->where('phone', '201055557777')->first();

        $this->assertSame('50.00', $user->wallet_balance);

        $admin = $this->actingAsRole(RoleName::PlatformAdmin);

        $this->actingAs($admin)
            ->get('/admin/loyalty')
            ->assertOk()
            ->assertSee(__('admin.loyalty.campaign_live_hidden'))
            ->assertSee(__('admin.loyalty.signup_banner_enabled'));
    }

    public function test_admin_user_page_exposes_the_referral_link_and_wallet(): void
    {
        $admin = $this->actingAsRole(RoleName::PlatformAdmin);
        $patient = User::factory()->create(['name' => 'مريض الولاء']);
        $patient->assignRole(RoleName::Patient);

        $this->get('/admin/users/'.$patient->id)
            ->assertOk()
            ->assertSee('/register?ref='.$patient->referral_code, false)
            ->assertSee(__('admin.loyalty.balance'));

        $this->actingAs($patient)
            ->get('/account')
            ->assertOk()
            ->assertSee('/register?ref='.$patient->referral_code, false);

        $this->actingAs($admin);

        $this->from('/admin/users/'.$patient->id)
            ->put('/admin/users/'.$patient->id, [
                'action' => 'adjust_wallet',
                'amount' => 25,
                'note' => 'رصيد يدوي',
            ])
            ->assertRedirect();

        $this->assertSame('25.00', $patient->fresh()->wallet_balance);

        $this->get('/admin/loyalty')
            ->assertOk()
            ->assertSee(__('admin.loyalty.heading'))
            ->assertSee('/register?ref='.$admin->referral_code, false);
    }
}
