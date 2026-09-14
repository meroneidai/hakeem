<?php

namespace App\Http\Middleware;

use App\Support\ClinicAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the clinic this request operates on and rejects anyone who does not
 * belong to it. Cross-clinic IDs 404 so one tenant cannot confirm another exists.
 */
class EnsureClinicStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && $user->is_active && $user->isClinicStaff(), 403);

        $clinic = $user->defaultClinic();

        abort_unless($clinic, 403);

        $access = new ClinicAccess($user, $clinic);

        abort_unless($access->canView(), 403);

        $request->attributes->set('clinic', $clinic);
        $request->attributes->set('clinicAccess', $access);

        view()->share('currentClinic', $clinic);
        view()->share('clinicAccess', $access);

        return $next($request);
    }
}
