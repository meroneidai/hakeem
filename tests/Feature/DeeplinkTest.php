<?php

namespace Tests\Feature;

use App\Support\Deeplink;
use Tests\TestCase;

class DeeplinkTest extends TestCase
{
    public function test_well_known_files_describe_ios_and_android_app_links(): void
    {
        config([
            'hakeem.deeplinks.scheme' => 'hakeem',
            'hakeem.deeplinks.ios_team_id' => 'ABCDE12345',
            'hakeem.deeplinks.ios_bundle_id' => 'eg.hakeem.app',
            'hakeem.deeplinks.android_package' => 'eg.hakeem',
            'hakeem.deeplinks.android_sha256' => ['AA:BB:CC'],
        ]);

        $this->get('/.well-known/apple-app-site-association')
            ->assertOk()
            ->assertJsonPath('applinks.details.0.appID', 'ABCDE12345.eg.hakeem.app')
            ->assertJsonPath('applinks.details.0.paths.0', '/clinics/*')
            ->assertJsonPath('applinks.details.0.paths.1', '/offers/*');

        $this->get('/.well-known/assetlinks.json')
            ->assertOk()
            ->assertJsonPath('0.target.package_name', 'eg.hakeem')
            ->assertJsonPath('0.target.sha256_cert_fingerprints.0', 'AA:BB:CC');

        $this->get('/.well-known/hakeem-app-links.json')
            ->assertOk()
            ->assertJsonPath('scheme', 'hakeem')
            ->assertJsonPath('screens.clinic', '/clinics/:slug')
            ->assertJsonPath('screens.offer', '/offers/:slug');
    }

    public function test_api_exposes_the_same_app_link_manifest(): void
    {
        $this->getJson('/api/v1/app-links')
            ->assertOk()
            ->assertJsonPath('scheme', 'hakeem')
            ->assertJsonPath('screens.offer', '/offers/:slug');
    }

    public function test_app_scheme_mirrors_the_web_path(): void
    {
        $this->assertSame('hakeem://clinics/nile-clinic', Deeplink::app('/clinics/nile-clinic'));
    }
}
