<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\ExceptionReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class ExceptionReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ViewErrorReports->value];
    }

    public function index(Request $request): View
    {
        $reports = ExceptionReport::query()
            ->with('user')
            ->when($request->string('q')->trim()->value(), function ($query, $term) {
                $query->where(fn ($inner) => $inner
                    ->whereLike('message', "%{$term}%")
                    ->orWhereLike('exception_class', "%{$term}%")
                    ->orWhereLike('url', "%{$term}%"));
            })
            ->when($request->string('status')->value() === 'open', fn ($query) => $query->whereNull('resolved_at'))
            ->when($request->string('status')->value() === 'resolved', fn ($query) => $query->whereNotNull('resolved_at'))
            ->latest('last_seen_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.errors.index', compact('reports'));
    }

    public function show(ExceptionReport $error): View
    {
        $error->load('user');

        return view('admin.errors.show', ['report' => $error]);
    }

    public function update(Request $request, ExceptionReport $error): RedirectResponse
    {
        $action = $request->validate([
            'action' => ['required', 'in:resolve,reopen'],
        ])['action'];

        $error->update([
            'resolved_at' => $action === 'resolve' ? now() : null,
        ]);

        return back()->with('status', __('common.updated_successfully'));
    }
}
