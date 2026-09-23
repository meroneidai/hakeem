<?php

namespace Database\Seeders;

use App\Enums\BillingCycle;
use App\Enums\BookingStatus;
use App\Enums\ClinicModule;
use App\Enums\DayOfWeek;
use App\Enums\OfferCategory;
use App\Enums\PaymentMode;
use App\Enums\RoleName;
use App\Enums\ServiceTypeCode;
use App\Enums\VerificationStatus;
use App\Models\Booking;
use App\Models\City;
use App\Models\Clinic;
use App\Models\ClinicAddress;
use App\Models\ClinicLabOffering;
use App\Models\ClinicService;
use App\Models\Doctor;
use App\Models\DoctorAddressAvailability;
use App\Models\LabPackage;
use App\Models\LabTest;
use App\Models\Promotion;
use App\Models\ServiceType;
use App\Models\Specialty;
use App\Models\SubscriptionPlan;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\AddressScheduleWriter;
use App\Services\ClinicSubscriptionService;
use App\Services\SeoPageGenerator;
use App\Support\UniqueSlug;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Local-only marketplace volume: clinics, centers, labs, doctors, patients,
 * services, offers and bookings across Egypt so directories and queues can be
 * exercised end to end. Idempotent by clinic slug / user phone.
 */
class MarketplaceCatalogSeeder extends Seeder
{
    private int $ownerSeq = 20000;

    private int $doctorSeq = 20000;

    private int $patientSeq = 20000;

    private int $receptionSeq = 20000;

    /** @var array<string, City> */
    private array $cities = [];

    /** @var array<string, Specialty> */
    private array $specialties = [];

    /** @var array<string, ServiceType> */
    private array $serviceTypes = [];

    public function run(
        AddressScheduleWriter $schedules,
        ClinicSubscriptionService $subscriptions,
        SeoPageGenerator $seo,
    ): void {
        $this->cities = City::query()->get()->keyBy('slug')->all();
        $this->specialties = Specialty::query()->get()->keyBy('slug')->all();
        $this->serviceTypes = ServiceType::query()->get()->keyBy('code')->all();

        if ($this->cities === [] || $this->specialties === [] || $this->serviceTypes === []) {
            return;
        }

        $this->enrichAlNoor($schedules);
        $this->seedFlagships($schedules, $subscriptions);
        $this->seedSpecialtyClinics($schedules, $subscriptions);
        $patients = $this->seedPatients();
        $this->seedBookings($patients);
        $this->seedReviews();
        $this->seedExtraSupportTickets($patients);
        $seo->generateMissing();
    }

    private function enrichAlNoor(AddressScheduleWriter $schedules): void
    {
        $clinic = Clinic::query()->where('email', 'clinic@hakeem.test')->first();

        if (! $clinic) {
            return;
        }

        $clinic->update([
            'description_ar' => 'عيادة النور للطب العام في مدينة نصر — كشف ومتابعة وزيارات منزلية واستشارة فيديو. حساب التجربة الرسمي للمنصة.',
            'description_en' => 'Al Noor general-practice clinic in Nasr City — visits, home calls and video consults. The official demo tenant.',
            'verification_status' => VerificationStatus::Verified,
            'verified_at' => $clinic->verified_at ?? now(),
            'is_active' => true,
            'modules' => ClinicModule::normalize([
                ClinicModule::Labs,
                ClinicModule::Promotions,
                ClinicModule::PhysicalTherapy,
                ClinicModule::Dental,
                ClinicModule::Cosmetic,
                ClinicModule::Massage,
            ]),
        ]);

        $address = $clinic->addresses()->orderByDesc('is_primary')->first();

        if ($address) {
            $pin = $this->location('nasr-city');
            $address->update([
                'address_line' => $pin['street'].'، الدور الأرضي',
                'landmark' => 'بجوار مدينة نصر للتأمين',
                'latitude' => $pin['lat'],
                'longitude' => $pin['lng'],
                'is_active' => true,
            ]);
            $schedules->seedDefaults($address);
        }

        $this->enableServices($clinic, [
            ServiceTypeCode::ClinicAppointment,
            ServiceTypeCode::HomeVisit,
            ServiceTypeCode::VideoConsultation,
            ServiceTypeCode::LabTest,
            ServiceTypeCode::HomeLabTest,
        ], $clinic->doctors()->first()?->specialty_id, 300);
        $this->seedLabOfferings($clinic, true);
    }

    private function seedFlagships(AddressScheduleWriter $schedules, ClinicSubscriptionService $subscriptions): void
    {
        foreach ($this->flagships() as $provider) {
            $this->seedProvider($provider, $schedules, $subscriptions);
        }
    }

    private function seedSpecialtyClinics(AddressScheduleWriter $schedules, ClinicSubscriptionService $subscriptions): void
    {
        $citySlugs = array_keys($this->locations());
        $people = $this->doctorNames();

        foreach (array_values($this->specialties) as $index => $specialty) {
            $alreadyListed = Doctor::query()
                ->where('specialty_id', $specialty->id)
                ->where('is_active', true)
                ->whereHas('clinics', fn ($query) => $query->listable())
                ->exists();

            if ($alreadyListed) {
                continue;
            }

            $citySlug = $citySlugs[$index % count($citySlugs)];
            $city = $this->cities[$citySlug] ?? null;
            $person = $people[$index % count($people)];
            $pin = $this->location($citySlug);

            if (! $city) {
                continue;
            }

            $this->seedProvider([
                'slug' => $specialty->slug.'-clinic',
                'name_ar' => 'عيادة '.$specialty->name_ar,
                'name_en' => $specialty->name_en.' Clinic',
                'city' => $citySlug,
                'address' => $pin['street'].'، الدور 1',
                'landmark' => null,
                'lat' => $pin['lat'],
                'lng' => $pin['lng'],
                'single' => true,
                'specialty' => $specialty->slug,
                'description_ar' => 'عيادة '.$specialty->name_ar.' تقدّم كشفًا ومتابعة في '.$city->name_ar.' مع مواعيد واضحة وخدمات مكملة حسب التخصص.',
                'description_en' => $specialty->name_en.' clinic in '.$city->name_en.' with booked slots and matching add-on services.',
                'hours' => 'default',
                'services' => $this->defaultServicesFor($specialty->slug),
                'lab' => $specialty->slug === 'medical-laboratory',
                'doctors' => [[
                    'name_ar' => $person[0],
                    'name_en' => $person[1],
                    'specialty' => $specialty->slug,
                    'gender' => $person[2],
                    'years' => 8 + ($index % 22),
                    'fee' => $this->feeFor($specialty->slug),
                    'credentials' => 'استشاري · نقابة الأطباء المصرية',
                    'bio_ar' => 'استشاري '.$specialty->name_ar.' بخبرة عملية في العيادات المصرية، يهتم بالشرح الواضح وخطة المتابعة.',
                    'bio_en' => 'Consultant in '.$specialty->name_en.' with clinic experience in Egypt, focused on clear follow-up plans.',
                ]],
            ], $schedules, $subscriptions);
        }
    }

    /**
     * @param  array<string, mixed>  $provider
     */
    private function seedProvider(
        array $provider,
        AddressScheduleWriter $schedules,
        ClinicSubscriptionService $subscriptions,
    ): void {
        $city = $this->cities[$provider['city']] ?? null;
        $specialty = $this->specialties[$provider['specialty']] ?? null;

        if (! $city || ! $specialty) {
            return;
        }

        $status = $provider['verification'] ?? VerificationStatus::Verified;
        $email = $provider['email'] ?? $provider['slug'].'@hakeem.test';
        $existingClinic = Clinic::query()->where('slug', $provider['slug'])->first();
        $phone = $existingClinic?->owner?->phone
            ?? User::normalizePhone($provider['phone'] ?? $this->uniquePhone('010555', $this->ownerSeq));
        $single = (bool) ($provider['single'] ?? true);
        $ownerName = $single
            ? ($provider['doctors'][0]['name_ar'] ?? $provider['name_ar'])
            : 'مالك '.$provider['name_ar'];

        $owner = User::updateOrCreate(
            ['phone' => $phone],
            [
                'name' => $ownerName,
                'email' => $email,
                'password' => 'password',
                'preferred_language' => 'ar',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $clinic = Clinic::updateOrCreate(
            ['slug' => $provider['slug']],
            [
                'owner_user_id' => $owner->id,
                'name_ar' => $provider['name_ar'],
                'name_en' => $provider['name_en'],
                'description_ar' => $provider['description_ar'],
                'description_en' => $provider['description_en'],
                'email' => $email,
                'phone' => $phone,
                'is_single_doctor' => $single,
                'verification_status' => $status,
                'rejection_reason' => $status === VerificationStatus::Rejected
                    ? 'بيانات الرخصة غير مكتملة في هذه العيادة التجريبية.'
                    : null,
                'verified_at' => $status === VerificationStatus::Verified ? now() : null,
                'is_active' => $provider['active'] ?? true,
                'payment_modes' => $provider['payment_modes'] ?? [PaymentMode::AtClinic->value, PaymentMode::AfterService->value],
                'default_payment_mode' => $provider['default_payment_mode'] ?? PaymentMode::AtClinic->value,
            ],
        );

        $owner->assignRole(RoleName::ClinicOwner, $clinic->id);

        if (! $clinic->subscriptions()->where('status', 'active')->exists()) {
            $planSlug = $provider['plan'] ?? 'starter';
            $plan = SubscriptionPlan::query()->where('slug', $planSlug)->first()
                ?? SubscriptionPlan::query()->where('is_default_free', true)->firstOrFail();
            $subscriptions->subscribe($clinic, $plan, BillingCycle::Monthly);
        }

        $address = $this->seedAddress($clinic, $city, $provider, $schedules, primary: true);

        foreach ($provider['branches'] ?? [] as $branch) {
            $this->seedAddress($clinic, $this->cities[$branch['city']] ?? $city, $branch, $schedules, primary: false);
        }

        $addresses = $clinic->addresses()->orderByDesc('is_primary')->get();

        foreach ($provider['doctors'] as $index => $doctorData) {
            $doctorUser = $single && $index === 0
                ? $owner
                : $this->makeDoctorUser($provider['slug'], $index, $doctorData);

            $this->attachDoctor($clinic, $doctorUser, $doctorData, $addresses);
        }

        if (! $single) {
            $this->makeReception($clinic, $provider['slug']);
        }

        $this->enableServices(
            $clinic,
            $provider['services'] ?? [ServiceTypeCode::ClinicAppointment],
            $specialty->id,
            (int) ($provider['doctors'][0]['fee'] ?? $this->feeFor($provider['specialty'])),
            extraSpecialties: collect($provider['doctors'])
                ->pluck('specialty')
                ->unique()
                ->values()
                ->all(),
        );

        if ($provider['lab'] ?? false) {
            $this->seedLabOfferings($clinic, (bool) ($provider['lab_home'] ?? true));
        }

        foreach ($provider['offers'] ?? [] as $offer) {
            $this->seedOffer($clinic, $offer);
        }

        $this->syncModules($clinic, $provider, $specialty);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function seedAddress(
        Clinic $clinic,
        City $city,
        array $data,
        AddressScheduleWriter $schedules,
        bool $primary,
    ): ClinicAddress {
        $address = $clinic->addresses()->updateOrCreate(
            [
                'clinic_id' => $clinic->id,
                'address_line' => $data['address'],
            ],
            [
                'city_id' => $city->id,
                'label_ar' => $data['label_ar'] ?? ($primary ? ($clinic->is_single_doctor ? 'العيادة' : 'الفرع الرئيسي') : 'فرع'),
                'label_en' => $data['label_en'] ?? ($primary ? ($clinic->is_single_doctor ? 'Clinic' : 'Main branch') : 'Branch'),
                'landmark' => $data['landmark'] ?? null,
                'phone' => $clinic->phone,
                'latitude' => $data['lat'] ?? null,
                'longitude' => $data['lng'] ?? null,
                'is_primary' => $primary,
                'is_active' => true,
            ],
        );

        $this->writeHours($schedules, $address, $data['hours'] ?? 'default');

        return $address;
    }

    private function writeHours(AddressScheduleWriter $schedules, ClinicAddress $address, string $pattern): void
    {
        $days = [];

        foreach (DayOfWeek::weekOrder() as $day) {
            $open = match ($pattern) {
                'extended' => true,
                'evening' => in_array($day, DayOfWeek::defaultWorkingDays(), true),
                'lab' => true,
                default => in_array($day, DayOfWeek::defaultWorkingDays(), true),
            };

            $days[$day->value] = match ($pattern) {
                'extended' => [
                    'open' => $open,
                    'open_time' => $day === DayOfWeek::Friday ? '10:00' : '09:00',
                    'close_time' => $day === DayOfWeek::Friday ? '14:00' : '21:00',
                    'slot_duration_minutes' => 20,
                ],
                'evening' => [
                    'open' => $open,
                    'open_time' => '14:00',
                    'close_time' => '22:00',
                    'slot_duration_minutes' => 20,
                ],
                'lab' => [
                    'open' => true,
                    'open_time' => '08:00',
                    'close_time' => '22:00',
                    'slot_duration_minutes' => 15,
                ],
                default => [
                    'open' => $open,
                    'open_time' => '09:00',
                    'close_time' => '17:00',
                    'slot_duration_minutes' => 30,
                ],
            };
        }

        $schedules->sync($address, $days);
    }

    /**
     * @param  array<string, mixed>  $doctorData
     */
    private function makeDoctorUser(string $clinicSlug, int $index, array $doctorData): User
    {
        $email = 'doctor.'.$clinicSlug.'.'.$index.'@hakeem.test';
        $existing = User::query()->where('email', $email)->first();

        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $doctorData['name_ar'],
                'phone' => $existing?->phone ?? $this->uniquePhone('010556', $this->doctorSeq),
                'password' => 'password',
                'preferred_language' => 'ar',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $doctorData
     * @param  Collection<int, ClinicAddress>  $addresses
     */
    private function attachDoctor(Clinic $clinic, User $user, array $doctorData, $addresses): void
    {
        $specialty = $this->specialties[$doctorData['specialty']] ?? $clinic->doctors()->first()?->specialty;

        if (! $specialty) {
            return;
        }

        $doctor = Doctor::query()->where('user_id', $user->id)->first();

        $payload = [
            'name_ar' => $doctorData['name_ar'],
            'name_en' => $doctorData['name_en'],
            'specialty_id' => $specialty->id,
            'bio_ar' => $doctorData['bio_ar'] ?? null,
            'bio_en' => $doctorData['bio_en'] ?? null,
            'credentials' => $doctorData['credentials'] ?? 'استشاري · نقابة الأطباء المصرية',
            'years_of_experience' => $doctorData['years'] ?? 10,
            'gender' => $doctorData['gender'] ?? 'male',
            'consultation_fee' => $doctorData['fee'] ?? 300,
            'is_active' => true,
        ];

        if ($doctor) {
            $doctor->update($payload);
        } else {
            $doctor = Doctor::create([
                ...$payload,
                'user_id' => $user->id,
                'slug' => UniqueSlug::for($doctorData['name_en'], 'doctors'),
            ]);
        }

        $clinic->doctors()->syncWithoutDetaching([$doctor->id]);
        $user->assignRole(RoleName::Doctor, $clinic->id);

        foreach ($addresses as $address) {
            foreach ($address->schedules()->where('is_closed', false)->get() as $schedule) {
                DoctorAddressAvailability::query()->updateOrCreate(
                    [
                        'doctor_id' => $doctor->id,
                        'clinic_address_id' => $address->id,
                        'day_of_week' => $schedule->day_of_week,
                        'open_time' => $schedule->open_time,
                    ],
                    ['close_time' => $schedule->close_time],
                );
            }
        }
    }

    private function makeReception(Clinic $clinic, string $slug): void
    {
        $email = 'reception.'.$slug.'@hakeem.test';
        $existing = User::query()->where('email', $email)->first();

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'استقبال '.$clinic->name_ar,
                'phone' => $existing?->phone ?? $this->uniquePhone('010558', $this->receptionSeq),
                'password' => 'password',
                'preferred_language' => 'ar',
                'is_active' => true,
            ],
        );

        $user->assignRole(RoleName::Reception, $clinic->id);
    }

    /**
     * @param  list<ServiceTypeCode|string>  $codes
     * @param  list<string>  $extraSpecialties
     */
    private function enableServices(
        Clinic $clinic,
        array $codes,
        ?int $primarySpecialtyId,
        int $baseFee,
        array $extraSpecialties = [],
    ): void {
        $specialtyIds = collect($extraSpecialties)
            ->map(fn (string $slug) => ($this->specialties[$slug] ?? null)?->id)
            ->filter()
            ->push($primarySpecialtyId)
            ->filter()
            ->unique()
            ->values();

        foreach ($codes as $code) {
            $enum = $code instanceof ServiceTypeCode ? $code : ServiceTypeCode::from($code);
            $type = $this->serviceTypes[$enum->value] ?? null;

            if (! $type) {
                continue;
            }

            $targets = $enum === ServiceTypeCode::ClinicAppointment
                ? $specialtyIds
                : collect([$primarySpecialtyId])->filter();

            if ($targets->isEmpty()) {
                $targets = collect([null]);
            }

            foreach ($targets as $specialtyId) {
                $pricing = $this->servicePricing($enum, $baseFee);

                ClinicService::query()->updateOrCreate(
                    [
                        'clinic_id' => $clinic->id,
                        'service_type_id' => $type->id,
                        'specialty_id' => $specialtyId,
                    ],
                    [
                        'price' => $pricing['price'],
                        'promo_price' => $pricing['promo'],
                        'duration_minutes' => $pricing['duration'],
                        'session_count' => $pricing['sessions'],
                        'is_active' => true,
                        'requires_evaluation_first' => $enum === ServiceTypeCode::PsychiatricConsultation || $enum->isRehab(),
                    ],
                );
            }
        }
    }

    /**
     * @return array{price: int, promo: int|null, duration: int, sessions: int}
     */
    private function servicePricing(ServiceTypeCode $code, int $baseFee): array
    {
        return match ($code) {
            ServiceTypeCode::ClinicAppointment => ['price' => $baseFee, 'promo' => null, 'duration' => 20, 'sessions' => 1],
            ServiceTypeCode::HomeVisit => ['price' => $baseFee + 350, 'promo' => $baseFee + 280, 'duration' => 40, 'sessions' => 1],
            ServiceTypeCode::VideoConsultation => ['price' => max(150, $baseFee - 80), 'promo' => null, 'duration' => 15, 'sessions' => 1],
            ServiceTypeCode::LabTest => ['price' => 0, 'promo' => null, 'duration' => 15, 'sessions' => 1],
            ServiceTypeCode::HomeLabTest => ['price' => 150, 'promo' => 120, 'duration' => 20, 'sessions' => 1],
            ServiceTypeCode::PsychiatricConsultation => ['price' => max(450, $baseFee), 'promo' => null, 'duration' => 50, 'sessions' => 1],
            ServiceTypeCode::PhysicalTherapy => ['price' => max(250, $baseFee), 'promo' => null, 'duration' => 45, 'sessions' => 8],
            ServiceTypeCode::OccupationalTherapy => ['price' => max(250, $baseFee), 'promo' => null, 'duration' => 45, 'sessions' => 8],
        };
    }

    private function seedLabOfferings(Clinic $clinic, bool $home): void
    {
        foreach (LabTest::query()->active()->ordered()->get() as $index => $test) {
            $price = (int) $test->suggested_price;
            $promo = $index % 4 === 0 ? max(50, (int) round($price * 0.75)) : null;

            ClinicLabOffering::query()->updateOrCreate(
                [
                    'clinic_id' => $clinic->id,
                    'lab_test_id' => $test->id,
                ],
                [
                    'item_type' => 'test',
                    'lab_package_id' => null,
                    'price' => $price,
                    'promo_price' => $promo,
                    'allows_home_collection' => $home,
                    'is_active' => true,
                ],
            );
        }

        foreach (LabPackage::query()->active()->ordered()->get() as $package) {
            ClinicLabOffering::query()->updateOrCreate(
                [
                    'clinic_id' => $clinic->id,
                    'lab_package_id' => $package->id,
                ],
                [
                    'item_type' => 'package',
                    'lab_test_id' => null,
                    'price' => $package->package_price,
                    'promo_price' => $package->is_featured ? max(100, (int) round((float) $package->package_price * 0.9)) : null,
                    'allows_home_collection' => $home,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $offer
     */
    private function seedOffer(Clinic $clinic, array $offer): void
    {
        $service = isset($offer['service'])
            ? ($this->serviceTypes[$offer['service']] ?? null)
            : null;
        $specialty = isset($offer['specialty'])
            ? ($this->specialties[$offer['specialty']] ?? null)
            : null;

        Promotion::query()->updateOrCreate(
            ['slug' => $offer['slug']],
            [
                'clinic_id' => $clinic->id,
                'title_ar' => $offer['title_ar'],
                'title_en' => $offer['title_en'],
                'category' => $offer['category'],
                'description_ar' => $offer['description_ar'],
                'description_en' => $offer['description_en'],
                'includes_ar' => $offer['includes_ar'] ?? null,
                'includes_en' => $offer['includes_en'] ?? null,
                'conditions_ar' => $offer['conditions_ar'] ?? 'ساري في الفروع المشاركة حتى تاريخ الانتهاء. غير قابل للجمع مع عرض آخر.',
                'conditions_en' => $offer['conditions_en'] ?? 'Valid at participating branches until expiry. Not combinable with another offer.',
                'discount_type' => $offer['discount_type'] ?? 'percentage',
                'discount_value' => $offer['discount_value'] ?? 20,
                'original_price' => $offer['original_price'] ?? null,
                'offer_price' => $offer['offer_price'] ?? null,
                'session_count' => $offer['session_count'] ?? 1,
                'specialty_id' => $specialty?->id,
                'service_type_id' => $service?->id,
                'starts_at' => now()->subDays(2),
                'ends_at' => now()->addDays(40),
                'is_featured' => (bool) ($offer['featured'] ?? false),
                'is_active' => true,
                'created_by_user_id' => $clinic->owner_user_id,
            ],
        );
    }

    /**
     * @return list<User>
     */
    private function seedPatients(): array
    {
        $named = [
            ['منى إبراهيم', '01011112222'],
            ['أحمد محمود', '01033334444'],
            ['نورا حسن', '01044445555'],
            ['كريم فؤاد', '01066667777'],
            ['سلمى عادل', '01077778888'],
            ['يوسف علي', '01088889999'],
            ['هدى سعيد', '01099990001'],
            ['محمود جمال', '01099990002'],
            ['فاطمة زكي', '01099990003'],
            ['علي رضا', '01099990004'],
            ['مريم شريف', '01099990005'],
            ['حسام نبيل', '01099990006'],
            ['دينا عمر', '01099990007'],
            ['طارق سامي', '01099990008'],
            ['إيمان جلال', '01099990009'],
            ['وليد حمدي', '01099990010'],
        ];

        $patients = [];

        foreach ($named as $index => [$name, $phone]) {
            $patients[] = $this->makePatient($name, $phone, 'patient.'.$index.'@hakeem.test');
        }

        $generated = [
            'شريف عادل', 'لينا مجدي', 'باسم فوزي', 'غادة أنور', 'رامي صلاح',
            'نهى كمال', 'عمرو هاني', 'سمر توفيق', 'حنان رضا', 'زياد منصور',
            'ميادة نبيل', 'أيمن لطفي', 'ياسمين فريد', 'نادر حسني', 'ريهام عطية',
        ];

        foreach ($generated as $index => $name) {
            $patients[] = $this->makePatient(
                $name,
                $this->uniquePhone('010557', $this->patientSeq),
                'patient.gen.'.$index.'@hakeem.test',
            );
        }

        return $patients;
    }

    private function makePatient(string $name, string $phone, string $email): User
    {
        $existing = User::query()->where('email', $email)->first();

        $user = User::updateOrCreate(
            ['phone' => $existing?->phone ?? User::normalizePhone($phone)],
            [
                'name' => $name,
                'email' => $email,
                'password' => 'password',
                'preferred_language' => 'ar',
                'is_active' => true,
            ],
        );

        $user->assignRole(RoleName::Patient);

        return $user;
    }

    /**
     * @param  list<User>  $patients
     */
    private function seedBookings(array $patients): void
    {
        if ($patients === []) {
            return;
        }

        $clinics = Clinic::query()
            ->listable()
            ->with(['doctors', 'addresses', 'services.serviceType'])
            ->get();

        $statuses = [
            BookingStatus::Pending,
            BookingStatus::Confirmed,
            BookingStatus::InProgress,
            BookingStatus::Completed,
            BookingStatus::Cancelled,
            BookingStatus::NoShow,
        ];

        foreach ($clinics as $clinicIndex => $clinic) {
            $doctor = $clinic->doctors->first();
            $address = $clinic->addresses->first();
            $appointment = $clinic->services->first(
                fn (ClinicService $service) => $service->serviceType?->code === ServiceTypeCode::ClinicAppointment->value,
            ) ?? $clinic->services->first();

            if (! $doctor || ! $address || ! $appointment) {
                continue;
            }

            $homeService = $clinic->services->first(
                fn (ClinicService $service) => $service->serviceType?->code === ServiceTypeCode::HomeVisit->value,
            );
            $videoService = $clinic->services->first(
                fn (ClinicService $service) => $service->serviceType?->code === ServiceTypeCode::VideoConsultation->value,
            );
            $labService = $clinic->services->first(
                fn (ClinicService $service) => $service->serviceType?->code === ServiceTypeCode::LabTest->value,
            );

            for ($slot = 0; $slot < 6; $slot++) {
                $patient = $patients[($clinicIndex + $slot) % count($patients)];
                $status = $statuses[$slot % count($statuses)];
                $when = $this->slotTime($status, $slot, $clinicIndex);
                $service = $appointment;
                $homeAddress = null;

                if ($slot === 3 && $homeService) {
                    $service = $homeService;
                    $homeAddress = 'شقة 12، شارع النيل، الدور 3';
                } elseif ($slot === 4 && $videoService) {
                    $service = $videoService;
                } elseif ($slot === 5 && $labService) {
                    $service = $labService;
                }

                $paid = in_array($status, [BookingStatus::Completed, BookingStatus::InProgress], true);
                $mode = $slot % 2 === 0 ? PaymentMode::AtClinic : PaymentMode::AfterService;
                $isEvaluation = $doctor->specialty?->category === 'physical_therapy'
                    || $service->serviceType?->code === ServiceTypeCode::PsychiatricConsultation->value;

                $booking = Booking::query()->firstOrCreate(
                    [
                        'patient_id' => $patient->id,
                        'clinic_id' => $clinic->id,
                        'scheduled_at' => $when,
                    ],
                    [
                        'doctor_id' => $doctor->id,
                        'clinic_address_id' => $service->serviceType?->requires_clinic_address ? $address->id : null,
                        'service_type_id' => $service->service_type_id,
                        'status' => $status,
                        'is_evaluation' => $isEvaluation && $slot === 0,
                        'payment_mode' => $mode,
                        'payment_status' => $paid ? 'paid' : 'unpaid',
                        'patient_home_address' => $homeAddress,
                        'notes' => $slot === 0 ? 'حجز تجريبي للمتابعة من التطبيق.' : null,
                    ],
                );

                if ($booking->statusHistory()->doesntExist()) {
                    $this->writeHistory($booking, $patient, $status);
                }
            }
        }
    }

    private function seedReviews(): void
    {
        $completed = Booking::query()
            ->where('status', BookingStatus::Completed)
            ->whereDoesntHave('review')
            ->with(['patient', 'doctor', 'clinic'])
            ->get();

        foreach ($completed as $index => $booking) {
            if (! $booking->patient_id || ! $booking->doctor_id || ! $booking->clinic_id) {
                continue;
            }

            $booking->review()->create([
                'patient_id' => $booking->patient_id,
                'doctor_id' => $booking->doctor_id,
                'clinic_id' => $booking->clinic_id,
                'overall' => 4 + ($index % 2),
                'wait_time' => 4,
                'staff' => 5,
                'cleanliness' => 4,
                'body' => $index % 2 === 0 ? 'زيارة منظمة والكشف واضح.' : null,
                'is_visible' => true,
            ]);
        }
    }

    private function slotTime(BookingStatus $status, int $slot, int $clinicIndex): CarbonInterface
    {
        $hour = 8 + (($slot + $clinicIndex) % 8);

        return match ($status) {
            BookingStatus::Completed, BookingStatus::Cancelled, BookingStatus::NoShow => now()->subDays(2 + ($slot % 5))->setTime($hour, 0),
            BookingStatus::InProgress => now()->setTime(max(9, now()->hour), 0),
            BookingStatus::Confirmed => now()->setTime($hour, 30),
            BookingStatus::Pending => now()->addDays(1 + ($slot % 4))->setTime($hour, 0),
        };
    }

    private function writeHistory(Booking $booking, User $patient, BookingStatus $status): void
    {
        $started = $booking->scheduled_at?->copy()->subDays(3) ?? now()->subDays(3);

        $booking->statusHistory()->create([
            'status' => BookingStatus::Pending,
            'changed_by_user_id' => $patient->id,
            'changed_at' => $started,
        ]);

        if ($status === BookingStatus::Pending) {
            return;
        }

        $booking->statusHistory()->create([
            'status' => $status === BookingStatus::Cancelled ? BookingStatus::Cancelled : BookingStatus::Confirmed,
            'changed_by_user_id' => $booking->clinic?->owner_user_id,
            'changed_at' => $started->copy()->addHours(2),
        ]);

        if (in_array($status, [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Cancelled], true)) {
            return;
        }

        $booking->statusHistory()->create([
            'status' => $status,
            'changed_by_user_id' => $booking->clinic?->owner_user_id,
            'changed_at' => $started->copy()->addHours(6),
        ]);
    }

    /**
     * @param  list<User>  $patients
     */
    private function seedExtraSupportTickets(array $patients): void
    {
        $agent = User::query()->where('email', 'support@hakeem.test')->first();
        $extra = [
            [
                'subject' => 'تعديل موعد الزيارة المنزلية',
                'category' => 'booking',
                'channel' => 'phone',
                'priority' => 'normal',
                'status' => 'waiting_on_customer',
                'message' => 'أحتاج تأجيل الزيارة المنزلية ليوم الخميس بعد العصر.',
            ],
            [
                'subject' => 'نتيجة التحليل لم تظهر في السجل',
                'category' => 'technical',
                'channel' => 'email',
                'priority' => 'high',
                'status' => 'open',
                'message' => 'عملت تحليل أمس في المعمل والنتيجة لم تُضف إلى حسابي بعد.',
            ],
            [
                'subject' => 'شكوى من انتظار طويل في العيادة',
                'category' => 'complaint',
                'channel' => 'chat',
                'priority' => 'high',
                'status' => 'open',
                'message' => 'انتظرت أكثر من ساعة بعد الموعد المؤكد دون اعتذار.',
            ],
            [
                'subject' => 'الاستشارة النفسية باسم عرض',
                'category' => 'other',
                'channel' => 'chat',
                'priority' => 'low',
                'status' => 'resolved',
                'message' => 'هل يمكن حجز الجلسة النفسية بدون ظهور اسمي للعيادة الأخرى؟',
            ],
        ];

        foreach ($extra as $index => $data) {
            $patient = $patients[($index + 1) % count($patients)];
            $ticket = SupportTicket::query()->firstOrCreate(
                ['subject' => $data['subject']],
                [
                    'opened_by_user_id' => $patient->id,
                    'channel' => $data['channel'],
                    'category' => $data['category'],
                    'priority' => $data['priority'],
                    'status' => $data['status'],
                    'assigned_agent_id' => $agent?->id,
                    'first_response_at' => $data['status'] === 'open' ? null : now()->subHours(5),
                    'resolved_at' => $data['status'] === 'resolved' ? now()->subHour() : null,
                ],
            );

            if ($ticket->messages()->doesntExist()) {
                $ticket->messages()->create([
                    'sender_user_id' => $patient->id,
                    'body' => $data['message'],
                    'sent_at' => now()->subHours(8),
                ]);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function defaultServicesFor(string $specialtySlug): array
    {
        return match ($specialtySlug) {
            'medical-laboratory' => [
                ServiceTypeCode::LabTest->value,
                ServiceTypeCode::HomeLabTest->value,
            ],
            'psychiatry', 'psychotherapy' => [
                ServiceTypeCode::ClinicAppointment->value,
                ServiceTypeCode::VideoConsultation->value,
                ServiceTypeCode::PsychiatricConsultation->value,
            ],
            'general-practice', 'internal-medicine', 'pediatrics' => [
                ServiceTypeCode::ClinicAppointment->value,
                ServiceTypeCode::HomeVisit->value,
                ServiceTypeCode::VideoConsultation->value,
            ],
            'physical-therapy', 'sports-rehabilitation', 'occupational-therapy' => [
                ServiceTypeCode::ClinicAppointment->value,
                ServiceTypeCode::PhysicalTherapy->value,
                ServiceTypeCode::OccupationalTherapy->value,
                ServiceTypeCode::HomeVisit->value,
            ],
            default => [
                ServiceTypeCode::ClinicAppointment->value,
                ServiceTypeCode::VideoConsultation->value,
            ],
        };
    }

    private function feeFor(string $specialtySlug): int
    {
        $specialty = $this->specialties[$specialtySlug] ?? null;
        $category = $specialty?->category;

        return match ($category) {
            'dental' => 450,
            'cosmetic' => 900,
            'beauty' => 350,
            'physical_therapy' => 300,
            'psychiatry' => 600,
            'laboratory' => 0,
            default => 350,
        };
    }

    /**
     * @return array{lat: float, lng: float, street: string}
     */
    private function location(string $citySlug): array
    {
        return $this->locations()[$citySlug] ?? ['lat' => 30.0444, 'lng' => 31.2357, 'street' => 'شارع رئيسي'];
    }

    /**
     * @return array<string, array{lat: float, lng: float, street: string}>
     */
    private function locations(): array
    {
        return [
            'nasr-city' => ['lat' => 30.0566, 'lng' => 31.3289, 'street' => 'شارع عباس العقاد، مدينة نصر'],
            'heliopolis' => ['lat' => 30.0912, 'lng' => 31.3254, 'street' => 'شارع إبراهيم اللقاني، مصر الجديدة'],
            'maadi' => ['lat' => 29.9602, 'lng' => 31.2569, 'street' => 'شارع 9، المعادي'],
            'zamalek' => ['lat' => 30.0619, 'lng' => 31.2197, 'street' => 'شارع 26 يوليو، الزمالك'],
            'downtown-cairo' => ['lat' => 30.0444, 'lng' => 31.2357, 'street' => 'شارع طلعت حرب، وسط البلد'],
            'new-cairo' => ['lat' => 30.0274, 'lng' => 31.4913, 'street' => 'التسعين الشمالي، التجمع الخامس'],
            'mokattam' => ['lat' => 30.0045, 'lng' => 31.3001, 'street' => 'شارع 9، المقطم'],
            'dokki' => ['lat' => 30.0389, 'lng' => 31.2118, 'street' => 'شارع التحرير، الدقي'],
            'mohandessin' => ['lat' => 30.0571, 'lng' => 31.2001, 'street' => 'شارع جامعة الدول العربية، المهندسين'],
            'haram' => ['lat' => 29.9935, 'lng' => 31.1481, 'street' => 'شارع الهرم'],
            'sixth-of-october' => ['lat' => 29.9285, 'lng' => 30.9188, 'street' => 'المحور المركزي، 6 أكتوبر'],
            'sheikh-zayed' => ['lat' => 30.0490, 'lng' => 30.9760, 'street' => 'الحي 12، الشيخ زايد'],
            'smouha' => ['lat' => 31.2165, 'lng' => 29.9425, 'street' => 'شارع فوزي معاذ، سموحة'],
            'sidi-gaber' => ['lat' => 31.2180, 'lng' => 29.9320, 'street' => 'شارع بورسعيد، سيدي جابر'],
            'mansoura' => ['lat' => 31.0409, 'lng' => 31.3785, 'street' => 'شارع الجمهورية، المنصورة'],
            'tanta' => ['lat' => 30.7865, 'lng' => 31.0004, 'street' => 'شارع البحر، طنطا'],
            'zagazig' => ['lat' => 30.5877, 'lng' => 31.5020, 'street' => 'شارع القومية، الزقازيق'],
            'asyut-city' => ['lat' => 27.1809, 'lng' => 31.1837, 'street' => 'شارع الجمهورية، أسيوط'],
            'aswan-city' => ['lat' => 24.0889, 'lng' => 32.8998, 'street' => 'كورنيش النيل، أسوان'],
            'luxor-city' => ['lat' => 25.6872, 'lng' => 32.6396, 'street' => 'الكورنيش، الأقصر'],
            'hurghada' => ['lat' => 27.2579, 'lng' => 33.8116, 'street' => 'شارع شيراتون، الغردقة'],
            'sharm-el-sheikh' => ['lat' => 27.9158, 'lng' => 34.3300, 'street' => 'خليج نعمة، شرم الشيخ'],
            'ismailia-city' => ['lat' => 30.5965, 'lng' => 32.2715, 'street' => 'شارع السلطان حسين، الإسماعيلية'],
            'minya-city' => ['lat' => 28.1099, 'lng' => 30.7503, 'street' => 'كورنيش المنيا'],
            'benha' => ['lat' => 30.4660, 'lng' => 31.1848, 'street' => 'شارع سعد زغلول، بنها'],
            'port-said-el-sharq' => ['lat' => 31.2653, 'lng' => 32.3019, 'street' => 'شارع 23 يوليو، بورسعيد'],
            'faiyum-city' => ['lat' => 29.3084, 'lng' => 30.8428, 'street' => 'شارع الجيش، الفيوم'],
            'sohag-city' => ['lat' => 26.5570, 'lng' => 31.6948, 'street' => 'كورنيش النيل، سوهاج'],
            'qena-city' => ['lat' => 26.1551, 'lng' => 32.7160, 'street' => 'شارع 23 يوليو، قنا'],
            'damanhour' => ['lat' => 31.0341, 'lng' => 30.4682, 'street' => 'شارع عبد السلام عارف، دمنهور'],
            'shebin-el-kom' => ['lat' => 30.5591, 'lng' => 31.0106, 'street' => 'شارع الجلاء، شبين الكوم'],
            'el-mahalla-el-kubra' => ['lat' => 30.9697, 'lng' => 31.1681, 'street' => 'شارع البحر، المحلة'],
            'tenth-of-ramadan' => ['lat' => 30.2980, 'lng' => 31.7410, 'street' => 'الحي السابع، العاشر من رمضان'],
            'obour' => ['lat' => 30.2150, 'lng' => 31.4590, 'street' => 'الحي الأول، العبور'],
            'agouza' => ['lat' => 30.0520, 'lng' => 31.2125, 'street' => 'شارع السودان، العجوزة'],
            'el-shorouk' => ['lat' => 30.1390, 'lng' => 31.6180, 'street' => 'الحي الثالث، الشروق'],
        ];
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function doctorNames(): array
    {
        return [
            ['د. أحمد حسن', 'Dr. Ahmed Hassan', 'male'],
            ['د. سارة محمود', 'Dr. Sara Mahmoud', 'female'],
            ['د. محمد علي', 'Dr. Mohamed Ali', 'male'],
            ['د. ندى فتحي', 'Dr. Nada Fathy', 'female'],
            ['د. كريم يوسف', 'Dr. Karim Youssef', 'male'],
            ['د. هبة عادل', 'Dr. Heba Adel', 'female'],
            ['د. عمر خالد', 'Dr. Omar Khaled', 'male'],
            ['د. ياسمين طارق', 'Dr. Yasmin Tarek', 'female'],
            ['د. تامر إبراهيم', 'Dr. Tamer Ibrahim', 'male'],
            ['د. رنا مصطفى', 'Dr. Rana Mostafa', 'female'],
            ['د. هاني سمير', 'Dr. Hany Samir', 'male'],
            ['د. منى شوقي', 'Dr. Mona Shawky', 'female'],
            ['د. وليد عبد الرحمن', 'Dr. Walid Abdelrahman', 'male'],
            ['د. داليا نبيل', 'Dr. Dalia Nabil', 'female'],
            ['د. شريف عثمان', 'Dr. Sherif Osman', 'male'],
            ['د. إيمان فؤاد', 'Dr. Iman Fouad', 'female'],
            ['د. باسم جورج', 'Dr. Bassem George', 'male'],
            ['د. نيفين صلاح', 'Dr. Neveen Salah', 'female'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function flagships(): array
    {
        return [
            [
                'slug' => 'hayah-medical-center',
                'name_ar' => 'مركز حياة الطبي',
                'name_en' => 'Hayah Medical Center',
                'phone' => '01055510001',
                'city' => 'new-cairo',
                'address' => 'التسعين الشمالي، التجمع الخامس، الدور 2',
                'landmark' => 'بجوار كايرو فيستيفال سيتي',
                'lat' => 30.0270,
                'lng' => 31.4737,
                'single' => false,
                'specialty' => 'internal-medicine',
                'plan' => 'growth',
                'hours' => 'extended',
                'description_ar' => 'مركز متعدد التخصصات في القاهرة الجديدة يضم باطنة وقلب وأطفال ونساء وعظام وأنف وأذن وعيون ومخ وأعصاب، مع زيارة منزلية واستشارة فيديو.',
                'description_en' => 'A multi-specialty center in New Cairo covering internal medicine, cardiology, pediatrics, OB/GYN, orthopedics, ENT, ophthalmology and neurology, plus home visits and video consults.',
                'services' => [
                    ServiceTypeCode::ClinicAppointment->value,
                    ServiceTypeCode::HomeVisit->value,
                    ServiceTypeCode::VideoConsultation->value,
                ],
                'branches' => [[
                    'city' => 'nasr-city',
                    'address' => 'شارع مكرم عبيد، مدينة نصر، الدور 3',
                    'landmark' => 'قرب سيتي سنتر',
                    'lat' => 30.0621,
                    'lng' => 31.3450,
                    'label_ar' => 'فرع مدينة نصر',
                    'label_en' => 'Nasr City branch',
                    'hours' => 'evening',
                ]],
                'doctors' => [
                    ['name_ar' => 'د. خالد منصور', 'name_en' => 'Dr. Khaled Mansour', 'specialty' => 'internal-medicine', 'gender' => 'male', 'years' => 18, 'fee' => 450, 'credentials' => 'دكتوراه الباطنة · قصر العيني', 'bio_ar' => 'استشاري باطنة يتابع الأمراض المزمنة والسكر والضغط بمخطط واضح.', 'bio_en' => 'Internal-medicine consultant for chronic disease, diabetes and hypertension.'],
                    ['name_ar' => 'د. لمياء فريد', 'name_en' => 'Dr. Lamia Farid', 'specialty' => 'cardiology', 'gender' => 'female', 'years' => 14, 'fee' => 550, 'credentials' => 'زمالة القلب · المعهد القومي للقلب', 'bio_ar' => 'استشاري قلب تركز على الوقاية ومتابعة قسطرة القلب.', 'bio_en' => 'Cardiologist focused on prevention and post-catheter follow-up.'],
                    ['name_ar' => 'د. أحمد رشدي', 'name_en' => 'Dr. Ahmed Roshdy', 'specialty' => 'pediatrics', 'gender' => 'male', 'years' => 12, 'fee' => 400, 'credentials' => 'استشاري أطفال وحديثي الولادة', 'bio_ar' => 'طبيب أطفال يهتم بالتطعيمات والنمو والرضاعة.', 'bio_en' => 'Pediatrician for vaccines, growth and feeding.'],
                    ['name_ar' => 'د. نسرين عادل', 'name_en' => 'Dr. Nesrine Adel', 'specialty' => 'obstetrics-gynecology', 'gender' => 'female', 'years' => 16, 'fee' => 500, 'credentials' => 'استشاري نساء وتوليد', 'bio_ar' => 'متابعة الحمل والدورات وأمان العيادة النسائية.', 'bio_en' => 'OB/GYN for pregnancy follow-up and clinic visits.'],
                    ['name_ar' => 'د. هشام ناجي', 'name_en' => 'Dr. Hesham Nagy', 'specialty' => 'orthopedics', 'gender' => 'male', 'years' => 20, 'fee' => 500, 'credentials' => 'استشاري عظام ومفاصل', 'bio_ar' => 'إصابات الملاعب والخشونة وحقن المفاصل.', 'bio_en' => 'Orthopedics for sports injuries and joint care.'],
                    ['name_ar' => 'د. رشا يونس', 'name_en' => 'Dr. Rasha Younes', 'specialty' => 'ent', 'gender' => 'female', 'years' => 11, 'fee' => 380, 'credentials' => 'استشاري أنف وأذن وحنجرة', 'bio_ar' => 'الجيوب والسمع واللحمية للأطفال والكبار.', 'bio_en' => 'ENT for sinus, hearing and adenoids.'],
                    ['name_ar' => 'د. مينا جرجس', 'name_en' => 'Dr. Mina Guirguis', 'specialty' => 'ophthalmology', 'gender' => 'male', 'years' => 13, 'fee' => 420, 'credentials' => 'استشاري عيون', 'bio_ar' => 'كشف نظر وشبكية ومتابعة السكر على العين.', 'bio_en' => 'Ophthalmologist for refraction and diabetic eye care.'],
                    ['name_ar' => 'د. عبير حلمي', 'name_en' => 'Dr. Abeer Helmy', 'specialty' => 'neurology', 'gender' => 'female', 'years' => 15, 'fee' => 520, 'credentials' => 'استشاري مخ وأعصاب', 'bio_ar' => 'الصداع والصرع وتنميل الأطراف.', 'bio_en' => 'Neurologist for headache, epilepsy and neuropathy.'],
                ],
                'offers' => [[
                    'slug' => 'hayah-family-checkup',
                    'title_ar' => 'كشف باطنة للأسرة بخصم 20%',
                    'title_en' => 'Family internal-medicine visit 20% off',
                    'category' => OfferCategory::Checkup,
                    'service' => ServiceTypeCode::ClinicAppointment->value,
                    'specialty' => 'internal-medicine',
                    'original_price' => 450,
                    'offer_price' => 360,
                    'discount_type' => 'percentage',
                    'discount_value' => 20,
                    'featured' => true,
                    'description_ar' => 'عرض على كشف الباطنة في مركز حياة خلال هذا الشهر.',
                    'description_en' => 'Internal-medicine visit offer at Hayah this month.',
                    'includes_ar' => 'كشف باطنة + قياس ضغط وسكر في العيادة.',
                    'includes_en' => 'Internal-medicine visit plus in-clinic BP and glucose.',
                ]],
            ],
            [
                'slug' => 'nile-scan-lab',
                'name_ar' => 'معمل نايل سكان',
                'name_en' => 'Nile Scan Lab',
                'phone' => '01055510002',
                'city' => 'nasr-city',
                'address' => 'شارع عباس العقاد، مدينة نصر، الدور الأرضي',
                'landmark' => 'أمام موقف الأتوبيس',
                'lat' => 30.0588,
                'lng' => 31.3312,
                'single' => false,
                'specialty' => 'medical-laboratory',
                'plan' => 'pro',
                'hours' => 'lab',
                'lab' => true,
                'lab_home' => true,
                'description_ar' => 'معمل تحاليل يقدم كل فحوصات الكتالوج مع سحب منزلي وباقات فحص شامل وقلب وخصوبة.',
                'description_en' => 'A lab offering the full catalog, home collection, and checkup, heart and fertility packages.',
                'services' => [
                    ServiceTypeCode::LabTest->value,
                    ServiceTypeCode::HomeLabTest->value,
                ],
                'doctors' => [[
                    'name_ar' => 'د. سالي وهبة',
                    'name_en' => 'Dr. Sally Wahba',
                    'specialty' => 'medical-laboratory',
                    'gender' => 'female',
                    'years' => 17,
                    'fee' => 0,
                    'credentials' => 'استشاري باثولوجيا إكلينيكية',
                    'bio_ar' => 'تشرف على جودة التحاليل وشرح النتائج للأطباء.',
                    'bio_en' => 'Oversees lab quality and result interpretation for clinicians.',
                ]],
                'offers' => [[
                    'slug' => 'nile-full-checkup',
                    'title_ar' => 'الفحص الشامل في نايل سكان',
                    'title_en' => 'Nile Scan full checkup',
                    'category' => OfferCategory::Checkup,
                    'service' => ServiceTypeCode::LabTest->value,
                    'specialty' => 'medical-laboratory',
                    'original_price' => 1470,
                    'offer_price' => 890,
                    'discount_type' => 'percentage',
                    'discount_value' => 39,
                    'featured' => true,
                    'description_ar' => 'باقة سنوية للدم والكبد والكلى والسكر والدرقية.',
                    'description_en' => 'Annual package for blood, liver, kidney, sugar and thyroid.',
                    'includes_ar' => 'CBC، دهون، كبد، كلى، سكر، TSH، بول، ESR.',
                    'includes_en' => 'CBC, lipids, liver, kidney, glucose, TSH, urine, ESR.',
                ]],
            ],
            [
                'slug' => 'alex-care-lab',
                'name_ar' => 'معمل أليكس كير',
                'name_en' => 'Alex Care Lab',
                'phone' => '01055510003',
                'city' => 'smouha',
                'address' => 'شارع فوزي معاذ، سموحة',
                'landmark' => 'قرب مستشفى الشاطبي',
                'lat' => 31.2158,
                'lng' => 29.9410,
                'single' => true,
                'specialty' => 'medical-laboratory',
                'hours' => 'lab',
                'lab' => true,
                'lab_home' => true,
                'description_ar' => 'معمل تحاليل في سموحة يغطي الكتالوج كاملًا مع سحب منزلي حتى العجمي والمنتزه.',
                'description_en' => 'Smouha lab covering the full catalog with home collection across Alexandria.',
                'services' => [ServiceTypeCode::LabTest->value, ServiceTypeCode::HomeLabTest->value],
                'doctors' => [[
                    'name_ar' => 'د. كريم الشافعي',
                    'name_en' => 'Dr. Karim El Shafie',
                    'specialty' => 'medical-laboratory',
                    'gender' => 'male',
                    'years' => 19,
                    'fee' => 0,
                    'credentials' => 'استشاري تحاليل طبية',
                    'bio_ar' => 'خبرة معامل الإسكندرية والساحل الشمالي.',
                    'bio_en' => 'Alexandria and north-coast laboratory experience.',
                ]],
            ],
            [
                'slug' => 'delta-lab-mansoura',
                'name_ar' => 'معمل الدلتا',
                'name_en' => 'Delta Lab',
                'city' => 'mansoura',
                'address' => 'شارع الجمهورية، المنصورة',
                'lat' => 31.0415,
                'lng' => 31.3790,
                'single' => true,
                'specialty' => 'medical-laboratory',
                'hours' => 'lab',
                'lab' => true,
                'lab_home' => false,
                'description_ar' => 'معمل الدقهلية لكل التحاليل والباقات بدون سحب منزلي.',
                'description_en' => 'Dakahlia lab for the full catalog without home collection.',
                'services' => [ServiceTypeCode::LabTest->value],
                'doctors' => [[
                    'name_ar' => 'د. هدى البربري',
                    'name_en' => 'Dr. Hoda El Barbary',
                    'specialty' => 'medical-laboratory',
                    'gender' => 'female',
                    'years' => 21,
                    'fee' => 0,
                    'credentials' => 'استشاري باثولوجيا',
                    'bio_ar' => 'تدير معمل الدلتا في المنصورة منذ أكثر من عشر سنوات.',
                    'bio_en' => 'Has run Delta Lab in Mansoura for over a decade.',
                ]],
            ],
            [
                'slug' => 'bright-smile-dental',
                'name_ar' => 'عيادات ابتسامة مشرقة',
                'name_en' => 'Bright Smile Dental',
                'phone' => '01055510004',
                'city' => 'heliopolis',
                'address' => 'شارع إبراهيم اللقاني، مصر الجديدة',
                'landmark' => 'قرب كوربة',
                'lat' => 30.0918,
                'lng' => 31.3240,
                'single' => false,
                'specialty' => 'dentistry',
                'hours' => 'evening',
                'description_ar' => 'مركز أسنان للتقويم والزراعة وتجميل الأسنان وأسنان الأطفال.',
                'description_en' => 'Dental center for orthodontics, implants, cosmetic dentistry and kids.',
                'services' => [ServiceTypeCode::ClinicAppointment->value],
                'doctors' => [
                    ['name_ar' => 'د. عمرو ذكي', 'name_en' => 'Dr. Amr Zaki', 'specialty' => 'dentistry', 'gender' => 'male', 'years' => 14, 'fee' => 400, 'credentials' => 'استشاري علاج جذور', 'bio_ar' => 'حشو العصب والتجميل التحفظي.', 'bio_en' => 'Root canal and conservative dentistry.'],
                    ['name_ar' => 'د. يارا فوزي', 'name_en' => 'Dr. Yara Fawzy', 'specialty' => 'orthodontics', 'gender' => 'female', 'years' => 9, 'fee' => 500, 'credentials' => 'أخصائي تقويم', 'bio_ar' => 'تقويم معدني وشفاف للكبار والمراهقين.', 'bio_en' => 'Metal and clear aligners for adults and teens.'],
                    ['name_ar' => 'د. حازم فهمي', 'name_en' => 'Dr. Hazem Fahmy', 'specialty' => 'dental-implants', 'gender' => 'male', 'years' => 16, 'fee' => 700, 'credentials' => 'استشاري زراعة أسنان', 'bio_ar' => 'زراعة فورية وترقيع عظم.', 'bio_en' => 'Immediate implants and bone grafting.'],
                    ['name_ar' => 'د. سماح توفيق', 'name_en' => 'Dr. Samah Tawfik', 'specialty' => 'pediatric-dentistry', 'gender' => 'female', 'years' => 10, 'fee' => 350, 'credentials' => 'أخصائي أسنان أطفال', 'bio_ar' => 'جلسات أطفال بدون خوف وحشو وقائي.', 'bio_en' => 'Child-friendly visits and preventive fillings.'],
                ],
                'offers' => [[
                    'slug' => 'bright-smile-cleaning',
                    'title_ar' => 'تنظيف وتبييض بخصم 25%',
                    'title_en' => 'Cleaning and whitening 25% off',
                    'category' => OfferCategory::Dental,
                    'service' => ServiceTypeCode::ClinicAppointment->value,
                    'specialty' => 'cosmetic-dentistry',
                    'original_price' => 1200,
                    'offer_price' => 900,
                    'discount_type' => 'percentage',
                    'discount_value' => 25,
                    'featured' => true,
                    'description_ar' => 'جلسة تنظيف وتبييض في عيادات ابتسامة مشرقة.',
                    'description_en' => 'Cleaning and whitening session at Bright Smile.',
                    'includes_ar' => 'تنظيف جير + تبييض عيادة.',
                    'includes_en' => 'Scaling plus in-clinic whitening.',
                ]],
            ],
            [
                'slug' => 'derma-laser-dokki',
                'name_ar' => 'ديرما ليزر الدقي',
                'name_en' => 'Derma Laser Dokki',
                'city' => 'dokki',
                'address' => 'شارع التحرير، الدقي، الدور 5',
                'lat' => 30.0394,
                'lng' => 31.2112,
                'single' => false,
                'specialty' => 'dermatology',
                'hours' => 'evening',
                'description_ar' => 'جلدية وليزر وتجميل غير جراحي وعناية بالبشرة.',
                'description_en' => 'Dermatology, laser, aesthetic medicine and skin care.',
                'services' => [ServiceTypeCode::ClinicAppointment->value, ServiceTypeCode::VideoConsultation->value],
                'doctors' => [
                    ['name_ar' => 'د. ناهد سامي', 'name_en' => 'Dr. Nahed Samy', 'specialty' => 'dermatology', 'gender' => 'female', 'years' => 18, 'fee' => 500, 'credentials' => 'استشاري جلدية', 'bio_ar' => 'حب الشباب والصدفية والأكزيما.', 'bio_en' => 'Acne, psoriasis and eczema.'],
                    ['name_ar' => 'د. فادي موريس', 'name_en' => 'Dr. Fady Maurice', 'specialty' => 'laser-skin-treatment', 'gender' => 'male', 'years' => 11, 'fee' => 650, 'credentials' => 'أخصائي ليزر', 'bio_ar' => 'إزالة شعر وندبات وفلاتر ليزر.', 'bio_en' => 'Laser hair removal, scars and filters.'],
                    ['name_ar' => 'د. رولا أنطون', 'name_en' => 'Dr. Rola Antoun', 'specialty' => 'aesthetic-medicine', 'gender' => 'female', 'years' => 8, 'fee' => 800, 'credentials' => 'تجميل غير جراحي', 'bio_ar' => 'فيلر وبوتكس وخيوط.', 'bio_en' => 'Fillers, Botox and threads.'],
                ],
                'offers' => [[
                    'slug' => 'derma-laser-hair',
                    'title_ar' => 'جلسة ليزر إزالة شعر',
                    'title_en' => 'Laser hair-removal session',
                    'category' => OfferCategory::Laser,
                    'service' => ServiceTypeCode::ClinicAppointment->value,
                    'specialty' => 'laser-skin-treatment',
                    'original_price' => 900,
                    'offer_price' => 590,
                    'discount_type' => 'fixed',
                    'discount_value' => 310,
                    'featured' => true,
                    'description_ar' => 'عرض تجريبي على جلسة ليزر في الدقي.',
                    'description_en' => 'Introductory laser session in Dokki.',
                    'includes_ar' => 'جلسة واحدة لمنطقة متوسطة.',
                    'includes_en' => 'One session for a medium area.',
                ]],
            ],
            [
                'slug' => 'cairo-pt-center',
                'name_ar' => 'مركز القاهرة للعلاج الطبيعي',
                'name_en' => 'Cairo Physio Center',
                'phone' => '01055510005',
                'city' => 'maadi',
                'address' => 'شارع 9، المعادي',
                'lat' => 29.9610,
                'lng' => 31.2575,
                'single' => false,
                'specialty' => 'physical-therapy',
                'hours' => 'extended',
                'description_ar' => 'علاج طبيعي وعلاج وظيفي وتأهيل رياضي بعد الجراحة والإصابات. أول زيارة تقييم في المركز.',
                'description_en' => 'Physiotherapy, occupational therapy and sports rehab after surgery and injury. First visit is an evaluation.',
                'services' => [
                    ServiceTypeCode::ClinicAppointment->value,
                    ServiceTypeCode::PhysicalTherapy->value,
                    ServiceTypeCode::OccupationalTherapy->value,
                    ServiceTypeCode::HomeVisit->value,
                ],
                'doctors' => [
                    ['name_ar' => 'أ. محمد السيد', 'name_en' => 'Mohamed El Sayed, PT', 'specialty' => 'physical-therapy', 'gender' => 'male', 'years' => 12, 'fee' => 280, 'credentials' => 'أخصائي علاج طبيعي', 'bio_ar' => 'العمود الفقري والركبة بعد العمليات.', 'bio_en' => 'Spine and post-op knee rehab.'],
                    ['name_ar' => 'أ. نورا يحيى', 'name_en' => 'Nora Yehia, PT', 'specialty' => 'sports-rehabilitation', 'gender' => 'female', 'years' => 7, 'fee' => 320, 'credentials' => 'تأهيل رياضي', 'bio_ar' => 'إصابات الملاعب والعودة للتدريب.', 'bio_en' => 'Sports injuries and return-to-play.'],
                    ['name_ar' => 'أ. هدى منصور', 'name_en' => 'Hoda Mansour, OT', 'specialty' => 'occupational-therapy', 'gender' => 'female', 'years' => 9, 'fee' => 300, 'credentials' => 'أخصائي علاج وظيفي', 'bio_ar' => 'إعادة تأهيل اليد والنشاط اليومي بعد الجلطات.', 'bio_en' => 'Hand rehab and daily-function recovery after stroke.'],
                ],
                'offers' => [[
                    'slug' => 'cairo-pt-pack',
                    'title_ar' => 'باقة 8 جلسات علاج طبيعي',
                    'title_en' => '8-session physio pack',
                    'category' => OfferCategory::PhysicalTherapy,
                    'service' => ServiceTypeCode::PhysicalTherapy->value,
                    'specialty' => 'physical-therapy',
                    'original_price' => 2240,
                    'offer_price' => 1680,
                    'session_count' => 8,
                    'discount_type' => 'percentage',
                    'discount_value' => 25,
                    'featured' => true,
                    'description_ar' => 'باقة جلسات في المعادي مع تقييم أول.',
                    'description_en' => 'Session pack in Maadi including the first assessment.',
                    'includes_ar' => 'تقييم + 8 جلسات.',
                    'includes_en' => 'Assessment plus 8 sessions.',
                ]],
            ],
            [
                'slug' => 'mind-care-clinic',
                'name_ar' => 'عيادة مايند كير',
                'name_en' => 'Mind Care Clinic',
                'phone' => '01055510006',
                'city' => 'zamalek',
                'address' => 'شارع 26 يوليو، الزمالك',
                'lat' => 30.0624,
                'lng' => 31.2190,
                'single' => false,
                'specialty' => 'psychiatry',
                'hours' => 'evening',
                'description_ar' => 'طب نفسي وعلاج نفسي أونلاين وحضورًا، مع خيار الاستشارة النفسية المشددة الخصوصية.',
                'description_en' => 'Psychiatry and psychotherapy in clinic and online, including the privacy-sensitive psych service.',
                'services' => [
                    ServiceTypeCode::ClinicAppointment->value,
                    ServiceTypeCode::VideoConsultation->value,
                    ServiceTypeCode::PsychiatricConsultation->value,
                ],
                'doctors' => [
                    ['name_ar' => 'د. هالة كمال', 'name_en' => 'Dr. Hala Kamal', 'specialty' => 'psychiatry', 'gender' => 'female', 'years' => 16, 'fee' => 700, 'credentials' => 'استشاري طب نفسي', 'bio_ar' => 'القلق والاكتئاب واضطرابات النوم.', 'bio_en' => 'Anxiety, depression and sleep disorders.'],
                    ['name_ar' => 'أ. كريم صبحي', 'name_en' => 'Karim Sobhy, PsyD', 'specialty' => 'psychotherapy', 'gender' => 'male', 'years' => 9, 'fee' => 550, 'credentials' => 'معالج نفسي', 'bio_ar' => 'جلسات إرشاد معرفي سلوكي.', 'bio_en' => 'CBT counselling sessions.'],
                ],
            ],
            [
                'slug' => 'aswan-family-clinic',
                'name_ar' => 'عيادة أسوان العائلية',
                'name_en' => 'Aswan Family Clinic',
                'city' => 'aswan-city',
                'address' => 'كورنيش النيل، أسوان',
                'lat' => 24.0892,
                'lng' => 32.8991,
                'single' => true,
                'specialty' => 'general-practice',
                'hours' => 'default',
                'description_ar' => 'طب أسرة في أسوان مع زيارات منزلية واستشارة فيديو للقرى القريبة.',
                'description_en' => 'Family medicine in Aswan with home visits and video consults for nearby villages.',
                'services' => [
                    ServiceTypeCode::ClinicAppointment->value,
                    ServiceTypeCode::HomeVisit->value,
                    ServiceTypeCode::VideoConsultation->value,
                ],
                'doctors' => [[
                    'name_ar' => 'د. محمود عبد الله',
                    'name_en' => 'Dr. Mahmoud Abdallah',
                    'specialty' => 'general-practice',
                    'gender' => 'male',
                    'years' => 22,
                    'fee' => 250,
                    'credentials' => 'استشاري طب أسرة',
                    'bio_ar' => 'يكشف للكبار والأطفال ويتابع الأمراض المزمنة في أسوان.',
                    'bio_en' => 'Sees adults and children and follows chronic disease in Aswan.',
                ]],
            ],
            [
                'slug' => 'hurghada-care-clinic',
                'name_ar' => 'عيادة الغردقة كير',
                'name_en' => 'Hurghada Care Clinic',
                'city' => 'hurghada',
                'address' => 'شارع شيراتون، الغردقة',
                'lat' => 27.2584,
                'lng' => 33.8120,
                'single' => true,
                'specialty' => 'general-practice',
                'description_ar' => 'عيادة عامة للسكان والزوار في الغردقة، مع استشارة فيديو.',
                'description_en' => 'GP clinic for residents and visitors in Hurghada, with video consults.',
                'services' => [ServiceTypeCode::ClinicAppointment->value, ServiceTypeCode::VideoConsultation->value, ServiceTypeCode::HomeVisit->value],
                'doctors' => [[
                    'name_ar' => 'د. ياسمين البحري',
                    'name_en' => 'Dr. Yasmin El Bahry',
                    'specialty' => 'general-practice',
                    'gender' => 'female',
                    'years' => 11,
                    'fee' => 350,
                    'credentials' => 'طب عام وطوارئ',
                    'bio_ar' => 'تعامل مع حالات السياحة والإصابات البسيطة.',
                    'bio_en' => 'Handles visitor care and minor injuries.',
                ]],
            ],
            [
                'slug' => 'tanta-heart-clinic',
                'name_ar' => 'عيادة طنطا للقلب',
                'name_en' => 'Tanta Heart Clinic',
                'city' => 'tanta',
                'address' => 'شارع البحر، طنطا',
                'lat' => 30.7870,
                'lng' => 31.0010,
                'single' => true,
                'specialty' => 'cardiology',
                'description_ar' => 'عيادة قلب في الغربية مع رسم قلب ومتابعة الضغط.',
                'description_en' => 'Cardiology clinic in Gharbia with ECG and blood-pressure follow-up.',
                'services' => [ServiceTypeCode::ClinicAppointment->value, ServiceTypeCode::HomeVisit->value],
                'doctors' => [[
                    'name_ar' => 'د. عادل شوقي',
                    'name_en' => 'Dr. Adel Shawky',
                    'specialty' => 'cardiology',
                    'gender' => 'male',
                    'years' => 24,
                    'fee' => 400,
                    'credentials' => 'استشاري قلب وأوعية',
                    'bio_ar' => 'قسطرة ومتابعة هبوط القلب في الدلتا.',
                    'bio_en' => 'Catheter and heart-failure follow-up in the Delta.',
                ]],
            ],
            [
                'slug' => 'zayed-women-clinic',
                'name_ar' => 'عيادة زايد للنساء',
                'name_en' => 'Zayed Women Clinic',
                'city' => 'sheikh-zayed',
                'address' => 'الحي 12، الشيخ زايد',
                'lat' => 30.0484,
                'lng' => 30.9752,
                'single' => true,
                'specialty' => 'obstetrics-gynecology',
                'hours' => 'evening',
                'description_ar' => 'نساء وتوليد في الشيخ زايد مع متابعة حمل واستشارة فيديو.',
                'description_en' => 'OB/GYN in Sheikh Zayed with pregnancy follow-up and video consults.',
                'services' => [ServiceTypeCode::ClinicAppointment->value, ServiceTypeCode::VideoConsultation->value],
                'doctors' => [[
                    'name_ar' => 'د. سحر فؤاد',
                    'name_en' => 'Dr. Sahar Fouad',
                    'specialty' => 'obstetrics-gynecology',
                    'gender' => 'female',
                    'years' => 15,
                    'fee' => 480,
                    'credentials' => 'استشاري نساء وتوليد',
                    'bio_ar' => 'حمل عالي الخطورة وتنظيم الأسرة.',
                    'bio_en' => 'High-risk pregnancy and family planning.',
                ]],
            ],
            [
                'slug' => 'october-home-care',
                'name_ar' => 'أكتوبر هوم كير',
                'name_en' => 'October Home Care',
                'city' => 'sixth-of-october',
                'address' => 'المحور المركزي، 6 أكتوبر',
                'lat' => 29.9290,
                'lng' => 30.9195,
                'single' => true,
                'specialty' => 'general-practice',
                'description_ar' => 'زيارات منزلية أساسًا في 6 أكتوبر والشيخ زايد مع كشف عيادة خفيف.',
                'description_en' => 'Home-visit first GP covering 6th of October and Sheikh Zayed.',
                'services' => [ServiceTypeCode::HomeVisit->value, ServiceTypeCode::ClinicAppointment->value, ServiceTypeCode::VideoConsultation->value],
                'doctors' => [[
                    'name_ar' => 'د. تامر حسني',
                    'name_en' => 'Dr. Tamer Hosny',
                    'specialty' => 'general-practice',
                    'gender' => 'male',
                    'years' => 13,
                    'fee' => 300,
                    'credentials' => 'طب أسرة وزيارات منزلية',
                    'bio_ar' => 'كشف منزلي لكبار السن ومتابعة ما بعد العمليات.',
                    'bio_en' => 'Home visits for older adults and post-op follow-up.',
                ]],
            ],
            [
                'slug' => 'luxor-eye-clinic',
                'name_ar' => 'عيادة الأقصر للعيون',
                'name_en' => 'Luxor Eye Clinic',
                'city' => 'luxor-city',
                'address' => 'الكورنيش، الأقصر',
                'lat' => 25.6878,
                'lng' => 32.6401,
                'single' => true,
                'specialty' => 'ophthalmology',
                'description_ar' => 'كشف نظر ومياه بيضاء ومتابعة الشبكية في الأقصر.',
                'description_en' => 'Refraction, cataract and retina follow-up in Luxor.',
                'services' => [ServiceTypeCode::ClinicAppointment->value],
                'doctors' => [[
                    'name_ar' => 'د. جورج كامل',
                    'name_en' => 'Dr. George Kamel',
                    'specialty' => 'ophthalmology',
                    'gender' => 'male',
                    'years' => 19,
                    'fee' => 350,
                    'credentials' => 'استشاري عيون',
                    'bio_ar' => 'جراحة مياه بيضاء وكشف نظر للأطفال.',
                    'bio_en' => 'Cataract surgery and pediatric refraction.',
                ]],
            ],
            [
                'slug' => 'sharm-clinic',
                'name_ar' => 'عيادة شرم كير',
                'name_en' => 'Sharm Care Clinic',
                'city' => 'sharm-el-sheikh',
                'address' => 'خليج نعمة، شرم الشيخ',
                'lat' => 27.9149,
                'lng' => 34.3292,
                'single' => true,
                'specialty' => 'general-practice',
                'description_ar' => 'عيادة عامة في شرم الشيخ للسكان والزوار.',
                'description_en' => 'GP clinic in Sharm El Sheikh for residents and visitors.',
                'services' => [ServiceTypeCode::ClinicAppointment->value, ServiceTypeCode::HomeVisit->value],
                'doctors' => [[
                    'name_ar' => 'د. علي البحر',
                    'name_en' => 'Dr. Ali El Bahr',
                    'specialty' => 'general-practice',
                    'gender' => 'male',
                    'years' => 10,
                    'fee' => 400,
                    'credentials' => 'طب عام',
                    'bio_ar' => 'حالات الجهاز التنفسي والإصابات السياحية.',
                    'bio_en' => 'Respiratory cases and visitor injuries.',
                ]],
            ],
            [
                'slug' => 'nile-plastic-mohandessin',
                'name_ar' => 'عيادة النيل للتجميل',
                'name_en' => 'Nile Plastic Clinic',
                'city' => 'mohandessin',
                'address' => 'شارع جامعة الدول العربية، المهندسين',
                'lat' => 30.0566,
                'lng' => 31.1994,
                'single' => true,
                'specialty' => 'plastic-surgery',
                'hours' => 'evening',
                'description_ar' => 'جراحة تجميل واستشارة تقييمية قبل الإجراء.',
                'description_en' => 'Plastic surgery with an evaluation consult before procedures.',
                'services' => [ServiceTypeCode::ClinicAppointment->value],
                'doctors' => [[
                    'name_ar' => 'د. وائل فكري',
                    'name_en' => 'Dr. Wael Fekry',
                    'specialty' => 'plastic-surgery',
                    'gender' => 'male',
                    'years' => 18,
                    'fee' => 1200,
                    'credentials' => 'استشاري جراحة تجميل',
                    'bio_ar' => 'تجميل الأنف والجفن وشد الجسم.',
                    'bio_en' => 'Rhinoplasty, eyelids and body contouring.',
                ]],
                'offers' => [[
                    'slug' => 'nile-plastic-eval',
                    'title_ar' => 'كشف تقييم تجميلي',
                    'title_en' => 'Plastic evaluation visit',
                    'category' => OfferCategory::Beauty,
                    'service' => ServiceTypeCode::ClinicAppointment->value,
                    'specialty' => 'plastic-surgery',
                    'original_price' => 1200,
                    'offer_price' => 700,
                    'discount_type' => 'fixed',
                    'discount_value' => 500,
                    'featured' => false,
                    'description_ar' => 'كشف تقييم قبل أي إجراء جراحي.',
                    'description_en' => 'Evaluation visit before any surgical procedure.',
                    'includes_ar' => 'كشف + خطة مبدئية.',
                    'includes_en' => 'Consult plus an outline plan.',
                ]],
            ],
            [
                'slug' => 'pending-review-clinic',
                'name_ar' => 'عيادة قيد المراجعة',
                'name_en' => 'Pending Review Clinic',
                'city' => 'obour',
                'address' => 'الحي الأول، العبور',
                'lat' => 30.2155,
                'lng' => 31.4582,
                'single' => true,
                'specialty' => 'general-practice',
                'verification' => VerificationStatus::Pending,
                'description_ar' => 'عيادة تجريبية ما زالت تنتظر توثيق الإدارة — لا تظهر في الدليل العام.',
                'description_en' => 'A demo clinic still awaiting admin verification — hidden from public directories.',
                'services' => [ServiceTypeCode::ClinicAppointment->value],
                'doctors' => [[
                    'name_ar' => 'د. علاء حامد',
                    'name_en' => 'Dr. Alaa Hamed',
                    'specialty' => 'general-practice',
                    'gender' => 'male',
                    'years' => 6,
                    'fee' => 200,
                    'credentials' => 'طب عام',
                    'bio_ar' => 'عيادة جديدة في العبور بانتظار التوثيق.',
                    'bio_en' => 'New Obour clinic waiting for verification.',
                ]],
            ],
            [
                'slug' => 'rejected-demo-clinic',
                'name_ar' => 'عيادة مرفوضة تجريبيًا',
                'name_en' => 'Rejected Demo Clinic',
                'city' => 'imbaba',
                'address' => 'شارع المطابع، إمبابة',
                'single' => true,
                'specialty' => 'general-practice',
                'verification' => VerificationStatus::Rejected,
                'active' => true,
                'description_ar' => 'عيادة تجريبية مرفوضة لاختبار طابور مراجعة الإدارة.',
                'description_en' => 'A rejected demo clinic so the admin review queue has a negative case.',
                'services' => [ServiceTypeCode::ClinicAppointment->value],
                'doctors' => [[
                    'name_ar' => 'د. جمال رشيد',
                    'name_en' => 'Dr. Gamal Rasheed',
                    'specialty' => 'general-practice',
                    'gender' => 'male',
                    'years' => 4,
                    'fee' => 150,
                    'credentials' => 'طب عام',
                    'bio_ar' => 'حساب مرفوض عمدًا في البيانات التجريبية.',
                    'bio_en' => 'Intentionally rejected in the demo dataset.',
                ]],
            ],
        ];
    }

    private function syncModules(Clinic $clinic, array $provider, Specialty $specialty): void
    {
        $modules = [ClinicModule::Appointments->value];

        if ($provider['lab'] ?? false) {
            $modules[] = ClinicModule::Labs->value;
        }

        if (($provider['offers'] ?? []) !== []) {
            $modules[] = ClinicModule::Promotions->value;
        }

        $category = $specialty->category;

        if ($category === 'physical_therapy') {
            $modules[] = ClinicModule::PhysicalTherapy->value;
        }

        if ($category === 'dental') {
            $modules[] = ClinicModule::Dental->value;
        }

        if (in_array($category, ['cosmetic', 'beauty'], true)) {
            $modules[] = ClinicModule::Cosmetic->value;
        }

        if ($specialty->slug === 'massage-wellness') {
            $modules[] = ClinicModule::Massage->value;
        }

        $clinic->update(['modules' => ClinicModule::normalize($modules)]);
    }

    private function uniquePhone(string $prefix, int &$seq): string
    {
        do {
            $phone = $this->nextPhone($prefix, $seq);
        } while (User::query()->where('phone', $phone)->exists());

        return $phone;
    }

    private function nextPhone(string $prefix, int &$seq): string
    {
        $seq++;

        return User::normalizePhone($prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT));
    }
}
