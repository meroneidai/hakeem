<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\ClinicModule;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PublicImage;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use ResolvesClinicContext;

    public function edit(Request $request): View
    {
        $this->authorizeManage($request);

        return view('clinic.profile.edit', [
            'clinic' => $this->clinic($request),
            'modules' => ClinicModule::selectable(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $clinic = $this->clinic($request);

        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:160'],
            'name_en' => ['required', 'string', 'max:160'],
            'description_ar' => ['nullable', 'string', 'max:4000'],
            'description_en' => ['nullable', 'string', 'max:4000'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'logo' => PublicImage::rules(),
            'modules' => ['nullable', 'array'],
            'modules.*' => [Rule::enum(ClinicModule::class)],
        ]);

        $validated['phone'] = User::normalizePhone($validated['phone']);
        $validated['slug'] = UniqueSlug::for($validated['name_en'], 'clinics', 'slug', $clinic->id);
        unset($validated['logo']);
        $validated['logo_path'] = PublicImage::store($request, 'logo', 'clinics/'.$clinic->id, $clinic->logo_path);
        $validated['modules'] = ClinicModule::normalize($request->input('modules', []));

        $clinic->update($validated);

        return back()->with('status', __('common.updated_successfully'));
    }
}
