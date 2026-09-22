<?php

namespace App\Http\Middleware;

use App\Enums\ClinicModule;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClinicModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $clinic = $request->attributes->get('clinic');
        $enum = ClinicModule::tryFrom($module);

        abort_unless($clinic && $enum && $clinic->hasModule($enum), 404);

        return $next($request);
    }
}
