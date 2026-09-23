<?php

namespace App\Support;

class Deeplink
{
    /**
     * HTTPS universal link that opens the app when installed, otherwise the web page.
     */
    public static function web(string $name, mixed $parameters = []): string
    {
        return route($name, $parameters);
    }

    /**
     * Custom scheme used by the native shell (Expo / React Native).
     */
    public static function app(string $path): string
    {
        $path = ltrim($path, '/');

        return static::scheme().'://'.($path === '' ? '' : $path);
    }

    /**
     * @return array{web: string, app: string, path: string}
     */
    public static function forRoute(string $name, mixed $parameters = []): array
    {
        $web = static::web($name, $parameters);
        $path = parse_url($web, PHP_URL_PATH) ?: '/';

        return [
            'web' => $web,
            'app' => static::app($path),
            'path' => $path,
        ];
    }

    public static function scheme(): string
    {
        return (string) config('hakeem.deeplinks.scheme', 'hakeem');
    }

    /**
     * Screens the mobile app should register for universal links and the custom scheme.
     *
     * @return array<string, string>
     */
    public static function screens(): array
    {
        return [
            'clinic' => '/clinics/:slug',
            'offer' => '/offers/:slug',
            'doctor' => '/doctors/:slug',
            'labTest' => '/labs/tests/:slug',
            'labPackage' => '/labs/packages/:slug',
            'home' => '/',
        ];
    }
}
