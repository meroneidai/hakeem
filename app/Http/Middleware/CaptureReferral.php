<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        $code = strtoupper(trim((string) $request->query('ref', '')));

        if ($code !== '' && preg_match('/^[A-Z0-9]{4,16}$/', $code) === 1) {
            $request->session()->put('referral_code', $code);
        }

        return $next($request);
    }
}
