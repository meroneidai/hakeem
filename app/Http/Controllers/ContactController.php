<?php

namespace App\Http\Controllers;

use App\Models\ContactInquiry;
use App\Models\User;
use App\Support\SeoDocument;
use App\Support\SiteCopy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(SiteCopy $copy): View
    {
        return view('pages.contact', [
            'jsonLd' => [SeoDocument::contactPageGraph()],
            'heading' => $copy->heading('contact', 'pages.contact.heading'),
            'lead' => $copy->intro('contact', 'pages.contact.lead'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'audience' => ['required', Rule::in(ContactInquiry::audiences())],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $validated['phone'] = User::normalizePhone($validated['phone']);

        ContactInquiry::query()->create($validated);

        return back()->with('status', __('pages.contact.sent'));
    }
}
