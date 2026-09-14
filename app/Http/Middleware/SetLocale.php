<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('hakeem.locales'));

        $locale = $request->session()->get('locale')
            ?? $request->user()?->preferred_language
            ?? config('hakeem.default_locale');

        if (! in_array($locale, $supported, true)) {
            $locale = config('hakeem.default_locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
