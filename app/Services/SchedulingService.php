<?php

namespace App\Services;

use App\Enums\BusScheduleStatus;
use App\Enums\Corridor;
use App\Enums\TripStatus;
use App\Jobs\GenerateGtfsFeedJob;
use App\Models\BusSchedule;
use App\Models\Trip;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Recurring supply backbone (guide §6 Workflow 5 — Citymapper-style
 * "every 15 mins 6:30–9am").
 *
 * A BusSchedule row is declarative: "Kubwa→CBD every 15 min Mon–Fri
 * 06:30–09:00". This service materialises those declarations into real,
 * bookable Trip rows (one per departure slot, keyed by an idempotent
 * `schedule_ref`) so the existing board/booking/GTFS machinery just works.
 */
class SchedulingService
{
    public function __construct(
        private PricingService $pricing,
    ) {}

    /**
     * Materialise every active schedule for one calendar day into Trip rows.
     *
     * Idempotent: a slot whose `schedule_ref` already exists is skipped, so
     * the nightly job (or a manual "materialise today" from the Control Tower)
     * can run repeatedly without duplicating trips.
     *
     * @param  string|CarbonInterface  $date  any day; weekday is read from it
     * @return int number of Trip rows created
     */
    public function materializeDay(string|CarbonInterface $date): int
    {
        if (! (bool) config('workride.scheduling.enabled', true)) {
            return 0;
        }

        $day = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        $weekday = strtolower($day->format('D')); // mon..sun
        $created = 0;

        $schedules = BusSchedule::query()
            ->with(['route', 'vehicle', 'driver'])
            ->where('status', BusScheduleStatus::Active)
            ->get()
            ->filter(fn (BusSchedule $schedule) => $schedule->runsOn($weekday));

        foreach ($schedules as $schedule) {
            foreach ($schedule->departureTimes() as $time) {
                $departure = Carbon::parse($day->toDateString().' '.$time);

                if ($departure->isPast()) {
                    continue;
                }

                $reference = $schedule->referenceFor($day->toDateString(), $time);

                if (Trip::query()->where('schedule_ref', $reference)->exists()) {
                    continue;
                }

                $this->createTrip($schedule, $departure, $reference);
                $created++;
            }
        }

        if ($created > 0) {
            GenerateGtfsFeedJob::dispatch();
        }

        return $created;
    }

    /**
     * Passenger-facing "next departures" for the board panel: the closest
     * materialised trips (already on the board) merged with the closest
     * schedule slots that have not yet been materialised, so a rider sees the
     * guaranteed timetable even after the nightly job has run. Deduped by
     * `schedule_id|Y-m-d H:i` so the same slot never appears twice.
     *
     * @return list<array<string, mixed>>
     */
    public function nextDepartures(?Corridor $corridor = null, int $limit = 6): array
    {
        if (! (bool) config('workride.scheduling.enabled', true)) {
            return [];
        }

        $lookaheadHours = (int) config('workride.scheduling.lookahead_hours', 48);
        $from = now();
        $to = $from->copy()->addHours($lookaheadHours);

        $materialized = Trip::query()
            ->whereNotNull('schedule_ref')
            ->whereIn('status', [TripStatus::Scheduled, TripStatus::Active])
            ->where('available_seats', '>', 0)
            ->whereBetween('departure_time', [$from, $to])
            ->when($corridor, fn ($query) => $query->where('corridor', $corridor))
            ->orderBy('departure_time')
            ->get()
            ->map(fn (Trip $trip) => [
                'source' => 'trip',
                'trip_id' => $trip->id,
                'schedule_id' => $this->refScheduleId((string) $trip->schedule_ref),
                'departure_time' => $trip->departure_time,
                'corridor' => $trip->corridor,
                'label' => $trip->route_name,
                'fare' => (float) $trip->fare_per_seat,
                'seats' => (int) $trip->available_seats,
            ])
            ->all();

        $occurrences = [];
        $seen = [];

        foreach ($materialized as $row) {
            $key = $row['schedule_id'].'|'.Carbon::parse($row['departure_time'])->format('Y-m-d H:i');
            $seen[$key] = true;
        }

        $schedules = BusSchedule::query()
            ->with(['route', 'vehicle'])
            ->where('status', BusScheduleStatus::Active)
            ->get();

        foreach ($schedules as $schedule) {
            if ($corridor !== null && $schedule->corridor() !== $corridor) {
                continue;
            }

            foreach ($this->departuresBetween($schedule, $from, $to) as $departure) {
                $key = $schedule->id.'|'.$departure->format('Y-m-d H:i');

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $occurrences[] = [
                    'source' => 'schedule',
                    'trip_id' => null,
                    'schedule_id' => $schedule->id,
                    'departure_time' => $departure,
                    'corridor' => $schedule->corridor(),
                    'label' => $schedule->routeLabel(),
                    'fare' => $this->pricing->fareFor($schedule->corridor()),
                    'seats' => (int) ($schedule->vehicle?->seats ?? config('workride.scheduling.default_seats', 15)),
                ];
            }
        }

        return collect(array_merge($materialized, $occurrences))
            ->sortBy('departure_time')
            ->values()
            ->take($limit)
            ->all();
    }

    /**
     * Departure Carbon instances for a schedule within a time window, honouring
     * the day-of-week + frequency. Pure — never writes.
     *
     * @return list<CarbonInterface>
     */
    public function departuresBetween(BusSchedule $schedule, CarbonInterface $from, CarbonInterface $to): array
    {
        $departures = [];

        for ($day = $from->copy()->startOfDay(); $day->lte($to->copy()->startOfDay()); $day->addDay()) {
            $weekday = strtolower($day->format('D'));

            if (! $schedule->runsOn($weekday)) {
                continue;
            }

            foreach ($schedule->departureTimes() as $time) {
                $departure = Carbon::parse($day->toDateString().' '.$time);

                if ($departure->lt($from) || $departure->gt($to)) {
                    continue;
                }

                $departures[] = $departure;
            }
        }

        return $departures;
    }

    /**
     * Create one materialised Trip row for a schedule slot.
     */
    private function createTrip(BusSchedule $schedule, CarbonInterface $departure, string $reference): Trip
    {
        $corridor = $schedule->corridor();

        return DB::transaction(function () use ($schedule, $departure, $reference, $corridor) {
            $vehicle = $schedule->vehicle;
            $seats = (int) ($vehicle?->seats ?? config('workride.scheduling.default_seats', 15));

            $trip = Trip::create([
                'driver_id' => $schedule->driver_id,
                'vehicle_id' => $schedule->vehicle_id,
                'route_name' => $schedule->routeLabel(),
                'corridor' => $corridor,
                'origin_text' => $this->originText($corridor),
                'destination_text' => $this->destinationText($corridor),
                'total_seats' => $seats,
                'available_seats' => $seats,
                'fare_per_seat' => $this->pricing->fareFor($corridor),
                'is_free_volunteer' => false,
                'women_only' => false,
                'status' => TripStatus::Scheduled,
                'departure_time' => $departure,
                'schedule_ref' => $reference,
            ]);

            $origin = $this->corridorAnchor($corridor);
            $destination = $this->corridorDestination($corridor);

            $trip->waypoints()->create([
                'label' => $this->originText($corridor),
                'lat' => $origin['lat'],
                'lng' => $origin['lng'],
                'sequence' => 1,
            ]);

            $trip->waypoints()->create([
                'label' => $this->destinationText($corridor),
                'lat' => $destination['lat'],
                'lng' => $destination['lng'],
                'sequence' => 2,
            ]);

            return $trip;
        });
    }

    /**
     * The schedule id embedded in a `SCHED-{id}-{Ymd}-{Hi}` reference, or null.
     */
    private function refScheduleId(string $reference): ?int
    {
        $parts = explode('-', $reference);

        return isset($parts[1]) ? (int) $parts[1] : null;
    }

    private function originText(Corridor $corridor): string
    {
        return match ($corridor) {
            Corridor::KubwaCbd => 'Kubwa',
            Corridor::NyanyaIdu => 'Nyanya',
            Corridor::LugbeCbd => 'Lugbe',
        };
    }

    private function destinationText(Corridor $corridor): string
    {
        return match ($corridor) {
            Corridor::KubwaCbd => 'CBD (Central Business District)',
            Corridor::NyanyaIdu => 'Idu',
            Corridor::LugbeCbd => 'CBD (Central Business District)',
        };
    }

    /**
     * @return array{lat:float,lng:float}
     */
    private function corridorAnchor(Corridor $corridor): array
    {
        return match ($corridor) {
            Corridor::KubwaCbd => ['lat' => 9.1175, 'lng' => 7.3598],
            Corridor::NyanyaIdu => ['lat' => 9.0025, 'lng' => 7.5210],
            Corridor::LugbeCbd => ['lat' => 8.9836, 'lng' => 7.3498],
        };
    }

    /**
     * @return array{lat:float,lng:float}
     */
    private function corridorDestination(Corridor $corridor): array
    {
        return match ($corridor) {
            Corridor::KubwaCbd => ['lat' => 9.0550, 'lng' => 7.4890],
            Corridor::NyanyaIdu => ['lat' => 8.9960, 'lng' => 7.4270],
            Corridor::LugbeCbd => ['lat' => 9.0550, 'lng' => 7.4890],
        };
    }
}
