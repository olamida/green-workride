<?php

namespace Tests\Feature;

use App\Enums\RoadCondition;
use App\Enums\RoadEventType;
use App\Models\RoadEvent;
use App\Models\RoadSegment;
use App\Models\User;
use App\Services\RoadIntelligenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RoadIntelligenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): RoadIntelligenceService
    {
        return $this->app->make(RoadIntelligenceService::class);
    }

    private function pothole(float $lat, float $lng, ?Carbon $createdAt = null): RoadEvent
    {
        $event = RoadEvent::factory()->create([
            'lat' => $lat,
            'lng' => $lng,
            'type' => RoadEventType::Pothole,
            'accelerometer_z' => 20,
            'speed' => 30,
            'road_name' => 'Kubwa Expressway',
            'is_confirmed' => false,
        ]);

        if ($createdAt) {
            $event->update(['created_at' => $createdAt]);
        }

        return $event;
    }

    public function test_five_reports_within_radius_confirms_all(): void
    {
        $this->pothole(9.05000, 7.49000);
        $this->pothole(9.05001, 7.49001);
        $this->pothole(9.05002, 7.49002);
        $this->pothole(9.05003, 7.49003);
        $this->pothole(9.05004, 7.49004);

        $confirmed = $this->service()->confirmClusters();

        $this->assertSame(5, $confirmed);
        $this->assertSame(5, RoadEvent::where('is_confirmed', true)->count());
    }

    public function test_fewer_than_five_reports_are_not_confirmed(): void
    {
        $this->pothole(9.0500, 7.4900);
        $this->pothole(9.0501, 7.4901);

        $confirmed = $this->service()->confirmClusters();

        $this->assertSame(0, $confirmed);
        $this->assertSame(0, RoadEvent::where('is_confirmed', true)->count());
    }

    public function test_far_apart_reports_do_not_cluster(): void
    {
        $this->pothole(9.0500, 7.4900);
        $this->pothole(9.0600, 7.5000);
        $this->pothole(9.0700, 7.5100);
        $this->pothole(9.0800, 7.5200);
        $this->pothole(9.0900, 7.5300);

        $confirmed = $this->service()->confirmClusters();

        $this->assertSame(0, $confirmed);
    }

    public function test_reports_older_than_window_are_excluded(): void
    {
        $this->pothole(9.0500, 7.4900, Carbon::now()->subDays(10));
        $this->pothole(9.0501, 7.4901, Carbon::now()->subDays(10));
        $this->pothole(9.0502, 7.4902, Carbon::now()->subDays(10));
        $this->pothole(9.0503, 7.4903, Carbon::now()->subDays(10));
        $this->pothole(9.0504, 7.4904, Carbon::now()->subDays(10));

        $confirmed = $this->service()->confirmClusters();

        $this->assertSame(0, $confirmed);
    }

    public function test_iri_formula_maps_to_condition_bands(): void
    {
        $service = $this->service();

        // IRI = alpha * sqrt(z^2) / speed + beta, with alpha=2, beta=1.5.
        // z=20, speed=100 → 2*20/100 + 1.5 = 1.9 → Excellent.
        $this->assertSame(RoadCondition::Excellent, $service->conditionFor($service->iri(20, 100)));

        // z=20, speed=50 → 2*20/50 + 1.5 = 2.3 → Excellent (< 4).
        $this->assertSame(RoadCondition::Excellent, $service->conditionFor($service->iri(20, 50)));

        // z=20, speed=20 → 2*20/20 + 1.5 = 3.5 → Excellent.
        $this->assertSame(RoadCondition::Excellent, $service->conditionFor($service->iri(20, 20)));

        // z=30, speed=20 → 2*30/20 + 1.5 = 4.5 → Good (< 6).
        $this->assertSame(RoadCondition::Good, $service->conditionFor($service->iri(30, 20)));

        // z=50, speed=20 → 2*50/20 + 1.5 = 6.5 → Fair (< 10).
        $this->assertSame(RoadCondition::Fair, $service->conditionFor($service->iri(50, 20)));

        // z=90, speed=20 → 2*90/20 + 1.5 = 10.5 → Poor.
        $this->assertSame(RoadCondition::Poor, $service->conditionFor($service->iri(90, 20)));
    }

    public function test_iri_returns_null_without_accelerometer(): void
    {
        $this->assertNull($this->service()->iri(null, 30));
    }

    public function test_confirmed_potholes_refresh_segment_iri(): void
    {
        RoadEvent::factory()->count(5)->create([
            'type' => RoadEventType::Pothole,
            'road_name' => 'Nyanya-Keffi',
            'accelerometer_z' => 40,
            'speed' => 20,
            'is_confirmed' => true,
        ]);

        $segment = $this->service()->refreshSegment('Nyanya-Keffi');

        $this->assertNotNull($segment);
        $this->assertSame('Nyanya-Keffi', $segment->road_name);
        $this->assertNotNull($segment->avg_iri);
        $this->assertInstanceOf(RoadSegment::class, $segment);
    }

    public function test_record_event_confirms_and_refreshes_segment(): void
    {
        RoadEvent::factory()->count(4)->create([
            'type' => RoadEventType::Pothole,
            'road_name' => 'Airport Road',
            'lat' => 9.0500,
            'lng' => 7.4900,
            'accelerometer_z' => 20,
            'speed' => 30,
        ]);

        $event = $this->service()->recordEvent([
            'user_id' => User::factory()->create()->id,
            'lat' => 9.0501,
            'lng' => 7.4901,
            'type' => RoadEventType::Pothole,
            'severity' => 4,
            'speed' => 30,
            'accelerometer_z' => 20,
            'road_name' => 'Airport Road',
        ]);

        $this->assertTrue($event->is_confirmed);
        $this->assertDatabaseHas('road_segments', ['road_name' => 'Airport Road']);
    }

    public function test_ferma_export_contains_only_confirmed_potholes(): void
    {
        RoadEvent::factory()->count(3)->create(['is_confirmed' => true, 'type' => RoadEventType::Pothole]);
        RoadEvent::factory()->count(2)->create(['is_confirmed' => false, 'type' => RoadEventType::Pothole]);

        $rows = $this->service()->fermaExport();

        $this->assertCount(3, $rows);
        $this->assertArrayHasKey('road_name', $rows[0]);
        $this->assertArrayHasKey('lat', $rows[0]);
        $this->assertArrayHasKey('lng', $rows[0]);
    }
}
