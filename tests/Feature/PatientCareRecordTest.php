<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\CareDocumentType;
use App\Enums\LabOrderStatus;
use App\Enums\LabTestCategory;
use App\Enums\RoleName;
use App\Enums\SampleType;
use App\Models\Booking;
use App\Models\CareDocument;
use App\Models\ClinicLabOffering;
use App\Models\LabOrder;
use App\Models\LabTest;
use App\Models\User;
use App\Services\LabCart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientCareRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_booking_appears_in_patient_records_and_clinic_can_issue_a_prescription_pdf(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create();
        $owner = $provider['clinic']->owner;
        $owner->assignRole(RoleName::ClinicOwner, $provider['clinic']->id);

        $booking = Booking::factory()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
            'status' => BookingStatus::InProgress,
            'scheduled_at' => now()->subHour(),
        ]);

        $this->actingAs($owner)
            ->put('/clinic/queue/'.$booking->id, ['action' => 'complete'])
            ->assertRedirect();

        $consultation = CareDocument::query()->where('booking_id', $booking->id)->first();

        $this->assertNotNull($consultation);
        $this->assertSame(CareDocumentType::Consultation, $consultation->type);

        $this->actingAs($patient)
            ->get('/account/records')
            ->assertOk()
            ->assertSee(__('records.types.consultation'))
            ->assertSee($provider['clinic']->name);

        $this->actingAs($owner)
            ->post('/clinic/care-documents', [
                'booking_id' => $booking->id,
                'type' => CareDocumentType::Prescription->value,
                'title' => 'روشتة الكشف',
                'medications' => [
                    [
                        'name' => 'أموكسيسيلين',
                        'dose' => '500 مجم',
                        'frequency' => 'مرتين يوميًا',
                        'duration' => '7 أيام',
                    ],
                ],
            ])
            ->assertRedirect();

        $prescription = CareDocument::query()
            ->where('booking_id', $booking->id)
            ->where('type', CareDocumentType::Prescription)
            ->first();

        $this->assertNotNull($prescription);
        $this->assertNotEmpty($prescription->verification_code);

        $this->actingAs($patient)
            ->get('/account/records/'.$prescription->id)
            ->assertOk()
            ->assertSee('أموكسيسيلين')
            ->assertSee($prescription->verification_code);

        $this->actingAs($patient)
            ->get('/account/records/'.$prescription->id.'/pdf')
            ->assertOk()
            ->assertSee('أموكسيسيلين')
            ->assertSee($prescription->verification_code)
            ->assertHeader('content-disposition', 'inline; filename="'.$prescription->downloadName().'"');

        $this->get('/verify/'.$prescription->verification_code)
            ->assertOk()
            ->assertSee(__('records.verified'))
            ->assertSee($prescription->verification_code);

        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get('/account/records/'.$prescription->id)
            ->assertNotFound();
    }

    public function test_completed_lab_order_results_appear_in_patient_records(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $test = LabTest::query()->create([
            'slug' => 'cbc-record',
            'name_ar' => 'صورة دم كاملة',
            'name_en' => 'CBC',
            'category' => LabTestCategory::Blood,
            'sample_type' => SampleType::Blood,
            'suggested_price' => 180,
            'is_active' => true,
        ]);

        ClinicLabOffering::query()->create([
            'clinic_id' => $provider['clinic']->id,
            'item_type' => 'test',
            'lab_test_id' => $test->id,
            'price' => 200,
            'allows_home_collection' => true,
            'is_active' => true,
        ]);

        $patient = User::factory()->create();
        $owner = $provider['clinic']->owner;
        $owner->assignRole(RoleName::ClinicOwner, $provider['clinic']->id);

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
        $item = $order->items()->first();

        $this->actingAs($owner)
            ->put('/clinic/lab-orders/'.$order->id, ['action' => 'confirm'])
            ->assertRedirect();

        $this->actingAs($owner)
            ->put('/clinic/lab-orders/'.$order->id, [
                'action' => 'complete',
                'results' => [
                    $item->id => [
                        'value' => '13.5',
                        'unit' => 'g/dL',
                        'flag' => 'طبيعي',
                        'note' => 'ضمن المعدل',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(LabOrderStatus::Completed, $order->fresh()->status);
        $this->assertSame('13.5', $item->fresh()->result_value);

        $document = CareDocument::query()->where('lab_order_id', $order->id)->first();

        $this->assertNotNull($document);
        $this->assertSame(CareDocumentType::LabResult, $document->type);

        $this->actingAs($patient)
            ->get('/account/records')
            ->assertOk()
            ->assertSee(__('records.types.lab_result'))
            ->assertSee($order->reference);

        $this->actingAs($patient)
            ->get('/account/records/'.$document->id)
            ->assertOk()
            ->assertSee('13.5')
            ->assertSee('ضمن المعدل');
    }

    public function test_guest_is_redirected_from_records(): void
    {
        $this->get('/account/records')->assertRedirect('/login');
    }
}
