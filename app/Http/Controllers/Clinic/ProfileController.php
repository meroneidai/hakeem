<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use ResolvesClinicContext;

    public function edit(Request $request): View
    {
        $this->authorizeManage($request);

        return view('clinic.profile.edit', [
            'clinic' => $this->clinic($request),
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
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $validated['phone'] = User::normalizePhone($validated['phone']);
        $validated['slug'] = UniqueSlug::for($validated['name_en'], 'clinics', 'slug', $clinic->id);

        if ($request->hasFile('logo')) {
            if ($clinic->logo_path) {
                Storage::disk('public')->delete($clinic->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('clinics/'.$clinic->id, 'public');
        }

        unset($validated['logo']);

        $clinic->update($validated);

        return back()->with('status', __('common.updated_successfully'));
    }
}
