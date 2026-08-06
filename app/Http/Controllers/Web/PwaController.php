<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

/**
 * PWA shell — Web App Manifest + service worker.
 *
 * Keeps the rider PWA installable ("Add to Home Screen") and able to render
 * the dashboard shell offline on a throttled 2G connection, per the guide.
 */
class PwaController extends Controller
{
    public function manifest()
    {
        $appUrl = rtrim(url('/'), '/');

        return response()->json([
            'name' => 'WorkRide',
            'short_name' => 'WorkRide',
            'description' => 'Community-focused, subsidy-enabled transit intelligence — verified civil servants share rides on fixed corridors.',
            'start_url' => $appUrl.'/go',
            'scope' => $appUrl.'/',
            'display' => 'standalone',
            'background_color' => '#F6F9F6',
            'theme_color' => '#2E7D32',
            'orientation' => 'portrait-primary',
            'lang' => 'en',
            'icons' => [
                [
                    'src' => $appUrl.'/pwa/icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $appUrl.'/pwa/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json',
        ]);
    }

    public function serviceWorker()
    {
        $content = <<<'JS'
const CACHE = 'workride-v1';
    const SHELL = ['/', '/go', '/dashboard', '/trips', '/bookings', '/impact', '/road/map', '/manifest.json', '/offline'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(SHELL))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

// Stale-while-revalidate: serve cached shell instantly, refresh in background.
// Read-only caching — never cache POSTs, and never touch the FOR-UPDATE seat locks.
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    if (url.origin !== self.location.origin) return;

    event.respondWith(
        caches.open(CACHE).then(async (cache) => {
            const cached = await cache.match(event.request);

            const network = fetch(event.request)
                .then((response) => {
                    if (response && response.status === 200) cache.put(event.request, response.clone());
                    return response;
                })
                .catch(() => {
                    // Navigation offline → offline board page (corridor chips + retry).
                    if (event.request.mode === 'navigate') return caches.match('/offline');
                    return cached;
                });

            return cached || network;
        })
    );
});

// Let a newly installed service worker take control right away.
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') self.skipWaiting();
});
JS;

        return response($content, 200, [
            'Content-Type' => 'application/javascript',
            'Service-Worker-Allowed' => '/',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
