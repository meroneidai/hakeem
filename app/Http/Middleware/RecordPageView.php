<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class RecordPageView
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldRecord($request, $response)) {
            return $response;
        }

        PageView::query()->create([
            'path' => '/'.ltrim($request->path(), '/'),
            'route_name' => $request->route()?->getName(),
            'user_id' => $request->user()?->id,
            'ip_hash' => hash('sha256', $request->ip().config('app.key')),
            'referrer' => $request->headers->get('referer'),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'created_at' => now(),
        ]);

        return $response;
    }

    private function shouldRecord(Request $request, Response $response): bool
    {
        if (! Schema::hasTable('page_views')) {
            return false;
        }

        if (! $request->isMethod('GET') || $response->getStatusCode() >= 400) {
            return false;
        }

        if ($request->ajax() || $request->expectsJson()) {
            return false;
        }

        $path = $request->path();

        foreach (['admin', 'clinic', 'api', 'livewire', 'up', 'sitemap.xml', 'robots.txt'] as $skip) {
            if ($path === $skip || str_starts_with($path, $skip.'/')) {
                return false;
            }
        }

        return true;
    }
}
