<?php

namespace App\Http\Controllers;

use App\Models\InAppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->inAppNotifications()
            ->latest()
            ->limit(20)
            ->get(['id', 'event', 'title', 'body', 'url', 'read_at', 'created_at']);

        return response()->json([
            'unread' => $request->user()->inAppNotifications()->whereNull('read_at')->count(),
            'notifications' => $notifications,
        ]);
    }

    public function update(Request $request, InAppNotification $notification): JsonResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 404);

        $notification->markRead();

        return response()->json(['ok' => true]);
    }
}
