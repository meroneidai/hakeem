<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportTicketController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageSupportTickets->value];
    }

    public function index(Request $request): View
    {
        $tickets = SupportTicket::with(['openedBy', 'assignedAgent'])
            ->when($request->string('status')->value(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->string('priority')->value(), fn ($query, $priority) => $query->where('priority', $priority))
            ->when($request->boolean('mine'), fn ($query) => $query->where('assigned_agent_id', $request->user()->id))
            ->orderByRaw("case when status = 'open' then 0 when status = 'in_progress' then 1 else 2 end")
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.support.index', [
            'tickets' => $tickets,
            'counts' => SupportTicket::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(SupportTicket $ticket): View
    {
        return view('admin.support.show', [
            'ticket' => $ticket->load(['openedBy', 'assignedAgent', 'messages.sender']),
            'agents' => User::whereHas('roles', fn ($query) => $query->whereIn('name', [
                RoleName::PlatformAdmin->value,
                RoleName::SupportAgent->value,
            ]))->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(SupportTicket::STATUSES)],
            'priority' => ['required', Rule::in(SupportTicket::PRIORITIES)],
            'category' => ['required', Rule::in(SupportTicket::CATEGORIES)],
            'assigned_agent_id' => ['nullable', 'exists:users,id'],
        ]);

        if (in_array($data['status'], ['resolved', 'closed'], true)) {
            $data['resolved_at'] = $ticket->resolved_at ?? now();
        } else {
            $data['resolved_at'] = null;
        }

        $before = $ticket->getOriginal();
        $ticket->update($data);

        Audit::updated($ticket, $before);

        return back()->with('status', __('common.updated_successfully'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'is_internal_note' => ['boolean'],
        ]);

        $ticket->messages()->create([
            'sender_user_id' => $request->user()->id,
            'body' => $data['body'],
            'is_internal_note' => $request->boolean('is_internal_note'),
            'sent_at' => now(),
        ]);

        $updates = [];

        // A customer-visible reply starts the SLA clock and moves the ticket along.
        if (! $request->boolean('is_internal_note')) {
            $updates['first_response_at'] = $ticket->first_response_at ?? now();

            if ($ticket->status === 'open') {
                $updates['status'] = 'in_progress';
            }
        }

        $updates['assigned_agent_id'] = $ticket->assigned_agent_id ?? $request->user()->id;

        $ticket->update($updates);

        Audit::log('support_ticket.replied', $ticket, ['internal' => $request->boolean('is_internal_note')]);

        return back()->with('status', __('common.updated_successfully'));
    }
}
