<?php

namespace App\Http\Controllers;

use App\Support\SitemapBuilder;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(SitemapBuilder $sitemap): Response
    {
        $xml = view('sitemaps.urlset', [
            'urls' => $sitemap->urls(),
        ])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /search',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /book',
            'Disallow: /appointments',
            'Disallow: /admin',
            'Disallow: /clinic',
            'Disallow: /labs/cart',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
