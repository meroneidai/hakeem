<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\AgentConversation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class AgentConversationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageSupportTickets->value];
    }

    public function index(Request $request): View
    {
        $conversations = AgentConversation::query()
            ->with(['patient:id,name,phone', 'messages' => fn ($query) => $query->latest('id')->limit(1)])
            ->when($request->string('channel')->trim()->value(), fn ($query, $channel) => $query->where('channel', $channel))
            ->when($request->string('q')->trim()->value(), function ($query, $term) {
                $query->where(function ($inner) use ($term) {
                    $inner->whereLike('visitor_name', "%{$term}%")
                        ->orWhereHas('patient', fn ($patient) => $patient
                            ->whereLike('name', "%{$term}%")
                            ->orWhereLike('phone', "%{$term}%"))
                        ->orWhereHas('messages', fn ($messages) => $messages->whereLike('body', "%{$term}%"));
                });
            })
            ->when($request->filled('from'), fn ($query) => $query->whereDate('last_message_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('last_message_at', '<=', $request->date('to')))
            ->orderByDesc('last_message_at')
            ->orderByDesc('started_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.agent-conversations.index', [
            'conversations' => $conversations,
            'filters' => $request->only(['channel', 'q', 'from', 'to']),
        ]);
    }

    public function show(AgentConversation $agentConversation): View
    {
        $agentConversation->load([
            'patient:id,name,phone,email',
            'messages' => fn ($query) => $query->orderBy('id'),
        ]);

        return view('admin.agent-conversations.show', [
            'conversation' => $agentConversation,
        ]);
    }
}
