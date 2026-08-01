<?php

namespace Tests\Feature;

use App\Enums\Corridor;
use App\Models\GtfsFeedMeta;
use App\Models\GtfsRoute;
use App\Models\GtfsStop;
use App\Models\Trip;
use App\Services\GtfsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class GtfsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function service(): GtfsService
    {
        return $this->app->make(GtfsService::class);
    }

    private function seedStops(): void
    {
        GtfsStop::create([
            'stop_id' => 'KUB-01',
            'stop_name' => 'Kubwa Junction',
            'stop_lat' => 9.1117,
            'stop_lon' => 7.3304,
            'corridor' => Corridor::KubwaCbd->value,
        ]);
        GtfsStop::create([
            'stop_id' => 'KUB-16',
            'stop_name' => 'Federal Secretariat Gate',
            'stop_lat' => 9.0500,
            'stop_lon' => 7.4900,
            'corridor' => Corridor::KubwaCbd->value,
        ]);
    }

    public function test_generates_a_zip_with_all_seven_gtfs_files(): void
    {
        $this->seedStops();
        Trip::factory()->create(['corridor' => Corridor::KubwaCbd]);

        $stats = $this->service()->generate();

        $this->assertTrue(Storage::disk('public')->exists('gtfs/gtfs.zip'));
        $this->assertArrayHasKey('path', $stats);
        $this->assertGreaterThan(0, $stats['size']);
        $this->assertNotEmpty($stats['hash']);
        $this->assertSame(2, $stats['stops']);
        $this->assertSame(3, $stats['routes']);
        $this->assertSame(1, $stats['trips']);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('public')->path('gtfs/gtfs.zip')));

        foreach (['agency.txt', 'stops.txt', 'routes.txt', 'trips.txt', 'stop_times.txt', 'calendar.txt', 'shapes.txt'] as $file) {
            $this->assertNotFalse($zip->getFromName($file), "Missing $file from feed");
        }

        $zip->close();
    }

    public function test_records_feed_metadata(): void
    {
        $this->seedStops();
        Trip::factory()->create(['corridor' => Corridor::KubwaCbd]);

        $this->service()->generate();

        $meta = GtfsFeedMeta::find(1);

        $this->assertNotNull($meta);
        $this->assertNotNull($meta->last_generated_at);
        $this->assertSame(2, $meta->stops_count);
        $this->assertSame(3, $meta->routes_count);
        $this->assertSame(1, $meta->trips_count);
        $this->assertGreaterThan(0, $meta->file_size);
        $this->assertNotEmpty($meta->feed_hash);
    }

    public function test_routes_are_created_for_every_corridor(): void
    {
        $this->seedStops();

        $this->service()->generate();

        $this->assertSame(3, GtfsRoute::count());

        $routes = $this->readZip('routes.txt');

        $this->assertStringContainsString('KUB-CBD', $routes);
        $this->assertStringContainsString('NYY-IDU', $routes);
        $this->assertStringContainsString('LUG-CBD', $routes);
    }

    public function test_uses_relational_waypoints_over_the_json_column(): void
    {
        $this->seedStops();

        $trip = Trip::factory()->create([
            'corridor' => Corridor::KubwaCbd,
            'waypoints' => [
                ['label' => 'JSON far point', 'lat' => 12.0, 'lng' => 8.0],
                ['label' => 'JSON far point 2', 'lat' => 12.1, 'lng' => 8.1],
            ],
        ]);

        $trip->waypoints()->create(['label' => 'Kubwa Junction', 'lat' => 9.1117, 'lng' => 7.3304, 'sequence' => 1]);
        $trip->waypoints()->create(['label' => 'Federal Secretariat Gate', 'lat' => 9.0500, 'lng' => 7.4900, 'sequence' => 2]);

        $this->service()->generate();

        $stopTimes = $this->readZip('stop_times.txt');
        $shapes = $this->readZip('shapes.txt');

        $this->assertStringContainsString('KUB-01', $stopTimes);
        $this->assertStringContainsString('KUB-16', $stopTimes);
        $this->assertStringNotContainsString('12.000000', $shapes);
    }

    public function test_falls_back_to_json_waypoints_when_no_relational_rows(): void
    {
        $this->seedStops();

        Trip::factory()->create([
            'corridor' => Corridor::KubwaCbd,
            'waypoints' => [
                ['label' => 'Kubwa Junction', 'lat' => 9.1117, 'lng' => 7.3304],
                ['label' => 'Federal Secretariat Gate', 'lat' => 9.0500, 'lng' => 7.4900],
            ],
        ]);

        $this->service()->generate();

        $stopTimes = $this->readZip('stop_times.txt');

        $this->assertStringContainsString('KUB-01', $stopTimes);
        $this->assertStringContainsString('KUB-16', $stopTimes);
    }

    public function test_creates_synthetic_stops_for_points_outside_the_catalog(): void
    {
        $this->seedStops();

        Trip::factory()->create([
            'corridor' => Corridor::KubwaCbd,
            'waypoints' => [
                ['label' => 'Unknown A', 'lat' => 10.0, 'lng' => 8.0],
                ['label' => 'Unknown B', 'lat' => 10.1, 'lng' => 8.1],
            ],
        ]);

        $this->service()->generate();

        $stopTimes = $this->readZip('stop_times.txt');

        $this->assertMatchesRegularExpression('/SYN-WR-\d+-\d+/', $stopTimes);
    }

    public function test_feed_path_exists_only_after_generation(): void
    {
        $this->assertNull($this->service()->feedPath());

        $this->seedStops();
        Trip::factory()->create(['corridor' => Corridor::KubwaCbd]);

        $this->service()->generate();

        $this->assertNotNull($this->service()->feedPath());
    }

    private function readZip(string $file): string
    {
        $zip = new ZipArchive;
        $zip->open(Storage::disk('public')->path('gtfs/gtfs.zip'));
        $contents = $zip->getFromName($file) ?: '';
        $zip->close();

        return $contents;
    }
}
