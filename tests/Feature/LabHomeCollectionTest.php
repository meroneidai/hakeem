<?php

namespace Tests\Feature;

use App\Enums\CollectionMode;
use App\Enums\LabTestCategory;
use App\Enums\RoleName;
use App\Enums\SampleType;
use App\Models\ClinicLabOffering;
use App\Models\LabOrder;
use App\Models\LabTest;
use App\Models\PatientAddress;
use App\Models\User;
use App\Services\LabCart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabHomeCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_collection_uses_a_separate_price_from_the_lab(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $test = $this->labTest();

        ClinicLabOffering::query()->create([
            'clinic_id' => $provider['clinic']->id,
            'item_type' => 'test',
            'lab_test_id' => $test->id,
            'price' => 200,
            'home_price' => 350,
            'allows_home_collection' => true,
            'is_active' => true,
        ]);

        $patient = User::factory()->create();

        $this->actingAs($patient)
            ->withSession([LabCart::SESSION_KEY => [['type' => 'test', 'id' => $test->id, 'qty' => 1]]])
            ->post('/labs/checkout', [
                'clinic_id' => $provider['clinic']->id,
                'collection_mode' => CollectionMode::Home->value,
                'collection_date' => now()->addDay()->toDateString(),
                'collection_time' => '10:00',
                'payment_mode' => 'at_clinic',
                'patient_home_address' => 'مدينة نصر، شارع عباس العقاد، عمارة 12',
                'address_label' => 'المنزل',
                'save_address' => '1',
                'latitude' => '30.0567123',
                'longitude' => '31.3300456',
            ])
            ->assertRedirect();

        $order = LabOrder::query()->first();

        $this->assertNotNull($order);
        $this->assertSame(CollectionMode::Home, $order->collection_mode);
        $this->assertSame('350.00', $order->total);
        $this->assertSame('مدينة نصر، شارع عباس العقاد، عمارة 12', $order->patient_home_address);
        $this->assertEqualsWithDelta(30.0567123, (float) $order->latitude, 0.0000001);
        $this->assertEqualsWithDelta(31.3300456, (float) $order->longitude, 0.0000001);
        $this->assertSame(now()->addDay()->toDateString().' 10:00:00', $order->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('patient_addresses', [
            'user_id' => $patient->id,
            'label' => 'المنزل',
            'line' => 'مدينة نصر، شارع عباس العقاد، عمارة 12',
            'latitude' => '30.0567123',
            'longitude' => '31.3300456',
        ]);
        $this->assertNotNull($order->patient_address_id);
        $this->assertNotNull($order->mapsUrl());
    }

    public function test_home_collection_uses_a_saved_address_point(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $test = $this->labTest();

        ClinicLabOffering::query()->create([
            'clinic_id' => $provider['clinic']->id,
            'item_type' => 'test',
            'lab_test_id' => $test->id,
            'price' => 200,
            'home_price' => 280,
            'allows_home_collection' => true,
            'is_active' => true,
        ]);

        $patient = User::factory()->create();
        $address = PatientAddress::factory()->create([
            'user_id' => $patient->id,
            'label' => 'العمل',
            'line' => 'وسط البلد، مبنى التحرير',
            'city_id' => $provider['city']->id,
            'latitude' => 30.0444000,
            'longitude' => 31.2357000,
        ]);

        $this->actingAs($patient)
            ->withSession([LabCart::SESSION_KEY => [['type' => 'test', 'id' => $test->id, 'qty' => 1]]])
            ->post('/labs/checkout', [
                'clinic_id' => $provider['clinic']->id,
                'collection_mode' => CollectionMode::Home->value,
                'collection_date' => now()->addDay()->toDateString(),
                'collection_time' => '16:30',
                'payment_mode' => 'at_clinic',
                'patient_address_id' => $address->id,
                'latitude' => '30.0444000',
                'longitude' => '31.2357000',
            ])
            ->assertRedirect();

        $order = LabOrder::query()->first();

        $this->assertSame('280.00', $order->total);
        $this->assertSame($address->id, $order->patient_address_id);
        $this->assertSame('وسط البلد، مبنى التحرير', $order->patient_home_address);
        $this->assertEqualsWithDelta(30.0444, (float) $order->latitude, 0.0001);
        $this->assertEqualsWithDelta(31.2357, (float) $order->longitude, 0.0001);
    }

    public function test_home_collection_is_rejected_without_a_map_pin(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $test = $this->labTest();

        ClinicLabOffering::query()->create([
            'clinic_id' => $provider['clinic']->id,
            'item_type' => 'test',
            'lab_test_id' => $test->id,
            'price' => 200,
            'home_price' => 350,
            'allows_home_collection' => true,
            'is_active' => true,
        ]);

        $patient = User::factory()->create();

        $this->actingAs($patient)
            ->from('/labs/checkout')
            ->withSession([LabCart::SESSION_KEY => [['type' => 'test', 'id' => $test->id, 'qty' => 1]]])
            ->post('/labs/checkout', [
                'clinic_id' => $provider['clinic']->id,
                'collection_mode' => CollectionMode::Home->value,
                'collection_date' => now()->addDay()->toDateString(),
                'collection_time' => '10:00',
                'payment_mode' => 'at_clinic',
                'patient_home_address' => 'مدينة نصر',
            ])
            ->assertRedirect('/labs/checkout')
            ->assertSessionHasErrors('latitude');

        $this->assertSame(0, LabOrder::query()->count());
    }

    public function test_checkout_asks_for_a_map_arrival_point(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $test = $this->labTest();

        ClinicLabOffering::query()->create([
            'clinic_id' => $provider['clinic']->id,
            'item_type' => 'test',
            'lab_test_id' => $test->id,
            'price' => 200,
            'home_price' => 350,
            'allows_home_collection' => true,
            'is_active' => true,
        ]);

        $patient = User::factory()->create();

        $this->actingAs($patient)
            ->withSession([LabCart::SESSION_KEY => [['type' => 'test', 'id' => $test->id, 'qty' => 1]]])
            ->get('/labs/checkout')
            ->assertOk()
            ->assertSee(__('labs.checkout.map'))
            ->assertSee('data-map-canvas', false);
    }

    public function test_clinic_staff_see_the_arrival_pin_and_patient_contact(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $test = $this->labTest();

        ClinicLabOffering::query()->create([
            'clinic_id' => $provider['clinic']->id,
            'item_type' => 'test',
            'lab_test_id' => $test->id,
            'price' => 200,
            'home_price' => 350,
            'allows_home_collection' => true,
            'is_active' => true,
        ]);

        $patient = User::factory()->create([
            'name' => 'منى إبراهيم',
            'phone' => '201011112222',
        ]);

        $this->actingAs($patient)
            ->withSession([LabCart::SESSION_KEY => [['type' => 'test', 'id' => $test->id, 'qty' => 1]]])
            ->post('/labs/checkout', [
                'clinic_id' => $provider['clinic']->id,
                'collection_mode' => CollectionMode::Home->value,
                'collection_date' => now()->addDay()->toDateString(),
                'collection_time' => '10:00',
                'payment_mode' => 'at_clinic',
                'patient_home_address' => 'مدينة نصر، شارع عباس العقاد، عمارة 12',
                'latitude' => '30.0567123',
                'longitude' => '31.3300456',
            ])
            ->assertRedirect();

        $order = LabOrder::query()->first();
        $owner = $provider['clinic']->owner;
        $owner->assignRole(RoleName::ClinicOwner, $provider['clinic']->id);

        $this->actingAs($owner)
            ->get('/clinic/lab-orders/'.$order->id)
            ->assertOk()
            ->assertSee('منى إبراهيم')
            ->assertSee('201011112222')
            ->assertSee('مدينة نصر، شارع عباس العقاد، عمارة 12')
            ->assertSee('destination=30.0567123,31.3300456', false)
            ->assertSee('tel:+201011112222', false)
            ->assertSee('https://wa.me/201011112222', false);
    }

    private function labTest(): LabTest
    {
        return LabTest::query()->create([
            'slug' => 'cbc-home',
            'name_ar' => 'صورة دم منزلية',
            'name_en' => 'CBC Home',
            'category' => LabTestCategory::Blood,
            'sample_type' => SampleType::Blood,
            'suggested_price' => 180,
            'is_active' => true,
        ]);
    }
}
