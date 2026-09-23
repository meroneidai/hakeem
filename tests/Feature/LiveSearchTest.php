<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_json_search_returns_listable_doctors_as_you_type(): void
    {
        $provider = $this->seedListableProvider();

        $this->getJson('/api/v1/search?q=سارة')
            ->assertOk()
            ->assertJsonPath('q', 'سارة')
            ->assertJsonPath('doctors.0.name', $provider['doctor']->name)
            ->assertJsonPath('doctors.0.book_url', route('book.doctors.create', $provider['doctor']));
    }

    public function test_json_search_hides_doctors_from_unverified_clinics(): void
    {
        $provider = $this->seedListableProvider();
        $clinic = Clinic::factory()->create([
            'subscription_plan_id' => $provider['plan']->id,
            'name_ar' => 'عيادة مخفية للبحث',
            'name_en' => 'Hidden Search Clinic',
        ]);
        $doctor = Doctor::factory()->create([
            'specialty_id' => $provider['specialty']->id,
            'name_ar' => 'طبيب غير ظاهر',
            'name_en' => 'Hidden Doctor',
        ]);
        $clinic->doctors()->attach($doctor);

        $this->getJson('/api/v1/search?q=Hidden')
            ->assertOk()
            ->assertJsonPath('counts.doctors', 0)
            ->assertJsonMissing(['name' => 'Hidden Doctor']);
    }

    public function test_json_search_rejects_invalid_filters(): void
    {
        $this->getJson('/api/v1/search?type=robots')->assertUnprocessable();
    }

    public function test_live_filtering_returns_the_results_fragment_with_the_shared_doctor_card(): void
    {
        $provider = $this->seedListableProvider();

        $this->withHeader('X-Search-Fragment', '1')
            ->get('/search?type=doctors&specialty='.$provider['specialty']->slug)
            ->assertOk()
            ->assertSee($provider['doctor']->name_ar)
            ->assertSee(__('discover.view_profile'))
            ->assertSee(route('book.doctors.create', $provider['doctor']))
            ->assertDontSee('</html>', false)
            ->assertDontSee('name="q"', false);
    }

    public function test_html_search_still_finds_doctors_without_javascript(): void
    {
        $provider = $this->seedListableProvider();

        $this->get('/search?q=سارة&specialty='.$provider['specialty']->slug)
            ->assertOk()
            ->assertSee($provider['doctor']->name_ar)
            ->assertSee('noindex,nofollow');
    }
}
