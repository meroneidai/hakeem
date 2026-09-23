<?php

namespace App\Http\Controllers\Api\Agent\V1;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\AgentCustomerAuthenticator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SupportTicketController extends Controller
{
    public function store(Request $request, AgentCustomerAuthenticator $customers): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:32'],
            'subject' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:4000'],
            'category' => ['nullable', Rule::in(SupportTicket::CATEGORIES)],
            'channel' => ['nullable', Rule::in(['chat', 'whatsapp', 'email', 'phone'])],
        ]);

        $user = $customers->optional($request) ?? $this->findOrCreatePatient($data['phone'], $data['name']);

        $ticket = SupportTicket::query()->create([
            'opened_by_user_id' => $user->id,
            'channel' => $data['channel'] ?? 'chat',
            'subject' => $data['subject'],
            'category' => $data['category'] ?? 'other',
            'status' => 'open',
            'priority' => 'normal',
        ]);

        $ticket->messages()->create([
            'sender_user_id' => $user->id,
            'body' => $data['body'],
            'is_internal_note' => false,
            'sent_at' => now(),
        ]);

        return response()->json([
            'reference' => $ticket->reference,
            'status' => $ticket->status,
            'message' => __('pages.complaints.sent', ['reference' => $ticket->reference]),
        ], 201);
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
