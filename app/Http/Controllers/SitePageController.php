<?php

namespace App\Http\Controllers;

use App\Models\SitePage;
use App\Support\SafeHtml;
use App\Support\SiteCopy;
use Illuminate\View\View;

class SitePageController extends Controller
{
    public function about(SiteCopy $copy): View
    {
        return $this->system('about', 'pages.about', 'pages.about.heading', 'pages.about.lead', $copy);
    }

    public function howItWorks(SiteCopy $copy): View
    {
        return $this->system('how-it-works', 'pages.how-it-works', 'pages.how.heading', 'pages.how.lead', $copy);
    }

    public function help(SiteCopy $copy): View
    {
        return $this->system('help', 'pages.help', 'pages.help.heading', 'pages.help.lead', $copy);
    }

    public function terms(SiteCopy $copy): View
    {
        return $this->legal('terms', 'pages.terms', $copy);
    }

    public function privacy(SiteCopy $copy): View
    {
        return $this->legal('privacy', 'pages.privacy', $copy);
    }

    public function cookies(SiteCopy $copy): View
    {
        return $this->legal('cookies', 'pages.cookies', $copy);
    }

    public function cancellation(SiteCopy $copy): View
    {
        return $this->legal('cancellation', 'pages.cancellation', $copy);
    }

    public function medicalDisclaimer(SiteCopy $copy): View
    {
        return $this->legal('disclaimer', 'pages.disclaimer', $copy);
    }

    public function accessibility(SiteCopy $copy): View
    {
        return $this->legal('accessibility', 'pages.accessibility', $copy);
    }

    public function helpTopic(string $topic): View
    {
        $items = __('pages.help.items');
        abort_unless(is_array($items) && array_key_exists($topic, $items), 404);

        $item = $items[$topic];

        return view('pages.legal', [
            'heading' => $item['title'],
            'body' => $item['body'],
        ]);
    }

    public function show(SitePage $sitePage): View
    {
        return view('pages.cms', [
            'page' => $sitePage,
            'heading' => $sitePage->heading,
            'intro' => $sitePage->intro,
            'bodyHtml' => $sitePage->renderedBody(),
        ]);
    }

    private function system(string $slug, string $view, string $headingKey, string $leadKey, SiteCopy $copy): View
    {
        $page = $copy->page($slug);
        $heading = $copy->heading($slug, $headingKey);
        $intro = $copy->intro($slug, $leadKey);
        $body = $copy->body($slug);

        if ($body && ! SafeHtml::isEmpty($body)) {
            return view('pages.cms', [
                'page' => $page,
                'heading' => $heading,
                'intro' => $intro,
                'bodyHtml' => SafeHtml::render($body),
            ]);
        }

        return view($view, [
            'heading' => $heading,
            'lead' => $intro,
        ]);
    }

    private function legal(string $slug, string $langKey, SiteCopy $copy): View
    {
        $page = $copy->page($slug);
        $heading = $copy->heading($slug, $langKey.'.heading');
        $body = $copy->body($slug);

        if ($body && ! SafeHtml::isEmpty($body)) {
            return view('pages.cms', [
                'page' => $page,
                'heading' => $heading,
                'intro' => $page?->intro,
                'bodyHtml' => SafeHtml::render($body),
            ]);
        }

        return view('pages.legal', [
            'heading' => $heading,
            'body' => __($langKey.'.body'),
        ]);
    }
}
