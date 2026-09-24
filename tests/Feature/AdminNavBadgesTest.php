<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\OfferApprovalStatus;
use App\Enums\OfferCategory;
use App\Enums\RoleName;
use App\Enums\VerificationStatus;
use App\Models\AgentConversation;
use App\Models\Booking;
use App\Models\Clinic;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use App\Models\Promotion;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\AdminNavBadges;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavBadgesTest extends TestCase
{
    use RefreshDatabase;

    public function test_nav_badges_count_pending_items(): void
    {
        $catalog = $this->seedClinicCatalog();

        $clinic = Clinic::factory()->create([
            'subscription_plan_id' => $catalog['plan']->id,
            'verification_status' => VerificationStatus::Pending,
        ]);

        $address = ClinicAddress::factory()->create([
            'clinic_id' => $clinic->id,
            'city_id' => $catalog['city']->id,
        ]);

        $doctor = Doctor::factory()->create([
            'specialty_id' => $catalog['specialty']->id,
        ]);

        Booking::factory()->create([
            'clinic_id' => $clinic->id,
            'doctor_id' => $doctor->id,
            'clinic_address_id' => $address->id,
            'service_type_id' => $catalog['serviceType']->id,
            'status' => BookingStatus::Pending,
        ]);

        Booking::factory()->create([
            'clinic_id' => $clinic->id,
            'doctor_id' => $doctor->id,
            'clinic_address_id' => $address->id,
            'service_type_id' => $catalog['serviceType']->id,
            'status' => BookingStatus::Confirmed,
        ]);

        Promotion::query()->create([
            'title_ar' => 'قيد المراجعة',
            'title_en' => 'Pending offer',
            'slug' => 'pending-offer-badge',
            'category' => OfferCategory::Lab,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'starts_at' => now(),
            'ends_at' => now()->addWeek(),
            'is_active' => false,
            'approval_status' => OfferApprovalStatus::Pending,
        ]);

        SupportTicket::create([
            'opened_by_user_id' => User::factory()->create()->id,
            'channel' => 'chat',
            'subject' => 'مساعدة',
            'category' => 'booking',
        ]);

        AgentConversation::query()->create([
            'channel' => 'web',
            'locale' => 'ar',
            'visitor_name' => 'زائر',
            'started_at' => now(),
            'last_message_at' => now(),
            'message_count' => 1,
        ]);

        $badges = app(AdminNavBadges::class)->all();

        $this->assertSame(1, $badges['bookings']);
        $this->assertSame(1, $badges['promotions']);
        $this->assertSame(1, $badges['support']);
        $this->assertSame(1, $badges['agent']);
        $this->assertGreaterThanOrEqual(1, $badges['clinics']);
    }

    public function test_admin_layout_shows_site_settings_and_booking_badge(): void
    {
        $catalog = $this->seedClinicCatalog();
        $clinic = Clinic::factory()->create(['subscription_plan_id' => $catalog['plan']->id]);
        $address = ClinicAddress::factory()->create([
            'clinic_id' => $clinic->id,
            'city_id' => $catalog['city']->id,
        ]);
        $doctor = Doctor::factory()->create(['specialty_id' => $catalog['specialty']->id]);

        Booking::factory()->create([
            'clinic_id' => $clinic->id,
            'doctor_id' => $doctor->id,
            'clinic_address_id' => $address->id,
            'service_type_id' => $catalog['serviceType']->id,
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->get('/admin/bookings')
            ->assertOk()
            ->assertSee(__('admin.nav.site_settings'));
    }

    public function test_opening_agent_conversation_clears_unseen_badge(): void
    {
        $this->seedRoles();

        $conversation = AgentConversation::query()->create([
            'channel' => 'web',
            'locale' => 'ar',
            'visitor_name' => 'زائر',
            'started_at' => now(),
            'last_message_at' => now(),
            'message_count' => 1,
        ]);

        $this->assertSame(1, app(AdminNavBadges::class)->all()['agent']);

        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->get('/admin/agent-conversations/'.$conversation->id)->assertOk();

        $this->assertNotNull($conversation->fresh()->admin_seen_at);
        $this->assertSame(0, app(AdminNavBadges::class)->all()['agent']);
    }
}
