<?php

namespace App\Jobs;

use App\Enums\BookingStatus;
use App\Models\ImpactStat;
use App\Models\Trip;
use App\Models\User;
use App\Services\Co2Service;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * After a trip completes, credit every participant (driver + boarded
 * passengers) with their share of the CO2 / fuel saved.
 */
class CalculateImpactJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $tripId,
    ) {}

    public function handle(Co2Service $co2): void
    {
        $trip = Trip::with(['bookings.passenger', 'driver'])->find($this->tripId);

        if (! $trip) {
            return;
        }

        $riders = $trip->bookings
            ->filter(fn ($b) => in_array($b->status, [BookingStatus::Boarded, BookingStatus::Completed], true))
            ->values();

        $occupants = 1 + $riders->count();
        $distanceKm = $this->distanceKm($trip);

        if ($occupants < 2 || $distanceKm <= 0) {
            return;
        }

        $impact = $co2->forRide($occupants, $distanceKm);

        $this->credit($trip->driver, $impact);

        foreach ($riders as $booking) {
            if ($booking->passenger) {
                $this->credit($booking->passenger, $impact);
            }
        }
    }

    /**
     * Add one trip's worth of impact to a user's running totals.
     *
     * @param  array{co2_kg: float, trees: float, fuel_litres: float}  $impact
     */
    private function credit(User $user, array $impact): void
    {
        $stat = ImpactStat::firstOrCreate(['user_id' => $user->id]);

        $stat->update([
            'total_trips' => $stat->total_trips + 1,
            'co2_saved_kg' => round((float) $stat->co2_saved_kg + $impact['co2_kg'], 2),
            'fuel_saved_litres' => round((float) $stat->fuel_saved_litres + $impact['fuel_litres'], 2),
            'trees_equivalent' => round((float) $stat->trees_equivalent + $impact['trees'], 2),
            'level' => min(5, (int) $stat->level + 1),
        ]);
    }

    private function distanceKm(Trip $trip): float
    {
        // Best: measured waypoint span.
        $points = $trip->waypoints()
            ->orderBy('sequence')
            ->get(['lat', 'lng'])
            ->map(fn ($w) => [(float) $w->lat, (float) $w->lng]);

        if ($points->count() >= 2) {
            return $this->pathKm($points->values()->all());
        }

        // Fallback: config distance for the corridor.
        return (float) config("workride.corridor_distance_km.{$trip->corridor->value}", 15);
    }

    private function pathKm(array $points): float
    {
        $total = 0.0;

        for ($i = 1; $i < count($points); $i++) {
            [$lat1, $lng1] = $points[$i - 1];
            [$lat2, $lng2] = $points[$i];
            $total += app(Co2Service::class)->distanceKm($lat1, $lng1, $lat2, $lng2);
        }

        return round($total, 2);
    }
}
