<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Employer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Employer CSR / subsidy report (roadmap 3.14 + guide §11 receipt #7):
 * the one-click printable CO₂ + coverage report an MDA takes into its
 * renewal meeting. Aggregates the employer's covered bookings into a
 * monthly statement using the same impact convention as
 * CalculateImpactJob (every participant is credited the full trip saving).
 */
class EmployerReportService
{
    public function __construct(
        private readonly Co2Service $co2,
    ) {}

    /**
     * Build a CSR statement for one employer over one month.
     *
     * @return array{
     *   employer: Employer,
     *   month: Carbon,
     *   staff_covered: int,
     *   rides_covered: int,
     *   coverage_spent: float,
     *   co2_kg: float,
     *   fuel_litres: float,
     *   trees: float,
     *   per_employee: Collection,
     *   per_day: Collection,
     * }
     */
    public function report(Employer $employer, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $bookings = $employer->bookings()
            ->with(['trip', 'passenger'])
            ->whereNot('status', BookingStatus::Cancelled->value)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $byTrip = $bookings->groupBy('trip_id');

        // Trip-level impact is computed once per trip (occupants = driver +
        // covered riders on that trip) and credited per covered booking — the
        // same convention CalculateImpactJob applies to individual certificates.
        $tripImpact = [];

        foreach ($byTrip as $tripId => $rows) {
            $trip = $rows->first()?->trip;

            if (! $trip || $trip->corridor === null) {
                $tripImpact[$tripId] = ['co2_kg' => 0.0, 'fuel_litres' => 0.0, 'trees' => 0.0];

                continue;
            }

            $occupants = 1 + $rows->count();
            $distance = $this->distanceKm($trip);

            if ($occupants < 2 || $distance <= 0) {
                $tripImpact[$tripId] = ['co2_kg' => 0.0, 'fuel_litres' => 0.0, 'trees' => 0.0];

                continue;
            }

            $tripImpact[$tripId] = $this->co2->forRide($occupants, $distance);
        }

        $staffCovered = $bookings->where('passenger_id')->pluck('passenger_id')->unique()->count();
        $ridesCovered = $bookings->count();
        $coverageSpent = round((float) $bookings->sum('employer_contribution'), 2);

        $co2 = round((float) array_sum(array_column($tripImpact, 'co2_kg')), 2);
        $fuel = round((float) array_sum(array_column($tripImpact, 'fuel_litres')), 2);
        $trees = round((float) array_sum(array_column($tripImpact, 'trees')), 2);

        $perEmployee = $bookings
            ->groupBy('passenger_id')
            ->map(function (Collection $rows) use ($tripImpact) {
                $first = $rows->first();

                return [
                    'name' => $first?->passenger?->name ?? 'Unknown',
                    'email' => $first?->passenger?->email ?? '—',
                    'rides' => $rows->count(),
                    'coverage' => round((float) $rows->sum('employer_contribution'), 2),
                    'co2_kg' => round((float) array_sum(array_map(
                        fn (Booking $b) => (float) ($tripImpact[$b->trip_id]['co2_kg'] ?? 0),
                        $rows->all()
                    )), 2),
                ];
            })
            ->values()
            ->sortByDesc('coverage')
            ->values();

        $perDay = $bookings
            ->groupBy(fn (Booking $b) => $b->created_at->toDateString())
            ->map(fn (Collection $rows) => [
                'date' => $rows->first()->created_at->toDateString(),
                'rides' => $rows->count(),
                'coverage' => round((float) $rows->sum('employer_contribution'), 2),
                'co2_kg' => round((float) array_sum(array_map(
                    fn (Booking $b) => (float) ($tripImpact[$b->trip_id]['co2_kg'] ?? 0),
                    $rows->all()
                )), 2),
            ])
            ->values()
            ->sortBy('date')
            ->values();

        return [
            'employer' => $employer,
            'month' => $start,
            'staff_covered' => $staffCovered,
            'rides_covered' => $ridesCovered,
            'coverage_spent' => $coverageSpent,
            'co2_kg' => $co2,
            'fuel_litres' => $fuel,
            'trees' => $trees,
            'per_employee' => $perEmployee,
            'per_day' => $perDay,
        ];
    }

    private function distanceKm($trip): float
    {
        $points = $trip->waypoints()
            ->orderBy('sequence')
            ->get(['lat', 'lng'])
            ->map(fn ($w) => [(float) $w->lat, (float) $w->lng]);

        if ($points->count() >= 2) {
            return $this->pathKm($points->values()->all());
        }

        return (float) config("workride.corridor_distance_km.{$trip->corridor->value}", 15);
    }

    private function pathKm(array $points): float
    {
        $total = 0.0;

        for ($i = 1; $i < count($points); $i++) {
            [$lat1, $lng1] = $points[$i - 1];
            [$lat2, $lng2] = $points[$i];
            $total += $this->co2->distanceKm($lat1, $lng1, $lat2, $lng2);
        }

        return round($total, 2);
    }
}
