<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Enums\CollectionMode;
use App\Enums\LabOrderStatus;
use App\Enums\PaymentMode;
use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\LabOrder;
use App\Models\User;
use App\Services\NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBookingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_platform_bookings(): void
    {
        $this->get('/admin/bookings')->assertRedirect('/admin/login');
    }

    public function test_patients_cannot_open_platform_bookings(): void
    {
        $this->actingAsRole(RoleName::Patient);

        $this->get('/admin/bookings')->assertForbidden();
        $this->get('/admin/lab-orders')->assertForbidden();
    }

    public function test_support_agent_lists_and_opens_a_booking(): void
    {
        $this->freezeTime();

        $context = $this->seedListableProvider();
        $patient = User::factory()->create(['name' => 'ليلى الحجز']);
        $booking = Booking::factory()->create([
            'clinic_id' => $context['clinic']->id,
            'doctor_id' => $context['doctor']->id,
            'clinic_address_id' => $context['address']->id,
            'service_type_id' => $context['serviceType']->id,
            'patient_id' => $patient->id,
            'scheduled_at' => now(),
        ]);

        $this->actingAsRole(RoleName::SupportAgent);

        $this->get('/admin/bookings')
            ->assertOk()
            ->assertSee('ليلى الحجز')
            ->assertSee($context['clinic']->name);

        $this->get('/admin/bookings/'.$booking->id)
            ->assertOk()
            ->assertSee('ليلى الحجز');
    }

    public function test_support_agent_cancels_a_pending_booking(): void
    {
        $this->freezeTime();

        $context = $this->seedListableProvider();
        $booking = Booking::factory()->create([
            'clinic_id' => $context['clinic']->id,
            'doctor_id' => $context['doctor']->id,
            'clinic_address_id' => $context['address']->id,
            'service_type_id' => $context['serviceType']->id,
            'scheduled_at' => now(),
        ]);

        $this->mock(NotificationDispatcher::class)
            ->shouldReceive('send')
            ->once()
            ->withArgs(fn (string $event, array $payload): bool => $event === 'booking_cancelled'
                && (int) $payload['booking_id'] === $booking->id);

        $this->actingAsRole(RoleName::SupportAgent);

        $this->from('/admin/bookings/'.$booking->id)
            ->put('/admin/bookings/'.$booking->id, ['action' => 'cancel'])
            ->assertRedirect('/admin/bookings/'.$booking->id)
            ->assertSessionHas('status');

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_support_agent_cannot_confirm_a_booking(): void
    {
        $context = $this->seedListableProvider();
        $booking = Booking::factory()->create([
            'clinic_id' => $context['clinic']->id,
            'doctor_id' => $context['doctor']->id,
            'clinic_address_id' => $context['address']->id,
            'service_type_id' => $context['serviceType']->id,
        ]);

        $this->actingAsRole(RoleName::SupportAgent);

        $this->from('/admin/bookings/'.$booking->id)
            ->put('/admin/bookings/'.$booking->id, ['action' => 'confirm'])
            ->assertSessionHasErrors('action');

        $this->assertSame(BookingStatus::Pending, $booking->fresh()->status);
    }

    public function test_support_agent_lists_and_cancels_a_lab_order(): void
    {
        $this->freezeTime();

        $context = $this->seedListableProvider();
        $patient = User::factory()->create(['name' => 'مريض التحاليل']);
        $order = LabOrder::query()->create([
            'reference' => 'LAB-TEST01',
            'patient_id' => $patient->id,
            'clinic_id' => $context['clinic']->id,
            'clinic_address_id' => $context['address']->id,
            'collection_mode' => CollectionMode::Clinic,
            'scheduled_at' => now(),
            'status' => LabOrderStatus::Pending,
            'payment_mode' => PaymentMode::AtClinic,
            'payment_status' => 'unpaid',
            'total' => 250,
        ]);

        $this->mock(NotificationDispatcher::class)
            ->shouldReceive('send')
            ->once()
            ->withArgs(fn (string $event, array $payload): bool => $event === 'lab_order_cancelled'
                && (int) $payload['lab_order_id'] === $order->id);

        $this->actingAsRole(RoleName::SupportAgent);

        $this->get('/admin/lab-orders')
            ->assertOk()
            ->assertSee('مريض التحاليل')
            ->assertSee('LAB-TEST01');

        $this->from('/admin/lab-orders/'.$order->id)
            ->put('/admin/lab-orders/'.$order->id, ['action' => 'cancel'])
            ->assertRedirect('/admin/lab-orders/'.$order->id);

        $this->assertSame(LabOrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_admin_dashboard_shows_live_booking_pulse(): void
    {
        $this->freezeTime();

        $context = $this->seedListableProvider();
        Booking::factory()->create([
            'clinic_id' => $context['clinic']->id,
            'doctor_id' => $context['doctor']->id,
            'clinic_address_id' => $context['address']->id,
            'service_type_id' => $context['serviceType']->id,
            'scheduled_at' => now(),
            'status' => BookingStatus::Pending,
        ]);

        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->get('/admin')
            ->assertOk()
            ->assertSee(__('admin.dashboard.bookings_today'))
            ->assertSee(__('admin.dashboard.today_flow'));
    }
}
