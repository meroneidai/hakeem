<?php

namespace App\Http\Controllers;

use App\Support\Deeplink;
use Illuminate\Http\JsonResponse;

class DeeplinkController extends Controller
{
    public function appleAppSiteAssociation(): JsonResponse
    {
        $teamId = (string) config('hakeem.deeplinks.ios_team_id');
        $bundleId = (string) config('hakeem.deeplinks.ios_bundle_id');
        $appId = filled($teamId) && filled($bundleId) ? $teamId.'.'.$bundleId : $bundleId;

        $details = [];

        if (filled($appId)) {
            $details[] = [
                'appID' => $appId,
                'paths' => [
                    '/clinics/*',
                    '/offers/*',
                    '/doctors/*',
                    '/labs/*',
                    '/',
                ],
            ];
        }

        return response()->json([
            'applinks' => [
                'apps' => [],
                'details' => $details,
            ],
            'webcredentials' => [
                'apps' => filled($appId) ? [$appId] : [],
            ],
        ])->header('Content-Type', 'application/json');
    }

    public function assetLinks(): JsonResponse
    {
        $package = (string) config('hakeem.deeplinks.android_package');
        $fingerprints = array_values(array_filter((array) config('hakeem.deeplinks.android_sha256', [])));

        $links = [];

        if (filled($package)) {
            $target = [
                'namespace' => 'android_app',
                'package_name' => $package,
            ];

            if ($fingerprints !== []) {
                $target['sha256_cert_fingerprints'] = $fingerprints;
            }

            $links[] = [
                'relation' => ['delegate_permission/common.handle_all_urls'],
                'target' => $target,
            ];
        }

        return response()->json($links)->header('Content-Type', 'application/json');
    }

    public function appLinks(): JsonResponse
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return response()->json([
            'scheme' => Deeplink::scheme(),
            'host' => $host,
            'prefixes' => [
                Deeplink::scheme().'://',
                rtrim((string) config('app.url'), '/'),
            ],
            'screens' => Deeplink::screens(),
        ]);
    }
}
