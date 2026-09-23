<?php

namespace App\Http\Controllers\Api\Agent\V1;

use App\Enums\OfferCategory;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Governorate;
use App\Models\Promotion;
use App\Models\ServiceType;
use App\Models\SitePage;
use App\Models\Specialty;
use App\Models\SupportTicket;
use App\Services\LoyaltyProgram;
use App\Support\Branding;
use App\Support\PublicImage;
use App\Support\SearchQuery;
use App\Support\SupportLinks;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DiscoveryController extends Controller
{
    public function __construct(
        private LoyaltyProgram $loyalty,
        private SupportLinks $support,
        private Branding $branding,
    ) {}

    public function help(): JsonResponse
    {
        return $this->json([
            'brand' => method_exists($this->branding, 'name') ? $this->branding->name() : (string) config('app.name'),
            'tagline' => $this->branding->tagline(),
            'about' => [
                'heading' => (string) __('pages.about.heading'),
                'lead' => (string) __('pages.about.lead'),
            ],
            'how_it_works' => [
                'heading' => (string) __('pages.how.heading'),
                'lead' => (string) __('pages.how.lead'),
                'steps' => array_values(__('pages.how.steps')),
            ],
            'faq' => collect(__('pages.help.items'))
                ->map(fn (array $item, string $key) => [
                    'key' => $key,
                    'title' => $item['title'],
                    'body' => $item['body'],
                ])
                ->values()
                ->all(),
            'contact' => [
                'heading' => (string) __('pages.contact.heading'),
                'lead' => (string) __('pages.contact.lead'),
                'phone' => $this->support->telephone(),
                'whatsapp' => method_exists($this->support, 'whatsappTelephone')
                    ? $this->support->whatsappTelephone()
                    : $this->support->telephone(),
                'email' => $this->support->email(),
                'phone_url' => $this->support->hasPhone() ? $this->support->phoneUrl() : null,
                'whatsapp_url' => $this->support->hasWhatsapp() ? $this->support->whatsappUrl() : null,
                'contact_url' => $this->namedUrl('contact', '/contact'),
                'complaints_url' => $this->namedUrl('complaints.create', '/complaints'),
                'ticket_categories' => SupportTicket::CATEGORIES,
            ],
            'pages' => $this->publishedPages(),
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
        ]);

        $term = trim((string) ($validated['q'] ?? ''));

        return $this->json([
            'q' => $term,
            'suggestions' => array_values(array_filter([
                ...$this->intentSuggestions($term),
                ...$this->catalogSuggestions($term),
            ])),
        ]);
    }

    public function campaigns(): JsonResponse
    {
        $signup = $this->loyalty->activeSignupCampaign();

        if ($signup) {
            $signup['register_url'] = $this->namedUrl('register', '/register');
        }

        $offers = Promotion::query()
            ->running()
            ->featured()
            ->with(['clinic.primaryAddress.city', 'specialty'])
            ->latest('starts_at')
            ->limit(8)
            ->get()
            ->map(fn (Promotion $offer) => $this->offerCard($offer))
            ->values()
            ->all();

        return $this->json([
            'signup' => $signup,
            'offers' => $offers,
        ]);
    }

    public function offers(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:64'],
            'governorate' => ['nullable', 'string', 'max:140'],
            'city' => ['nullable', 'string', 'max:140'],
            'max_price' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $term = trim((string) ($filters['q'] ?? ''));
        $limit = max(1, min(24, (int) ($filters['limit'] ?? 12)));

        $offers = Promotion::query()
            ->running()
            ->with(['clinic.primaryAddress.city', 'specialty'])
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($term !== '', fn ($query) => SearchQuery::constrain($query, $term, function ($inner, $token) {
                $inner->whereLike('title_ar', "%{$token}%")->orWhereLike('title_en', "%{$token}%")
                    ->orWhereLike('includes_ar', "%{$token}%")->orWhereLike('includes_en', "%{$token}%");
            }))
            ->when($filters['governorate'] ?? null, fn ($query, $slug) => $query->whereHas(
                'clinic.addresses.city.governorate',
                fn ($governorate) => $governorate->where('slug', $slug)
            ))
            ->when($filters['city'] ?? null, fn ($query, $slug) => $query->whereHas(
                'clinic.addresses.city',
                fn ($city) => $city->where('slug', $slug)
            ))
            ->when($filters['max_price'] ?? null, fn ($query, $price) => $query->where('offer_price', '<=', $price))
            ->latest('starts_at')
            ->limit($limit)
            ->get()
            ->map(fn (Promotion $offer) => $this->offerCard($offer))
            ->values()
            ->all();

        return $this->json([
            'q' => $term,
            'categories' => collect(OfferCategory::cases())->map(fn (OfferCategory $category) => [
                'value' => $category->value,
                'label' => $category->label(),
            ])->values()->all(),
            'offers' => $offers,
        ]);
    }

    public function showOffer(Promotion $offer): JsonResponse
    {
        abort_unless($offer->isRunning(), 404);

        $offer->increment('views_count');
        $offer->load([
            'clinic.primaryAddress.city',
            'specialty',
            'serviceType',
        ]);

        return $this->json([
            ...$this->offerCard($offer),
            'description' => $offer->translated('description'),
            'includes' => $offer->includes,
            'conditions' => $offer->conditions,
            'starts_at' => $offer->starts_at?->toIso8601String(),
            'ends_at' => $offer->ends_at?->toIso8601String(),
            'book_url' => $this->namedUrl('offers.show', '/offers/'.$offer->slug, $offer),
            'specialty' => $offer->specialty?->name,
            'service' => $offer->serviceType?->name,
        ]);
    }

    public function geography(): JsonResponse
    {
        $payload = Governorate::active()->ordered()->with('cities', fn ($q) => $q->active())->get()
            ->map(fn (Governorate $governorate) => [
                'id' => $governorate->id,
                'slug' => $governorate->slug,
                'name' => ['ar' => $governorate->name_ar, 'en' => $governorate->name_en],
                'cities' => $governorate->cities->map(fn (City $city) => [
                    'id' => $city->id,
                    'slug' => $city->slug,
                    'name' => ['ar' => $city->name_ar, 'en' => $city->name_en],
                ]),
            ]);

        return $this->json($payload);
    }

    public function specialties(): JsonResponse
    {
        $payload = Specialty::active()->ordered()->get()
            ->map(fn (Specialty $specialty) => [
                'id' => $specialty->id,
                'slug' => $specialty->slug,
                'category' => $specialty->category,
                'name' => ['ar' => $specialty->name_ar, 'en' => $specialty->name_en],
            ]);

        return $this->json($payload);
    }

    public function serviceTypes(): JsonResponse
    {
        $payload = ServiceType::active()->ordered()->get()
            ->map(fn (ServiceType $type) => [
                'id' => $type->id,
                'code' => $type->code,
                'slug' => $type->slug,
                'name' => ['ar' => $type->name_ar, 'en' => $type->name_en],
                'requires' => [
                    'clinic_address' => $type->requires_clinic_address,
                    'patient_address' => $type->requires_patient_address,
                    'time_slot' => $type->requires_time_slot,
                ],
                'is_online' => $type->is_online,
            ]);

        return $this->json($payload);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function intentSuggestions(string $term): array
    {
        $intents = [
            ['type' => 'intent', 'key' => 'search_doctors', 'label' => __('agent.api.suggest_doctors'), 'path' => '/search', 'params' => ['type' => 'doctors']],
            ['type' => 'intent', 'key' => 'search_clinics', 'label' => __('agent.api.suggest_clinics'), 'path' => '/search', 'params' => ['type' => 'clinics']],
            ['type' => 'intent', 'key' => 'offers', 'label' => __('agent.api.suggest_offers'), 'path' => '/offers', 'params' => []],
            ['type' => 'intent', 'key' => 'campaigns', 'label' => __('agent.api.suggest_campaigns'), 'path' => '/campaigns', 'params' => []],
            ['type' => 'intent', 'key' => 'register', 'label' => __('agent.api.suggest_register'), 'path' => '/customers', 'params' => []],
            ['type' => 'intent', 'key' => 'reset_password', 'label' => __('agent.api.suggest_reset'), 'path' => '/customers/password/forgot', 'params' => []],
            ['type' => 'intent', 'key' => 'support', 'label' => __('agent.api.suggest_support'), 'path' => '/support/tickets', 'params' => []],
        ];

        if ($term === '') {
            return $intents;
        }

        $normalized = mb_strtolower($term);

        return array_values(array_filter($intents, function (array $intent) use ($normalized) {
            return str_contains(mb_strtolower((string) $intent['label']), $normalized)
                || str_contains($intent['key'], $normalized)
                || ($intent['key'] === 'register' && preg_match('/(حساب|تسجيل|register|sign ?up)/u', $normalized))
                || ($intent['key'] === 'reset_password' && preg_match('/(كلمة|مرور|password|reset)/u', $normalized))
                || ($intent['key'] === 'support' && preg_match('/(دعم|تذكرة|شكوى|support|ticket|help)/u', $normalized))
                || ($intent['key'] === 'offers' && preg_match('/(عرض|عروض|offer)/u', $normalized));
        }));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function catalogSuggestions(string $term): array
    {
        if ($term === '') {
            return Specialty::query()
                ->active()
                ->ordered()
                ->limit(6)
                ->get()
                ->map(fn (Specialty $specialty) => [
                    'type' => 'specialty',
                    'label' => $specialty->name,
                    'slug' => $specialty->slug,
                    'url' => $this->namedUrl('specialties.show', '/specialties/'.$specialty->slug, $specialty),
                ])
                ->all();
        }

        $specialties = Specialty::query()
            ->active()
            ->tap(fn ($query) => SearchQuery::constrain($query, $term, function ($inner, $token) {
                $inner->whereLike('name_ar', "%{$token}%")->orWhereLike('name_en', "%{$token}%");
            }))
            ->ordered()
            ->limit(5)
            ->get()
            ->map(fn (Specialty $specialty) => [
                'type' => 'specialty',
                'label' => $specialty->name,
                'slug' => $specialty->slug,
                'url' => $this->namedUrl('specialties.show', '/specialties/'.$specialty->slug, $specialty),
            ]);

        $cities = City::query()
            ->active()
            ->with('governorate')
            ->tap(fn ($query) => SearchQuery::constrain($query, $term, function ($inner, $token) {
                $inner->whereLike('name_ar', "%{$token}%")->orWhereLike('name_en', "%{$token}%");
            }))
            ->ordered()
            ->limit(5)
            ->get()
            ->map(fn (City $city) => [
                'type' => 'city',
                'label' => $city->name,
                'slug' => $city->slug,
                'governorate' => $city->governorate?->name,
                'url' => $this->namedUrl('cities.show', '/cities/'.$city->slug, $city),
            ]);

        $doctors = $this->visibleDoctors()
            ->tap(fn ($query) => SearchQuery::constrain($query, $term, function ($inner, $token) {
                $inner->whereLike('name_ar', "%{$token}%")->orWhereLike('name_en', "%{$token}%");
            }))
            ->orderBy('name_ar')
            ->limit(5)
            ->get()
            ->map(fn (Doctor $doctor) => [
                'type' => 'doctor',
                'label' => $doctor->name,
                'slug' => $doctor->slug,
                'url' => $this->namedUrl('doctors.show', '/doctors/'.$doctor->slug, $doctor),
            ]);

        $clinics = Clinic::query()
            ->listable()
            ->tap(fn ($query) => SearchQuery::constrain($query, $term, function ($inner, $token) {
                $inner->whereLike('name_ar', "%{$token}%")->orWhereLike('name_en', "%{$token}%");
            }))
            ->orderBy('name_ar')
            ->limit(5)
            ->get()
            ->map(fn (Clinic $clinic) => [
                'type' => 'clinic',
                'label' => $clinic->name,
                'slug' => $clinic->slug,
                'url' => $this->namedUrl('clinics.show', '/clinics/'.$clinic->slug, $clinic),
            ]);

        $services = ServiceType::query()
            ->active()
            ->tap(fn ($query) => SearchQuery::constrain($query, $term, function ($inner, $token) {
                $inner->whereLike('name_ar', "%{$token}%")->orWhereLike('name_en', "%{$token}%");
            }))
            ->ordered()
            ->limit(5)
            ->get()
            ->map(fn (ServiceType $type) => [
                'type' => 'service',
                'label' => $type->name,
                'slug' => $type->slug,
                'url' => $this->namedUrl('services.show', '/services/'.$type->slug, $type),
            ]);

        return [...$specialties, ...$cities, ...$doctors, ...$clinics, ...$services];
    }

    /**
     * @return array<string, mixed>
     */
    private function offerCard(Promotion $offer): array
    {
        return [
            'title' => $offer->title,
            'slug' => $offer->slug,
            'url' => $this->namedUrl('offers.show', '/offers/'.$offer->slug, $offer),
            'category' => $offer->category?->value,
            'category_label' => $offer->category?->label(),
            'offer_price' => $offer->offer_price !== null ? (float) $offer->offer_price : null,
            'original_price' => $offer->original_price !== null ? (float) $offer->original_price : null,
            'clinic' => $offer->clinic?->name,
            'city' => $offer->clinic?->primaryAddress?->city?->name,
            'image' => PublicImage::url($offer->banner_image_path),
            'featured' => (bool) $offer->is_featured,
        ];
    }

    /**
     * @return Builder<Doctor>
     */
    private function visibleDoctors()
    {
        $query = Doctor::query();

        if (method_exists(Doctor::class, 'scopeListable')) {
            return $query->listable();
        }

        return $query->where('is_active', true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function publishedPages(): array
    {
        if (! class_exists(SitePage::class) || ! Schema::hasTable('site_pages')) {
            return [];
        }

        return SitePage::query()
            ->published()
            ->orderBy('slug')
            ->get()
            ->map(fn (SitePage $page) => [
                'slug' => $page->slug,
                'heading' => $page->heading,
                'intro' => $page->intro,
                'url' => method_exists($page, 'publicUrl') ? $page->publicUrl() : url('/'.$page->slug),
            ])
            ->values()
            ->all();
    }

    private function namedUrl(string $name, string $fallback, mixed $parameters = []): string
    {
        return Route::has($name) ? route($name, $parameters) : url($fallback);
    }

    /**
     * @param  array<string, mixed>|Collection<int, mixed>  $payload
     */
    private function json(array|Collection $payload): JsonResponse
    {
        return response()->json($payload, 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
