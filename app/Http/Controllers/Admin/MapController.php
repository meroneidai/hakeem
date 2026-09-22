<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\ClinicAddress;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class MapController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ModerateClinics->value];
    }

    public function __invoke(): View
    {
        $pins = ClinicAddress::query()
            ->with(['clinic', 'city'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereHas('clinic')
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(fn (ClinicAddress $address) => [
                'id' => $address->id,
                'name' => $address->clinic?->name,
                'branch' => $address->displayName(),
                'lat' => (float) $address->latitude,
                'lng' => (float) $address->longitude,
                'url' => $address->clinic ? route('admin.clinics.show', $address->clinic) : null,
                'status' => $address->clinic?->verification_status?->value,
            ]);

        return view('admin.map.index', compact('pins'));
    }
}
