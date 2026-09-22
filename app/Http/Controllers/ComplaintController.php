<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function create(): View
    {
        return view('pages.complaints');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:32'],
            'subject' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:4000'],
            'category' => ['nullable', Rule::in(SupportTicket::CATEGORIES)],
        ]);

        $user = $request->user() ?? $this->findOrCreatePatient($data['phone'], $data['name']);

        $ticket = SupportTicket::query()->create([
            'opened_by_user_id' => $user->id,
            'channel' => 'chat',
            'subject' => $data['subject'],
            'category' => $data['category'] ?? 'complaint',
            'status' => 'open',
            'priority' => 'high',
        ]);

        $ticket->messages()->create([
            'sender_user_id' => $user->id,
            'body' => $data['body'],
            'is_internal_note' => false,
            'sent_at' => now(),
        ]);

        return back()->with('status', __('pages.complaints.sent', ['reference' => $ticket->reference]));
    }

    private function findOrCreatePatient(string $phone, string $name): User
    {
        $normalized = User::normalizePhone($phone);

        $user = User::query()->firstOrCreate(
            ['phone' => $normalized],
            [
                'name' => $name,
                'password' => Str::password(16),
                'preferred_language' => app()->getLocale(),
                'is_active' => true,
            ],
        );

        if (! $user->hasRole(RoleName::Patient)) {
            $user->assignRole(RoleName::Patient);
        }

        return $user;
    }
}
