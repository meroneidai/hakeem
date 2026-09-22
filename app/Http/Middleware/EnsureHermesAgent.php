<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHermesAgent
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.hermes.key');
        $provided = (string) $request->header('X-Hermes-Key', '');

        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json([
                'message' => __('agent.api.unauthorized'),
                'code' => 'hermes_key_required',
            ], 401);
        }

        $locale = $request->query('locale', $request->header('X-Locale'));

        if (is_string($locale) && array_key_exists($locale, config('hakeem.locales'))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
