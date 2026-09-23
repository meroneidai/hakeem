<?php

namespace Tests\Feature;

use App\Enums\LabOrderStatus;
use App\Enums\LabTestCategory;
use App\Enums\RoleName;
use App\Enums\SampleType;
use App\Models\ClinicLabOffering;
use App\Models\LabOrder;
use App\Models\LabTest;
use App\Models\User;
use App\Services\LabCart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_lab_filters_still_list_catalog_items(): void
    {
        $this->seedRoles();
        $this->labTest();

        $this->get('/labs?q=&max_price=')
            ->assertOk()
            ->assertSee('صورة دم');
    }

    public function test_guest_can_add_to_cart_and_must_login_to_checkout(): void
    {
        $this->seedRoles();
        $test = $this->labTest();

        $this->post('/labs/cart', ['type' => 'test', 'id' => $test->id])
            ->assertRedirect();

        $this->get('/labs/cart')
            ->assertOk()
            ->assertSee('صورة دم');

        $this->get('/labs/checkout')->assertRedirect('/login');
    }

    public function test_patient_checks_out_a_lab_order_and_clinic_confirms_it(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $test = $this->labTest();

        ClinicLabOffering::query()->create([
            'clinic_id' => $provider['clinic']->id,
            'item_type' => 'test',
            'lab_test_id' => $test->id,
            'price' => 200,
            'allows_home_collection' => true,
            'is_active' => true,
        ]);

        $patient = User::factory()->create();

        $this->actingAs($patient)
            ->withSession([LabCart::SESSION_KEY => [['type' => 'test', 'id' => $test->id, 'qty' => 1]]])
            ->post('/labs/checkout', [
                'clinic_id' => $provider['clinic']->id,
                'clinic_address_id' => $provider['address']->id,
                'collection_mode' => 'clinic',
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'payment_mode' => 'at_clinic',
            ])
            ->assertRedirect();

        $order = LabOrder::query()->first();

        $this->assertNotNull($order);
        $this->assertSame($patient->id, $order->patient_id);
        $this->assertSame(LabOrderStatus::Pending, $order->status);
        $this->assertSame('200.00', $order->total);
        $this->assertSame(1, $order->items()->count());
        $this->assertSame([], session(LabCart::SESSION_KEY, []));

        $owner = $provider['clinic']->owner;
        $owner->assignRole(RoleName::ClinicOwner, $provider['clinic']->id);

        $this->actingAs($owner)
            ->put('/clinic/lab-orders/'.$order->id, ['action' => 'confirm'])
            ->assertRedirect();

        $this->assertSame(LabOrderStatus::Confirmed, $order->fresh()->status);
    }

    public function test_patient_order_page_shows_payment_and_hides_foreign_orders(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $test = $this->labTest();

        ClinicLabOffering::query()->create([
            'clinic_id' => $provider['clinic']->id,
            'item_type' => 'test',
            'lab_test_id' => $test->id,
            'price' => 200,
            'allows_home_collection' => true,
            'is_active' => true,
        ]);

        $patient = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($patient)
            ->withSession([LabCart::SESSION_KEY => [['type' => 'test', 'id' => $test->id, 'qty' => 1]]])
            ->post('/labs/checkout', [
                'clinic_id' => $provider['clinic']->id,
                'clinic_address_id' => $provider['address']->id,
                'collection_mode' => 'clinic',
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'payment_mode' => 'at_clinic',
                'notes' => 'صيام قبل السحب',
            ])
            ->assertRedirect();

        $order = LabOrder::query()->first();

        $this->actingAs($patient)
            ->get('/labs/orders/'.$order->id)
            ->assertOk()
            ->assertSee($order->reference)
            ->assertSee(__('booking.payment_mode.at_clinic'))
            ->assertSee(__('booking.payment_status.unpaid'))
            ->assertSee('صيام قبل السحب')
            ->assertSee('صورة دم');

        $this->actingAs($other)
            ->get('/labs/orders/'.$order->id)
            ->assertNotFound();
    }

    private function labTest(): LabTest
    {
        return LabTest::query()->create([
            'slug' => 'cbc-checkout',
            'name_ar' => 'صورة دم',
            'name_en' => 'CBC',
            'category' => LabTestCategory::Blood,
            'sample_type' => SampleType::Blood,
            'suggested_price' => 180,
            'is_active' => true,
        ]);
    }
}
