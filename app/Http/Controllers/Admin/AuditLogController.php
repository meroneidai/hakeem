<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class AuditLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ViewAuditLog->value];
    }

    public function index(Request $request): View
    {
        $logs = AuditLog::with('user')
            ->when($request->string('action')->value(), fn ($query, $action) => $query->whereLike('action', "{$action}%"))
            ->latest('created_at')
            ->paginate(40)
            ->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'actions' => AuditLog::distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
