<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Support\Audit;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

/**
 * Per-event channel matrix (md_files/05 §3-4). The in-app notification centre is
 * always on and therefore not configurable here.
 */
class NotificationSettingsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManagePaymentSettings->value];
    }

    public function edit(Settings $settings): View
    {
        return view('admin.notifications.edit', [
            'matrix' => $settings->get('notifications.channels', []) ?: [],
        ]);
    }

    public function update(Request $request, Settings $settings): RedirectResponse
    {
        $events = config('hakeem.notification_events');
        $channels = config('hakeem.notification_channels');

        $submitted = $request->input('matrix', []);
        $matrix = [];

        foreach ($events as $event) {
            $matrix[$event] = collect($channels)
                ->filter(fn (string $channel) => (bool) ($submitted[$event][$channel] ?? false))
                ->values()
                ->all();
        }

        $settings->set('notifications.channels', $matrix, 'notifications');

        Audit::log('settings.notifications_updated', changes: ['matrix' => $matrix]);

        return back()->with('status', __('admin.notifications.saved'));
    }
}
