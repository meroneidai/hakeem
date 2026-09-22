<?php

namespace App\Http\Controllers;

use App\Models\ContactInquiry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('pages.contact');
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
