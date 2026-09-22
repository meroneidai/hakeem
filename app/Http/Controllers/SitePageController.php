<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SitePageController extends Controller
{
    public function about(): View
    {
        return view('pages.about');
    }

    public function howItWorks(): View
    {
        return view('pages.how-it-works');
    }

    public function help(): View
    {
        return view('pages.help');
    }

    public function terms(): View
    {
        return view('pages.legal', [
            'heading' => __('pages.terms.heading'),
            'body' => __('pages.terms.body'),
        ]);
    }

    public function privacy(): View
    {
        return view('pages.legal', [
            'heading' => __('pages.privacy.heading'),
            'body' => __('pages.privacy.body'),
        ]);
    }

    public function cookies(): View
    {
        return view('pages.legal', [
            'heading' => __('pages.cookies.heading'),
            'body' => __('pages.cookies.body'),
        ]);
    }

    public function cancellation(): View
    {
        return view('pages.legal', [
            'heading' => __('pages.cancellation.heading'),
            'body' => __('pages.cancellation.body'),
        ]);
    }

    public function medicalDisclaimer(): View
    {
        return view('pages.legal', [
            'heading' => __('pages.disclaimer.heading'),
            'body' => __('pages.disclaimer.body'),
        ]);
    }
}
