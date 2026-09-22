<?php

namespace App\Http\Controllers;

use App\Enums\OfferCategory;
use App\Models\Governorate;
use App\Models\Promotion;
use App\Services\PaymentOptions;
use App\Support\SearchQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfferDirectoryController extends Controller
{
    public function __construct(private PaymentOptions $payments) {}

    public function index(Request $request): View
    {
        $category = $request->string('category')->value();

        $term = $request->string('q')->trim()->value() ?: null;
        $governorate = $request->string('governorate')->trim()->value() ?: null;
        $city = $request->string('city')->trim()->value() ?: null;
        $maxPriceRaw = $request->input('max_price');
        $maxPrice = is_numeric($maxPriceRaw) && (int) $maxPriceRaw > 0 ? (int) $maxPriceRaw : null;

        $offers = Promotion::query()
            ->running()
            ->with([
                'specialty',
                'clinic' => fn ($clinic) => $clinic->withRatings()->with('primaryAddress.city'),
            ])
            ->when($category, fn ($query) => $query->where('category', $category))
            ->when($term, fn ($query) => SearchQuery::constrain($query, $term, function ($inner, $token) {
                $inner->whereLike('title_ar', "%{$token}%")->orWhereLike('title_en', "%{$token}%")
                    ->orWhereLike('includes_ar', "%{$token}%")->orWhereLike('includes_en', "%{$token}%");
            }))
            ->when($governorate, fn ($query, $slug) => $query->whereHas(
                'clinic.addresses.city.governorate',
                fn ($governorateQuery) => $governorateQuery->where('slug', $slug)
            ))
            ->when($city, fn ($query, $slug) => $query->whereHas(
                'clinic.addresses.city',
                fn ($cityQuery) => $cityQuery->where('slug', $slug)
            ))
            ->when($maxPrice, fn ($query) => $query->where('offer_price', '<=', $maxPrice))
            ->latest('starts_at')
            ->paginate(12)
            ->withQueryString();

        return view('offers.index', [
            'offers' => $offers,
            'categories' => OfferCategory::cases(),
            'governorates' => Governorate::query()->active()->ordered()->with(['cities' => fn ($q) => $q->active()->ordered()])->get(),
            'activeCategory' => $category,
            'maxPrice' => $maxPrice,
        ]);
    }

    public function show(Promotion $offer): View
    {
        abort_unless($offer->isRunning(), 404);

        $offer->increment('views_count');
        $offer->load([
            'clinic.addresses' => fn ($query) => $query->active()->with('city'),
            'clinic.doctors' => fn ($query) => $query->where('doctors.is_active', true),
            'specialty',
            'serviceType',
        ]);

        $paymentModes = $offer->clinic
            ? $this->payments->allowedModes($offer->clinic)
            : [];

        return view('offers.show', [
            'offer' => $offer,
            'paymentModes' => $paymentModes,
            'defaultPaymentMode' => $offer->clinic
                ? $this->payments->defaultMode($offer->clinic)
                : null,
            'gatewayReady' => $this->payments->isGatewayConfigured(),
        ]);
    }
}
