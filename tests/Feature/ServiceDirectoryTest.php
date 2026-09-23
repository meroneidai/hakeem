<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\ServiceTypeCode;
use App\Models\Booking;
use App\Models\City;
use App\Models\Clinic;
use App\Models\ClinicAddress;
use App\Models\ClinicService;
use App\Models\Doctor;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_video_consultation_lists_offering_doctors_with_clinic_visits_and_actions(): void
    {
        $provider = $this->seedListableProvider();
        $service = $this->createDoctorLedService(ServiceTypeCode::VideoConsultation, 'video-consultation', 'استشارة بالفيديو', 'Video Consultation');
        $this->offer($provider['clinic'], $service, $provider['specialty']->id);

        Booking::factory()->create([
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $service->id,
            'status' => BookingStatus::Cancelled,
        ]);
        Booking::factory()->completed()->count(2)->create([
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $service->id,
        ]);

        $this->get('/services/video-consultation')
            ->assertOk()
            ->assertSee(__('discover.services_page.doctors'))
            ->assertDontSee(__('discover.services_page.providers'))
            ->assertSee($provider['doctor']->name_ar)
            ->assertSee($provider['clinic']->name_ar)
            ->assertSee(__('discover.doctors.visits', ['count' => 2]))
            ->assertSee(__('discover.view_profile'))
            ->assertSee(__('discover.book_now'))
            ->assertSee(route('doctors.show', $provider['doctor']))
            ->assertSee(route('book.doctors.create', ['doctor' => $provider['doctor'], 'service_type_id' => $service->id]))
            ->assertSee('name="q"', false)
            ->assertSee('name="specialty"', false)
            ->assertSee('name="city"', false)
            ->assertSee('name="gender"', false)
            ->assertDontSee('<x-input', false);
    }

    public function test_clinic_led_service_lists_clinic_then_its_doctors_with_profile_and_book_actions(): void
    {
        $provider = $this->seedListableProvider();
        $this->offer($provider['clinic'], $provider['serviceType'], $provider['specialty']->id);

        $this->get('/services/'.$provider['serviceType']->slug)
            ->assertOk()
            ->assertSee(__('discover.services_page.providers'))
            ->assertDontSee(__('discover.services_page.doctors'))
            ->assertSee($provider['clinic']->name_ar)
            ->assertSee($provider['doctor']->name_ar)
            ->assertSee(__('discover.services_page.browse_clinic'))
            ->assertSee(route('clinics.show', $provider['clinic']))
            ->assertSee(route('doctors.show', $provider['doctor']))
            ->assertSee(route('book.doctors.create', ['doctor' => $provider['doctor'], 'service_type_id' => $provider['serviceType']->id]))
            ->assertDontSee('<x-input', false);
    }

    public function test_doctor_led_directory_hides_doctors_whose_clinics_do_not_offer_the_service(): void
    {
        $listed = $this->seedListableProvider();
        $service = $this->createDoctorLedService(ServiceTypeCode::HomeVisit, 'home-visit', 'زيارة منزلية', 'Home Visit');
        $this->offer($listed['clinic'], $service, $listed['specialty']->id);

        $hiddenClinic = Clinic::factory()->verified()->create([
            'subscription_plan_id' => $listed['plan']->id,
            'name_ar' => 'عيادة الشفاء',
            'name_en' => 'Al Shifa Clinic',
        ]);
        ClinicAddress::factory()->create([
            'clinic_id' => $hiddenClinic->id,
            'city_id' => $listed['city']->id,
        ]);
        $hiddenDoctor = Doctor::factory()->create([
            'specialty_id' => $listed['specialty']->id,
            'name_ar' => 'د. هدى سالم',
            'name_en' => 'Dr. Hoda Salem',
        ]);
        $hiddenClinic->doctors()->attach($hiddenDoctor);

        $this->get('/services/home-visit')
            ->assertOk()
            ->assertSee($listed['doctor']->name_ar)
            ->assertDontSee($hiddenDoctor->name_ar);
    }

    public function test_gender_filter_keeps_matching_doctors_on_a_doctor_led_service(): void
    {
        $provider = $this->seedListableProvider();
        $service = $this->createDoctorLedService(ServiceTypeCode::PsychiatricConsultation, 'psychiatric-consultation', 'استشارة نفسية', 'Psychiatric Consultation');
        $this->offer($provider['clinic'], $service, $provider['specialty']->id);

        $male = Doctor::factory()->create([
            'specialty_id' => $provider['specialty']->id,
            'name_ar' => 'د. كريم علي',
            'name_en' => 'Dr. Karim Ali',
            'gender' => 'male',
        ]);
        $provider['clinic']->doctors()->attach($male);

        $this->get('/services/psychiatric-consultation?gender=female')
            ->assertOk()
            ->assertSee($provider['doctor']->name_ar)
            ->assertDontSee($male->name_ar);
    }

    public function test_city_filter_keeps_clinics_in_the_selected_city_on_a_clinic_led_service(): void
    {
        $provider = $this->seedListableProvider();
        $this->offer($provider['clinic'], $provider['serviceType'], $provider['specialty']->id);

        $otherCity = City::query()->create([
            'governorate_id' => $provider['governorate']->id,
            'name_ar' => 'المعادي',
            'name_en' => 'Maadi',
            'slug' => 'maadi-test',
            'is_active' => true,
        ]);
        $otherClinic = Clinic::factory()->verified()->create([
            'subscription_plan_id' => $provider['plan']->id,
            'name_ar' => 'عيادة المعادي',
            'name_en' => 'Maadi Clinic',
        ]);
        ClinicAddress::factory()->create([
            'clinic_id' => $otherClinic->id,
            'city_id' => $otherCity->id,
        ]);
        $otherDoctor = Doctor::factory()->create([
            'specialty_id' => $provider['specialty']->id,
            'name_ar' => 'د. كريم علي',
            'name_en' => 'Dr. Karim Ali',
            'gender' => 'male',
        ]);
        $otherClinic->doctors()->attach($otherDoctor);
        $this->offer($otherClinic, $provider['serviceType'], $provider['specialty']->id);

        $this->get('/services/'.$provider['serviceType']->slug.'?city='.$provider['city']->slug)
            ->assertOk()
            ->assertSee($provider['clinic']->name_ar)
            ->assertSee($provider['doctor']->name_ar)
            ->assertDontSee($otherClinic->name_ar)
            ->assertDontSee($otherDoctor->name_ar);
    }

    public function test_booking_form_preselects_service_type_from_the_query_string(): void
    {
        $provider = $this->seedListableProvider();
        $service = $this->createDoctorLedService(ServiceTypeCode::VideoConsultation, 'video-consultation', 'استشارة بالفيديو', 'Video Consultation');
        $this->offer($provider['clinic'], $service, $provider['specialty']->id);

        $this->actingAs(User::factory()->create())
            ->get('/book/doctors/'.$provider['doctor']->slug.'?service_type_id='.$service->id)
            ->assertOk()
            ->assertSee('value="'.$service->id.'" selected', false);
    }

    public function test_inactive_service_page_is_not_found(): void
    {
        ServiceType::query()->create([
            'code' => ServiceTypeCode::HomeVisit->value,
            'name_ar' => 'زيارة منزلية',
            'name_en' => 'Home Visit',
            'slug' => 'home-visit',
            'is_active' => false,
        ]);

        $this->get('/services/home-visit')->assertNotFound();
    }

    public function test_rejects_an_invalid_gender_filter(): void
    {
        $provider = $this->seedListableProvider();
        $this->offer($provider['clinic'], $provider['serviceType'], $provider['specialty']->id);

        $this->from('/services/'.$provider['serviceType']->slug)
            ->get('/services/'.$provider['serviceType']->slug.'?gender=other')
            ->assertRedirect('/services/'.$provider['serviceType']->slug)
            ->assertSessionHasErrors('gender');
    }

    public function test_escapes_doctor_names_on_the_service_directory(): void
    {
        $provider = $this->seedListableProvider([
            'doctor_name_ar' => '<script>alert(1)</script>',
            'doctor_name_en' => 'Safe Doctor',
        ]);
        $service = $this->createDoctorLedService(ServiceTypeCode::VideoConsultation, 'video-consultation', 'استشارة بالفيديو', 'Video Consultation');
        $this->offer($provider['clinic'], $service, $provider['specialty']->id);

        $this->get('/services/video-consultation')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_teleconsultation_landing_lists_doctors_offering_video_consultation(): void
    {
        $provider = $this->seedListableProvider();
        $service = $this->createDoctorLedService(ServiceTypeCode::VideoConsultation, 'video-consultation', 'استشارة بالفيديو', 'Video Consultation');
        $this->offer($provider['clinic'], $service, $provider['specialty']->id);

        $this->get('/teleconsultation')
            ->assertOk()
            ->assertSee($provider['doctor']->name_ar)
            ->assertSee('/services/video-consultation', false);
    }

    public function test_service_city_landing_lists_doctors_offering_the_service(): void
    {
        $provider = $this->seedListableProvider();
        $service = $this->createDoctorLedService(ServiceTypeCode::HomeVisit, 'home-visit', 'زيارة منزلية', 'Home Visit');
        $this->offer($provider['clinic'], $service, $provider['specialty']->id);

        $this->get('/services/home-visit/'.$provider['city']->slug)
            ->assertOk()
            ->assertSee(__('discover.services_page.in_place', [
                'service' => $service->name,
                'place' => $provider['city']->name,
            ]))
            ->assertSee($provider['doctor']->name_ar);
    }

    private function createDoctorLedService(ServiceTypeCode $code, string $slug, string $nameAr, string $nameEn): ServiceType
    {
        return ServiceType::query()->create([
            'code' => $code->value,
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function offer(Clinic $clinic, ServiceType $serviceType, ?int $specialtyId = null): void
    {
        ClinicService::query()->create([
            'clinic_id' => $clinic->id,
            'service_type_id' => $serviceType->id,
            'specialty_id' => $specialtyId,
            'price' => 400,
            'is_active' => true,
        ]);
    }
}
