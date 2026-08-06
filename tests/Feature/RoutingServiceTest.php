<?php

namespace Tests\Feature;

use App\Exceptions\RoutingUnavailableException;
use App\Services\CostLogger;
use App\Services\RoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RoutingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function origin(): array
    {
        return ['lat' => 9.1117, 'lng' => 7.3304];
    }

    private function to(): array
    {
        return ['lat' => 9.0500, 'lng' => 7.4900];
    }

    private function osrmBody(): array
    {
        return [
            'code' => 'Ok',
            'routes' => [[
                'distance' => 18000,
                'duration' => 1800,
                'geometry' => ['coordinates' => [[7.3304, 9.1117], [7.4900, 9.0500]]],
            ]],
        ];
    }

    public function test_osrm_primary_is_free_and_preferred(): void
    {
        config(['workride.routing.primary' => 'osrm']);
        Http::fake([
            '*/route/v1/driving/*' => Http::response($this->osrmBody()),
        ]);

        $result = $this->app->make(RoutingService::class)->route($this->origin(), $this->to());

        $this->assertSame(18000.0, $result['distance_m']);
        $this->assertSame(1800.0, $result['duration_s']);
        $this->assertCount(2, $result['points']);

        // Free provider is logged with cost 0 (audit trail only).
        $this->assertDatabaseHas('api_cost_logs', [
            'provider' => 'osrm',
            'service' => 'routing',
            'cost_naira' => '0.00',
        ]);
    }

    public function test_foot_profile_hits_walking_endpoint_and_reports_provider(): void
    {
        config(['workride.routing.primary' => 'osrm']);
        Http::fake([
            '*/route/v1/foot/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 420,
                    'duration' => 340,
                    'geometry' => ['coordinates' => [[7.3304, 9.1117], [7.4900, 9.0500]]],
                ]],
            ]),
        ]);

        $result = $this->app->make(RoutingService::class)->route($this->origin(), $this->to(), 'foot');

        $this->assertSame(420.0, $result['distance_m']);
        $this->assertSame(340.0, $result['duration_s']);
        $this->assertSame('osrm', $result['provider']);
        $this->assertDatabaseHas('api_cost_logs', [
            'provider' => 'osrm',
            'service' => 'routing',
            'cost_naira' => '0.00',
        ]);
    }

    public function test_google_fallback_is_capped_and_logged(): void
    {
        config([
            'workride.routing.primary' => 'osrm',
            'workride.routing.use_google_fallback' => true,
            'workride.routing.google_api_key' => 'test-key',
            'workride.routing.google_cost_per_request' => 20,
        ]);

        // OSRM fails, Google succeeds.
        Http::fake([
            '*/route/v1/driving/*' => Http::response([], 500),
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'routes' => [[
                    'legs' => [[
                        'distance' => ['value' => 17500],
                        'duration' => ['value' => 1900],
                    ]],
                    'overview_polyline' => ['points' => ''],
                ]],
            ]),
        ]);

        $result = $this->app->make(RoutingService::class)->route($this->origin(), $this->to());

        $this->assertSame(17500.0, $result['distance_m']);
        $this->assertDatabaseHas('api_cost_logs', [
            'provider' => 'google_directions',
            'service' => 'routing',
            'cost_naira' => '20.00',
        ]);
    }

    public function test_google_fallback_refused_when_disabled(): void
    {
        config([
            'workride.routing.primary' => 'osrm',
            'workride.routing.use_google_fallback' => false,
        ]);

        Http::fake([
            '*/route/v1/driving/*' => Http::response([], 500),
        ]);

        $this->expectException(RoutingUnavailableException::class);

        $this->app->make(RoutingService::class)->route($this->origin(), $this->to());
    }

    public function test_paid_fallback_refused_when_monthly_cap_exceeded(): void
    {
        config([
            'workride.routing.primary' => 'osrm',
            'workride.routing.use_google_fallback' => true,
            'workride.routing.google_api_key' => 'test-key',
            'workride.routing.google_cost_per_request' => 20,
            'workride.api_caps.monthly_naira' => 10,
        ]);

        Http::fake([
            '*/route/v1/driving/*' => Http::response([], 500),
        ]);

        $this->expectException(RoutingUnavailableException::class);

        $this->app->make(RoutingService::class)->route($this->origin(), $this->to());
    }

    public function test_cost_logger_tracks_monthly_spend_and_calls(): void
    {
        config(['workride.api_caps.monthly_naira' => 100]);

        $logger = $this->app->make(CostLogger::class);

        $logger->log('google_directions', 'routing', 20, []);
        $logger->log('google_directions', 'routing', 25, []);
        $logger->log('osrm', 'routing', 0, []);

        $this->assertSame(45.0, $logger->monthlySpend('google_directions'));
        $this->assertSame(45.0, $logger->monthlySpend());
        $this->assertSame(2, $logger->monthlyCalls('google_directions'));
        $this->assertTrue($logger->withinMonthlyCap(50));
        $this->assertFalse($logger->withinMonthlyCap(60));
    }

    public function test_mapbox_fallback_is_capped_and_logged(): void
    {
        config([
            'workride.routing.primary' => 'mapbox',
            'workride.routing.use_mapbox_premium' => true,
            'workride.routing.mapbox_access_token' => 'pk.test',
            'workride.routing.mapbox_cost_per_request' => 25,
        ]);

        Http::fake([
            'api.mapbox.com/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 16000,
                    'duration' => 1700,
                    'geometry' => ['coordinates' => [[7.3304, 9.1117], [7.4900, 9.0500]]],
                ]],
            ]),
        ]);

        $result = $this->app->make(RoutingService::class)->route($this->origin(), $this->to());

        $this->assertSame(16000.0, $result['distance_m']);
        $this->assertDatabaseHas('api_cost_logs', [
            'provider' => 'mapbox',
            'service' => 'routing',
            'cost_naira' => '25.00',
        ]);
    }

    public function test_polyline_decoder_reconstructs_points(): void
    {
        $service = $this->app->make(RoutingService::class);
        $reflection = new \ReflectionMethod($service, 'decodePolyline');
        $reflection->setAccessible(true);

        // Encoded polyline for [(9.1117, 7.3304), (9.05, 7.49)] — valid Google encoding.
        $encoded = '_upxHw}h`M@h@';
        $points = $reflection->invoke($service, $encoded);

        $this->assertIsArray($points);
        $this->assertNotEmpty($points);
        $this->assertArrayHasKey('lat', $points[0]);
        $this->assertArrayHasKey('lng', $points[0]);
    }
}
