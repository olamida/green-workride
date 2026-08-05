<?php

namespace Database\Seeders;

use App\Enums\RoadCondition;
use App\Enums\RoadEventType;
use App\Models\RoadEvent;
use App\Models\RoadSegment;
use App\Models\User;
use Database\Seeders\Concerns\InteractsWithDemoData;
use Illuminate\Database\Seeder;

/**
 * Road Intelligence demo data (guide §13): 100 sensor events along the three
 * demo corridors + 20 IRI road segments. Confirmed potholes are written as
 * 5-report clusters around a hotspot (matching RoadIntelligenceService's
 * confirmation rule) so the public map and FERMA export demo real findings.
 * Coordinates stay inside the FCT bounding box.
 */
class RichRoadSeeder extends Seeder
{
    use InteractsWithDemoData;

    public function run(): void
    {
        if ($this->demoSynced()) {
            $this->command?->warn('Rich demo data already present — skipping RichRoadSeeder.');

            return;
        }

        $users = User::query()
            ->where('email', 'like', 'demo%@workride.ng')
            ->where('verification_level', 3)
            ->get();

        if ($users->isEmpty()) {
            $this->command?->error('RichRoadSeeder needs demo L3 users first.');

            return;
        }

        // Road corridors: [road_name, lat, lng] anchor points along each line.
        $corridors = [
            ['Kubwa Expressway', 9.1500, 7.3333],
            ['Kubwa Expressway', 9.1200, 7.3800],
            ['Kubwa Expressway', 9.1000, 7.4100],
            ['Kubwa Expressway', 9.0820, 7.4450],
            ['Kubwa Expressway', 9.0650, 7.4200],
            ['Nyanya-Keffi Road', 8.9800, 7.5800],
            ['Nyanya-Keffi Road', 8.9700, 7.5900],
            ['Nyanya-Keffi Road', 8.9900, 7.5700],
            ['Nyanya-Keffi Road', 9.0500, 7.5200],
            ['Airport Road', 8.9600, 7.3800],
            ['Airport Road', 8.9900, 7.5000],
            ['Airport Road', 8.9700, 7.4200],
        ];

        $hotspots = [
            // Confirmed pothole clusters: [road_name, lat, lng, type, severity].
            ['Kubwa Expressway', 9.1200, 7.3800, 'pothole', 4],
            ['Kubwa Expressway', 9.0820, 7.4450, 'bump', 3],
            ['Nyanya-Keffi Road', 8.9700, 7.5900, 'pothole', 5],
            ['Nyanya-Keffi Road', 9.0500, 7.5200, 'rough', 3],
            ['Airport Road', 8.9900, 7.5000, 'pothole', 4],
            ['Airport Road', 8.9700, 7.4200, 'flood', 3],
        ];

        $eventCount = 0;
        $seed = 1;

        foreach ($corridors as [$road, $lat, $lng]) {
            foreach (range(1, 6) as $k) {
                $type = RoadEventType::cases()[$k % 4];
                $severity = 1 + ($k % 5);
                RoadEvent::create([
                    'user_id' => $users[$seed % $users->count()]->id,
                    'lat' => round($lat + (($k % 3) - 1) * 0.0002, 7),
                    'lng' => round($lng + (($k % 2)) * 0.0002, 7),
                    'type' => $type,
                    'severity' => $severity,
                    'speed' => 20 + ($k * 7) % 45,
                    'accelerometer_z' => 8 + ($k * 3) % 18,
                    'is_confirmed' => false,
                    'road_name' => $road,
                    'created_at' => now()->subHours($k * 7),
                ]);
                $seed++;
                $eventCount++;
            }
        }

        // 5-report confirmed clusters at each hotspot.
        foreach ($hotspots as [$road, $lat, $lng, $type, $severity]) {
            foreach (range(1, 5) as $k) {
                RoadEvent::create([
                    'user_id' => $users[$seed % $users->count()]->id,
                    'lat' => round($lat + ($k % 3) * 0.0003, 7),
                    'lng' => round($lng + (($k + 1) % 3) * 0.0003, 7),
                    'type' => $type,
                    'severity' => $severity,
                    'speed' => 15 + $k * 5,
                    'accelerometer_z' => 15 + $k * 2,
                    'is_confirmed' => true,
                    'road_name' => $road,
                    'created_at' => now()->subDays(1 + $k),
                ]);
                $seed++;
                $eventCount++;
            }
        }

        // 20 road segments with World Bank RoadLab IRI bands.
        $segments = [
            // [road, start_lat, start_lng, end_lat, end_lng, iri]
            ['Kubwa Expressway', 9.1500, 7.3333, 9.1200, 7.3800, 3.2],
            ['Kubwa Expressway', 9.1200, 7.3800, 9.1000, 7.4100, 4.5],
            ['Kubwa Expressway', 9.1000, 7.4100, 9.0820, 7.4450, 7.8],
            ['Kubwa Expressway', 9.0820, 7.4450, 9.0650, 7.4200, 5.4],
            ['Kubwa Expressway', 9.0650, 7.4200, 9.0450, 7.4922, 3.0],
            ['Kubwa Expressway', 9.1500, 7.3333, 9.0450, 7.4922, 4.8],
            ['Nyanya-Keffi Road', 8.9800, 7.5800, 8.9700, 7.5900, 8.9],
            ['Nyanya-Keffi Road', 8.9700, 7.5900, 8.9900, 7.5700, 6.2],
            ['Nyanya-Keffi Road', 8.9900, 7.5700, 9.0500, 7.5200, 4.1],
            ['Nyanya-Keffi Road', 9.0500, 7.5200, 9.0522, 7.3245, 3.6],
            ['Nyanya-Keffi Road', 8.9800, 7.5800, 9.0522, 7.3245, 5.9],
            ['Airport Road', 8.9600, 7.3800, 8.9900, 7.5000, 4.7],
            ['Airport Road', 8.9900, 7.5000, 8.9700, 7.4200, 9.3],
            ['Airport Road', 8.9700, 7.4200, 8.9600, 7.3800, 6.8],
            ['Airport Road', 8.9600, 7.3800, 9.0450, 7.4922, 5.2],
            ['Gwagwalada Road', 8.9400, 7.0800, 8.9600, 7.3800, 3.9],
            ['Gwagwalada Road', 8.9600, 7.3800, 9.0450, 7.4922, 6.4],
            ['Dutse-Bwari Road', 9.1200, 7.3800, 9.2833, 7.3800, 7.1],
            ['Keffi-Berger Road', 8.8500, 7.5500, 9.0820, 7.4450, 4.2],
            ['Idu-AYA Road', 9.0522, 7.3245, 9.0500, 7.5200, 3.3],
        ];

        $segmentCount = 0;
        foreach ($segments as $s) {
            [$road, $slat, $slng, $elat, $elng, $iri] = $s;
            RoadSegment::updateOrCreate(
                ['road_name' => $road, 'start_lat' => $slat, 'start_lng' => $slng, 'end_lat' => $elat, 'end_lng' => $elng],
                [
                    'avg_iri' => $iri,
                    'condition' => $this->conditionFor($iri),
                    'last_updated' => now()->subDays(($segmentCount % 5) + 1),
                ]
            );
            $segmentCount++;
        }

        $this->command?->info(sprintf('Rich demo road data seeded: %d sensor events (incl. confirmed clusters) + %d IRI segments.', $eventCount, $segmentCount));
    }

    private function conditionFor(float $iri): RoadCondition
    {
        return match (true) {
            $iri < 4 => RoadCondition::Excellent,
            $iri < 6 => RoadCondition::Good,
            $iri < 10 => RoadCondition::Fair,
            default => RoadCondition::Poor,
        };
    }
}
