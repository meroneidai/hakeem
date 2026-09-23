<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctors_directory_filters_by_gender_and_specialty(): void
    {
        $context = $this->seedClinicCatalog();
        $address = ClinicAddress::factory()->create([
            'clinic_id' => Clinic::factory()->verified()->create([
                'subscription_plan_id' => $context['plan']->id,
            ])->id,
            'city_id' => $context['city']->id,
        ]);

        $male = Doctor::factory()->create([
            'name_ar' => 'د. كريم',
            'name_en' => 'Dr. Karim',
            'specialty_id' => $context['specialty']->id,
            'gender' => 'male',
            'years_of_experience' => 12,
            'consultation_fee' => 350,
        ]);
        $female = Doctor::factory()->create([
            'name_ar' => 'د. منى',
            'name_en' => 'Dr. Mona',
            'specialty_id' => $context['specialty']->id,
            'gender' => 'female',
            'years_of_experience' => 4,
        ]);

        $address->clinic->doctors()->attach([$male->id, $female->id]);

        $this->get('/doctors')
            ->assertOk()
            ->assertSee('د. كريم')
            ->assertSee('د. منى');

        $this->get('/doctors?gender=male')
            ->assertOk()
            ->assertSee('د. كريم')
            ->assertDontSee('د. منى');

        $this->get('/doctors?min_experience=10')
            ->assertOk()
            ->assertSee('د. كريم')
            ->assertDontSee('د. منى');

        $this->get('/doctors?governorate='.$context['governorate']->slug)
            ->assertOk()
            ->assertSee('د. كريم');
    }

    public function test_doctors_directory_hides_doctors_without_a_listable_clinic(): void
    {
        $context = $this->seedClinicCatalog();
        $pendingClinic = Clinic::factory()->create([
            'subscription_plan_id' => $context['plan']->id,
        ]);
        $pendingDoctor = Doctor::factory()->create([
            'name_ar' => 'د. غير ظاهر',
            'name_en' => 'Dr. Hidden',
            'specialty_id' => $context['specialty']->id,
        ]);
        $pendingClinic->doctors()->attach($pendingDoctor->id);

        $this->get('/doctors')
            ->assertOk()
            ->assertDontSee('د. غير ظاهر');

        $this->get('/doctors/'.$pendingDoctor->slug)->assertNotFound();
    }
}
