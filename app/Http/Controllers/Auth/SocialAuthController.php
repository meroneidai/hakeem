<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class SocialAuthController extends Controller
{
    /**
     * @var list<string>
     */
    private const PROVIDERS = ['google', 'facebook', 'apple'];

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        if (! filled(config("services.{$provider}.client_id"))) {
            return back()->with('error', __('auth.social_not_configured', ['provider' => $provider]));
        }

        return back()->with('status', __('auth.social_ready', ['provider' => $provider]));
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        return redirect()->route('login')->with('error', __('auth.social_not_configured', ['provider' => $provider]));
    }
}
