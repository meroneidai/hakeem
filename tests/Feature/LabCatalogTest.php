<?php

namespace Tests\Feature;

use App\Enums\LabTestCategory;
use App\Enums\RoleName;
use App\Enums\SampleType;
use App\Models\ClinicLabOffering;
use App\Models\LabPackage;
use App\Models\LabTest;
use App\Services\LabCart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LabCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_lab_catalog_lists_active_tests_and_packages(): void
    {
        [$cbc, $package] = $this->seedCatalog();

        LabTest::query()->create([
            'slug' => 'hidden-test',
            'name_ar' => 'مخفي',
            'name_en' => 'Hidden',
            'category' => LabTestCategory::Blood,
            'sample_type' => SampleType::Blood,
            'suggested_price' => 10,
            'is_active' => false,
        ]);

        $this->get('/labs')
            ->assertOk()
            ->assertSee('صورة دم كاملة')
            ->assertSee('فحص شامل')
            ->assertSee('images/labs/blood.svg', false)
            ->assertDontSee('مخفي');

        $this->get('/labs/tests/'.$cbc->slug)
            ->assertOk()
            ->assertSee('الهيموجلوبين');

        $this->get('/labs/packages/'.$package->slug)
            ->assertOk()
            ->assertSee('CBC');
    }

    public function test_inactive_lab_pages_return_not_found(): void
    {
        $test = LabTest::query()->create([
            'slug' => 'inactive-cbc',
            'name_ar' => 'غير نشط',
            'name_en' => 'Inactive',
            'category' => LabTestCategory::Blood,
            'sample_type' => SampleType::Blood,
            'suggested_price' => 10,
            'is_active' => false,
        ]);

        $this->get('/labs/tests/'.$test->slug)->assertNotFound();
    }

    public function test_cart_adds_and_removes_tests_and_packages(): void
    {
        [$cbc, $package] = $this->seedCatalog();

        $this->from('/labs')->post('/labs/cart', ['type' => 'test', 'id' => $cbc->id])
            ->assertRedirect('/labs');

        $this->from('/labs')->post('/labs/cart', ['type' => 'package', 'id' => $package->id])
            ->assertRedirect('/labs');

        $this->get('/labs/cart')
            ->assertOk()
            ->assertSee('صورة دم كاملة')
            ->assertSee('فحص شامل');

        $this->assertSame(2, app(LabCart::class)->count());
        $this->assertEquals(1070.0, app(LabCart::class)->total());

        $this->from('/labs/cart')->delete('/labs/cart', ['type' => 'test', 'id' => $cbc->id])
            ->assertRedirect('/labs/cart');

        $this->assertSame(1, app(LabCart::class)->count());
    }

    public function test_json_cart_add_is_idempotent_and_updates_quantity_without_redirect(): void
    {
        [$cbc] = $this->seedCatalog();

        $this->postJson('/labs/cart', ['type' => 'test', 'id' => $cbc->id])
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('keys.0', 'test:'.$cbc->id);

        $this->postJson('/labs/cart', ['type' => 'test', 'id' => $cbc->id])
            ->assertOk()
            ->assertJsonPath('count', 1);

        $this->patchJson('/labs/cart', ['type' => 'test', 'id' => $cbc->id, 'qty' => 3])
            ->assertOk()
            ->assertJsonPath('count', 3);

        $this->assertSame(3, app(LabCart::class)->count());
    }

    public function test_city_filter_lists_only_tests_offered_by_listable_labs_there(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        [$offered, $package] = $this->seedCatalog();

        $elsewhere = LabTest::query()->create([
            'slug' => 'elsewhere-test',
            'name_ar' => 'تحليل خارج المدينة',
            'name_en' => 'Elsewhere',
            'category' => LabTestCategory::Blood,
            'sample_type' => SampleType::Blood,
            'suggested_price' => 90,
            'is_active' => true,
        ]);

        $inactive = LabTest::query()->create([
            'slug' => 'inactive-offering',
            'name_ar' => 'تحليل غير مفعل',
            'name_en' => 'Inactive offering',
            'category' => LabTestCategory::Blood,
            'sample_type' => SampleType::Blood,
            'suggested_price' => 70,
            'is_active' => true,
        ]);

        ClinicLabOffering::query()->create([
            'clinic_id' => $provider['clinic']->id,
            'item_type' => 'test',
            'lab_test_id' => $offered->id,
            'price' => 180,
            'is_active' => true,
        ]);

        ClinicLabOffering::query()->create([
            'clinic_id' => $provider['clinic']->id,
            'item_type' => 'package',
            'lab_package_id' => $package->id,
            'price' => 890,
            'is_active' => true,
        ]);

        ClinicLabOffering::query()->create([
            'clinic_id' => $provider['clinic']->id,
            'item_type' => 'test',
            'lab_test_id' => $inactive->id,
            'price' => 70,
            'is_active' => false,
        ]);

        $this->get('/labs?q=&governorate=&city='.$provider['city']->slug.'&max_price=')
            ->assertOk()
            ->assertSee('صورة دم كاملة')
            ->assertSee('فحص شامل')
            ->assertDontSee('تحليل خارج المدينة')
            ->assertDontSee('تحليل غير مفعل');

        $this->get('/labs')
            ->assertOk()
            ->assertSee('تحليل خارج المدينة')
            ->assertSee('تحليل غير مفعل');
    }

    public function test_cart_page_escapes_lab_test_names(): void
    {
        $test = LabTest::query()->create([
            'slug' => 'xss-cbc',
            'name_ar' => '<script>alert(1)</script>',
            'name_en' => '<script>alert(1)</script>',
            'category' => LabTestCategory::Blood,
            'sample_type' => SampleType::Blood,
            'suggested_price' => 10,
            'is_active' => true,
        ]);

        $this->withSession([LabCart::SESSION_KEY => [['type' => 'test', 'id' => $test->id, 'qty' => 1]]])
            ->get('/labs/cart')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_admin_creates_a_lab_test(): void
    {
        Storage::fake('public');

        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->post('/admin/lab-tests', [
            'name_ar' => 'سكر صائم',
            'name_en' => 'Fasting Glucose',
            'category' => LabTestCategory::Diabetes->value,
            'sample_type' => SampleType::Blood->value,
            'suggested_price' => 90,
            'fasting_hours' => 8,
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('glucose.jpg', 80, 80),
        ])->assertRedirect('/admin/lab-tests');

        $test = LabTest::query()->where('name_en', 'Fasting Glucose')->first();

        $this->assertNotNull($test);
        $this->assertNotNull($test->image_path);
        Storage::disk('public')->assertExists($test->image_path);
    }

    public function test_admin_rejects_a_lab_test_without_an_image(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->from('/admin/lab-tests/create')
            ->post('/admin/lab-tests', [
                'name_ar' => 'سكر صائم',
                'name_en' => 'Fasting Glucose',
                'category' => LabTestCategory::Diabetes->value,
                'sample_type' => SampleType::Blood->value,
                'suggested_price' => 90,
                'is_active' => '1',
            ])
            ->assertRedirect('/admin/lab-tests/create')
            ->assertSessionHasErrors('image');

        $this->assertSame(0, LabTest::query()->count());
    }

    /**
     * @return array{0: LabTest, 1: LabPackage}
     */
    private function seedCatalog(): array
    {
        $cbc = LabTest::query()->create([
            'slug' => 'cbc',
            'name_ar' => 'صورة دم كاملة (CBC)',
            'name_en' => 'Complete Blood Count (CBC)',
            'category' => LabTestCategory::Blood,
            'sample_type' => SampleType::Blood,
            'suggested_price' => 180,
            'measures_ar' => 'الهيموجلوبين، الهيماتوكريت',
            'measures_en' => 'Haemoglobin, haematocrit',
            'is_active' => true,
        ]);

        $lipid = LabTest::query()->create([
            'slug' => 'lipid',
            'name_ar' => 'دهون',
            'name_en' => 'Lipid',
            'category' => LabTestCategory::Lipids,
            'sample_type' => SampleType::Blood,
            'suggested_price' => 280,
            'is_active' => true,
        ]);

        $package = LabPackage::query()->create([
            'slug' => 'full-checkup',
            'name_ar' => 'فحص شامل',
            'name_en' => 'Full checkup',
            'includes_ar' => 'CBC ودهون',
            'includes_en' => 'CBC and lipids',
            'original_price' => 460,
            'package_price' => 890,
            'is_featured' => true,
            'is_active' => true,
        ]);

        $package->tests()->sync([$cbc->id, $lipid->id]);

        return [$cbc, $package];
    }
}
