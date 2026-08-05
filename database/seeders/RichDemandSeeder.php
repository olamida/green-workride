<?php

namespace Database\Seeders;

use App\Enums\Corridor;
use App\Enums\DemandDayType;
use App\Enums\DemandRequestStatus;
use App\Enums\OdSurveyMode;
use App\Models\DemandRequest;
use App\Models\DemandSurvey;
use App\Models\Junction;
use App\Models\OdMatrix;
use App\Models\OdSurvey;
use App\Models\ProbeDemandPoint;
use App\Models\User;
use App\Models\Workplace;
use Database\Seeders\Concerns\InteractsWithDemoData;
use Illuminate\Database\Seeder;

/**
 * Demand research field kit (guide §9B): 120 junction counts, 40 rider
 * check-ins, 25 workplace OD surveys, 30 probe dwell points and the resulting
 * OD matrix — the "₦50k interns + phones" dataset for the Control Tower.
 * Counts are spread over weekday/weekend hours so the per-hour slider demos.
 */
class RichDemandSeeder extends Seeder
{
    use InteractsWithDemoData;

    public function run(): void
    {
        if ($this->demoSynced()) {
            $this->command?->warn('Rich demo data already present — skipping RichDemandSeeder.');

            return;
        }

        $junctions = Junction::query()->get();

        if ($junctions->isEmpty()) {
            $this->command?->error('RichDemandSeeder needs JunctionSeeder to run first.');

            return;
        }

        $users = User::query()
            ->where('email', 'like', 'demo%@workride.ng')
            ->get();

        $collector = $users->first();

        // --- 120 junction counts (2 per junction: weekday + weekend peak). ---
        $countCreated = 0;
        $destinations = [
            Corridor::KubwaCbd->value => ['CBD', 'Garki', 'Wuse', 'Banex'],
            Corridor::NyanyaIdu->value => ['Idu', 'CBD', 'Wuse', 'Karu'],
            Corridor::LugbeCbd->value => ['CBD', 'Airport', 'Garki', 'Apo'],
        ];

        foreach ($junctions as $junction) {
            $dest = $destinations[$junction->corridor] ?? ['CBD'];
            $hour = in_array($junction->corridor, [Corridor::KubwaCbd->value, Corridor::NyanyaIdu->value], true) ? 7 : 6;

            foreach ([DemandDayType::Weekday, DemandDayType::Weekend] as $dayType) {
                $count = $dayType === DemandDayType::Weekday
                    ? 30 + ($junction->id * 7) % 200
                    : 10 + ($junction->id * 3) % 60;

                DemandSurvey::create([
                    'junction_id' => $junction->id,
                    'count' => $count,
                    'destination_text' => $dest[$junction->id % count($dest)],
                    'hour' => $hour,
                    'day_type' => $dayType,
                    'weather' => $dayType === DemandDayType::Weekday ? 'sunny' : 'cloudy',
                    'collected_by' => $collector?->id,
                    'lat' => $junction->lat,
                    'lng' => $junction->lng,
                    'photo_path' => null,
                    'created_at' => now()->subDays($countCreated % 12)->setHour($hour),
                ]);
                $countCreated++;
            }
        }

        // --- 40 rider check-ins (demand requests). ---
        $checkInCreated = 0;
        $jcnt = $junctions->count();
        foreach (range(1, 40) as $i) {
            $junction = $junctions[$i % $jcnt];
            $status = match (true) {
                $i % 4 === 0 => DemandRequestStatus::Cancelled,
                $i % 3 === 0 => DemandRequestStatus::Matched,
                default => DemandRequestStatus::Pending,
            };

            DemandRequest::create([
                'user_id' => $users[$i % $users->count()]->id,
                'pickup_lat' => $junction->lat,
                'pickup_lng' => $junction->lng,
                'destination_text' => ($destinations[$junction->corridor] ?? ['CBD'])[0],
                'passengers_count' => 1 + ($i % 3),
                'requested_at' => now()->subHours($i % 20),
                'status' => $status,
                'matched_trip_id' => null,
            ]);
            $checkInCreated++;
        }

        // --- 25 workplace OD surveys (home → work origin/destination matrix). ---
        $odCreated = 0;
        $workplaces = Workplace::query()->limit(10)->get();
        $homeAreas = ['Kubwa', 'Gwarimpa', 'Lugbe', 'Nyanya', 'Karu', 'Dutse', 'Bwari', 'Kuje'];
        foreach (range(1, 25) as $i) {
            $workplace = $workplaces[$i % $workplaces->count()];
            $home = $homeAreas[$i % count($homeAreas)];

            OdSurvey::create([
                'workplace_id' => $workplace->id,
                'user_id' => $users[$i % $users->count()]->id,
                'home_area' => $home,
                'departure_time' => now()->subDays($i % 30)->setTime(6, 30 + ($i % 60)),
                'arrival_time' => now()->subDays($i % 30)->setTime(7, 15 + ($i % 30)),
                'fare_paid' => 400 + ($i * 13) % 600,
                'mode' => OdSurveyMode::cases()[$i % 3],
                'created_at' => now()->subDays($i % 30),
            ]);
            $odCreated++;
        }

        // --- 30 probe dwell points (cars slowing at junctions). ---
        $probeCreated = 0;
        foreach (range(1, 30) as $i) {
            $junction = $junctions[$i % $jcnt];
            ProbeDemandPoint::create([
                'lat' => $junction->lat,
                'lng' => $junction->lng,
                'corridor' => $junction->corridor,
                'avg_speed' => 3 + ($i % 6),
                'dwell_time_seconds' => 60 + ($i * 11) % 300,
                'times_visited' => 1 + ($i % 12),
                'last_seen_at' => now()->subHours($i % 48),
            ]);
            $probeCreated++;
        }

        // --- OD matrix snapshot from the surveys. ---
        $matrixCreated = 0;
        $rows = OdSurvey::query()
            ->join('workplaces', 'workplaces.id', '=', 'od_surveys.workplace_id')
            ->selectRaw('od_surveys.home_area, workplaces.zone as dest_zone, count(*) as c')
            ->groupBy('od_surveys.home_area', 'workplaces.zone')
            ->get();

        foreach ($rows as $row) {
            OdMatrix::updateOrCreate(
                [
                    'origin_area' => $row->home_area,
                    'destination_area' => $row->dest_zone,
                ],
                [
                    'count' => $row->c,
                    'corridor' => Corridor::KubwaCbd->value,
                    'period_start' => now()->subDays(30)->toDateString(),
                    'period_end' => now()->toDateString(),
                    'generated_by' => $collector?->id,
                ]
            );
            $matrixCreated++;
        }

        $this->command?->info(sprintf(
            'Rich demo demand data seeded: %d junction counts + %d check-ins + %d OD surveys + %d probe points + %d OD-matrix rows.',
            $countCreated,
            $checkInCreated,
            $odCreated,
            $probeCreated,
            $matrixCreated
        ));
    }
}
