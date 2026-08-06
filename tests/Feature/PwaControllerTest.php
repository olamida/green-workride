<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaControllerTest extends TestCase
{
    public function test_manifest_returns_pwa_json(): void
    {
        $this->get('/manifest.json')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonFragment(['name' => 'WorkRide'])
            ->assertJsonFragment(['start_url' => url('/go')])
            ->assertJsonFragment(['theme_color' => '#2E7D32'])
            ->assertJsonFragment(['display' => 'standalone']);
    }

    public function test_manifest_includes_icons(): void
    {
        $manifest = $this->get('/manifest.json')->json();

        $this->assertCount(2, $manifest['icons']);
        $this->assertTrue(collect($manifest['icons'])->contains('sizes', '192x192'));
        $this->assertTrue(collect($manifest['icons'])->contains('sizes', '512x512'));
        $this->assertTrue(collect($manifest['icons'])->contains('src', url('/pwa/icon-192.png')));
        $this->assertTrue(collect($manifest['icons'])->contains('type', 'image/png'));
    }

    public function test_service_worker_returns_js(): void
    {
        $this->get('/sw.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript')
            ->assertSee('workride-v1')
            ->assertSee('Stale-while-revalidate');
    }

    public function test_pwa_icons_exist_on_disk(): void
    {
        $this->assertFileExists(public_path('pwa/icon-192.png'));
        $this->assertFileExists(public_path('pwa/icon-512.png'));
        $this->assertGreaterThan(500, filesize(public_path('pwa/icon-192.png')));
    }
}
