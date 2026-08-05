<?php

namespace Database\Seeders;

use App\Enums\Corridor;
use App\Enums\TripStatus;
use App\Models\Trip;
use App\Models\TripWaypoint;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\Concerns\InteractsWithDemoData;
use Illuminate\Database\Seeder;

/**
 * 80 interlinked demo trips across the three corridors (guide §6 workflow 1).
 *
 * Mix: ~40 completed (impact + receipts + driver scores), ~10 active TODAY
 * (live board + GTFS-RT positions), ~22 scheduled (this week, GTFS feed),
 * ~8 cancelled (refund trail). Every trip carries relational trip_waypoints
 * (used by GtfsService) AND a `waypoints` JSON snapshot (legacy fallback).
 * Fares follow PricingService fixed per-corridor prices; some are volunteers
 * (fare 0) and some women-only — the board filters demo live.
 */
class RichTripSeeder extends Seeder
{
    use InteractsWithDemoData;

    public function run(): void
    {
        if ($this->demoSynced()) {
            $this->command?->warn('Rich demo data already present — skipping RichTripSeeder.');

            return;
        }

        $drivers = User::query()
            ->where('email', 'like', 'demo%@workride.ng')
            ->where('verification_level', 3)
            ->orderBy('id')
            ->get();

        $vehicles = Vehicle::query()
            ->whereIn('user_id', $drivers->pluck('id'))
            ->orderBy('id')
            ->get();

        if ($drivers->isEmpty() || $vehicles->isEmpty()) {
            $this->command?->error('RichTripSeeder needs L3 demo users + registered vehicles first.');

            return;
        }

        $routes = [
            Corridor::KubwaCbd->value => [
                'origin' => 'Kubwa Junction',
                'destination' => 'Federal Secretariat',
                'origin_lat' => 9.1500,
                'origin_lng' => 7.3333,
                'dest_lat' => 9.0450,
                'dest_lng' => 7.4922,
                'waypoints' => [
                    ['label' => 'Kubwa Junction', 'lat' => 9.1500, 'lng' => 7.3333],
                    ['label' => 'Dutse Alhaji', 'lat' => 9.1200, 'lng' => 7.3800],
                    ['label' => 'Gwarimpa Gate', 'lat' => 9.1000, 'lng' => 7.4100],
                    ['label' => 'Berger Junction', 'lat' => 9.0820, 'lng' => 7.4450],
                    ['label' => 'Jabi Lake Junction', 'lat' => 9.0650, 'lng' => 7.4200],
                    ['label' => 'Federal Secretariat', 'lat' => 9.0450, 'lng' => 7.4922],
                ],
                'fare' => 800,
            ],
            Corridor::NyanyaIdu->value => [
                'origin' => 'Nyanya Under-Bridge',
                'destination' => 'Idu Junction',
                'origin_lat' => 8.9800,
                'origin_lng' => 7.5800,
                'dest_lat' => 9.0522,
                'dest_lng' => 7.3245,
                'waypoints' => [
                    ['label' => 'Nyanya Under-Bridge', 'lat' => 8.9800, 'lng' => 7.5800],
                    ['label' => 'Mararaba Junction', 'lat' => 8.9700, 'lng' => 7.5900],
                    ['label' => 'Karu Junction', 'lat' => 8.9900, 'lng' => 7.5700],
                    ['label' => 'Asokoro Junction', 'lat' => 9.0500, 'lng' => 7.5200],
                    ['label' => 'Idu Junction', 'lat' => 9.0522, 'lng' => 7.3245],
                ],
                'fare' => 700,
            ],
            Corridor::LugbeCbd->value => [
                'origin' => 'Lugbe Junction',
                'destination' => 'Federal Secretariat',
                'origin_lat' => 8.9600,
                'origin_lng' => 7.3800,
                'dest_lat' => 9.0450,
                'dest_lng' => 7.4922,
                'waypoints' => [
                    ['label' => 'Lugbe Junction', 'lat' => 8.9600, 'lng' => 7.3800],
                    ['label' => 'Apo Junction', 'lat' => 8.9900, 'lng' => 7.5000],
                    ['label' => 'Lokogoma Junction', 'lat' => 8.9600, 'lng' => 7.4500],
                    ['label' => 'Galadimawa Junction', 'lat' => 8.9700, 'lng' => 7.4200],
                    ['label' => 'Federal Secretariat', 'lat' => 9.0450, 'lng' => 7.4922],
                ],
                'fare' => 600,
            ],
        ];

        $created = 0;

        // --- Completed trips: last 14 days, mornings + evenings per corridor. ---
        $completed = 0;
        foreach (range(1, 40) as $k) {
            $corridor = Corridor::cases()[$k % 3]->value;
            $route = $routes[$corridor];
            $daysAgo = 1 + ($k % 14);
            $morning = $k % 2 === 0;
            $departure = now()
                ->subDays($daysAgo)
                ->setTime($morning ? 6 : 17, 30 + ($k % 3) * 15, 0);
            if ($departure->isFuture()) {
                $departure = $departure->subDays(1);
            }
            $driver = $drivers[$k % $drivers->count()];
            $vehicle = $vehicles[$k % $vehicles->count()];
            $volunteer = $k % 9 === 0;

            $trip = Trip::create([
                'driver_id' => $driver->id,
                'vehicle_id' => $volunteer ? null : $vehicle->id,
                'route_name' => $route['origin'].' → '.$route['destination'],
                'corridor' => $corridor,
                'origin_text' => $route['origin'],
                'destination_text' => $route['destination'],
                'current_lat' => $route['dest_lat'],
                'current_lng' => $route['dest_lng'],
                'total_seats' => $volunteer ? ($vehicle->seats ?? 4) : $vehicle->seats,
                'available_seats' => 0,
                'fare_per_seat' => $volunteer ? 0 : $route['fare'],
                'is_free_volunteer' => $volunteer,
                'women_only' => $k % 7 === 0,
                'status' => TripStatus::Completed,
                'departure_time' => $departure,
            ]);

            $this->insertWaypoints($trip, $route['waypoints']);
            $completed++;
            $created++;
        }

        // --- Active trips: today, staggered departures, live coordinates. ---
        foreach (range(1, 10) as $k) {
            $corridor = Corridor::cases()[$k % 3]->value;
            $route = $routes[$corridor];
            $departure = now()->addMinutes(($k % 3) * 20)->startOfMinute();
            $driver = $drivers[40 + $k % 5] ?? $drivers[$k % $drivers->count()];
            $vehicle = $vehicles[10 + $k % 5] ?? $vehicles[$k % $vehicles->count()];
            $volunteer = $k % 8 === 0;

            // Interpolate position partway along the corridor.
            $progress = 0.15 + ($k % 4) * 0.12;
            $start = $route['waypoints'][0];
            $end = $route['waypoints'][count($route['waypoints']) - 1];
            $lat = $start['lat'] + ($end['lat'] - $start['lat']) * $progress;
            $lng = $start['lng'] + ($end['lng'] - $start['lng']) * $progress;

            $trip = Trip::create([
                'driver_id' => $driver->id,
                'vehicle_id' => $volunteer ? null : $vehicle->id,
                'route_name' => $route['origin'].' → '.$route['destination'],
                'corridor' => $corridor,
                'origin_text' => $route['origin'],
                'destination_text' => $route['destination'],
                'current_lat' => $lat,
                'current_lng' => $lng,
                'total_seats' => $vehicle->seats,
                'available_seats' => max($vehicle->seats - (2 + ($k % 3)), 1),
                'fare_per_seat' => $volunteer ? 0 : $route['fare'],
                'is_free_volunteer' => $volunteer,
                'women_only' => $k % 6 === 0,
                'status' => TripStatus::Active,
                'departure_time' => $departure,
            ]);

            $this->insertWaypoints($trip, $route['waypoints']);
            $created++;
        }

        // --- Scheduled trips: next 7 days, peak hours. ---
        foreach (range(1, 22) as $k) {
            $corridor = Corridor::cases()[$k % 3]->value;
            $route = $routes[$corridor];
            $dayAhead = 1 + ($k % 7);
            $morning = $k % 2 === 0;
            $departure = now()
                ->addDays($dayAhead)
                ->setTime($morning ? 6 : 17, 0 + ($k % 4) * 15, 0);
            $driver = $drivers[60 + $k % 5] ?? $drivers[$k % $drivers->count()];
            $vehicle = $vehicles[20 + $k % 5] ?? $vehicles[$k % $vehicles->count()];
            $volunteer = $k % 11 === 0;

            $trip = Trip::create([
                'driver_id' => $driver->id,
                'vehicle_id' => $volunteer ? null : $vehicle->id,
                'route_name' => $route['origin'].' → '.$route['destination'],
                'corridor' => $corridor,
                'origin_text' => $route['origin'],
                'destination_text' => $route['destination'],
                'current_lat' => $route['origin_lat'],
                'current_lng' => $route['origin_lng'],
                'total_seats' => $vehicle->seats,
                'available_seats' => $vehicle->seats,
                'fare_per_seat' => $volunteer ? 0 : $route['fare'],
                'is_free_volunteer' => $volunteer,
                'women_only' => $k % 9 === 0,
                'status' => TripStatus::Scheduled,
                'departure_time' => $departure,
            ]);

            $this->insertWaypoints($trip, $route['waypoints']);
            $created++;
        }

        // --- Cancelled trips: some refund trail. ---
        foreach (range(1, 8) as $k) {
            $corridor = Corridor::cases()[$k % 3]->value;
            $route = $routes[$corridor];
            $daysAgo = 2 + ($k % 10);
            $departure = now()->subDays($daysAgo)->setTime(7, 0, 0);
            $driver = $drivers[30 + $k % 5] ?? $drivers[$k % $drivers->count()];
            $vehicle = $vehicles[30 + $k % 5] ?? $vehicles[$k % $vehicles->count()];

            $trip = Trip::create([
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'route_name' => $route['origin'].' → '.$route['destination'],
                'corridor' => $corridor,
                'origin_text' => $route['origin'],
                'destination_text' => $route['destination'],
                'current_lat' => $route['origin_lat'],
                'current_lng' => $route['origin_lng'],
                'total_seats' => $vehicle->seats,
                'available_seats' => $vehicle->seats,
                'fare_per_seat' => $route['fare'],
                'is_free_volunteer' => false,
                'women_only' => false,
                'status' => TripStatus::Cancelled,
                'departure_time' => $departure,
            ]);

            $this->insertWaypoints($trip, $route['waypoints']);
            $created++;
        }

        $this->command?->info(sprintf('Rich demo trips seeded: %d (%d completed, 10 active, 22 scheduled, %d cancelled).', $created, $completed, 8));
    }

    private function insertWaypoints(Trip $trip, array $points): void
    {
        foreach ($points as $seq => $point) {
            TripWaypoint::create([
                'trip_id' => $trip->id,
                'label' => $point['label'],
                'lat' => $point['lat'],
                'lng' => $point['lng'],
                'sequence' => $seq,
            ]);
        }

        // Legacy JSON snapshot mirror (GtfsService prefers the relation).
        $trip->forceFill(['waypoints' => $points])->save();
    }
}
