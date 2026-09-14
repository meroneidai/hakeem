<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate-keeps the /admin area. Access control is enforced server-side on every
 * request — hidden navigation is never the only barrier (md_files/04 §2).
 */
class EnsureInternalStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && $user->is_active && $user->hasPermission(Permission::ViewAdminPanel), 403);

        return $next($request);
    }
}
