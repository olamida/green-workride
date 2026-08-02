<?php

namespace App\Services;

use App\Enums\ForecastEventType;
use App\Models\Booking;
use App\Models\Forecast;
use Illuminate\Support\Carbon;

/**
 * Demand forecasting (guide §9): Abuja demand follows religious, government
 * and weather calendars. Phase 1 is a transparent manual multiplier layered
 * over last-4-same-weekday booking averages; Phase 2 swaps the predictor for
 * a real ML job without touching the scheduling UI.
 */
class ForecastService
{
    /**
     * Phase 1 prediction: predicted = avg last-4 same-weekday bookings ×
     * event multiplier. Recommended extra vehicles = the gap the multiplier
     * opens up, expressed in full buses.
     */
    public function suggest(Carbon $date, string $corridor, float $multiplier): array
    {
        $windowStart = $date->copy()->subWeeks(4)->startOfDay();

        // Count boarded/completed bookings on the same weekday over the last 4
        // weeks. Weekday filtering happens in PHP so the same SQL runs on
        // MySQL (prod) and SQLite (tests) — the window is tiny.
        $sameWeekday = Booking::query()
            ->whereHas('trip', fn ($q) => $q->where('corridor', $corridor))
            ->whereIn('status', ['boarded', 'completed'])
            ->where('created_at', '>', $windowStart)
            ->where('created_at', '<=', $date->copy()->startOfDay())
            ->get()
            ->filter(fn (Booking $b) => $b->created_at->dayOfWeek === $date->dayOfWeek)
            ->count();

        $baseline = $sameWeekday / 4;

        $predicted = $baseline * $multiplier;

        $seatsPerVehicle = (int) config('workride.forecasts.seats_per_vehicle', 15);

        $recommended = max(0, (int) ceil($predicted / $seatsPerVehicle) - (int) floor($baseline / $seatsPerVehicle));

        return [
            'baseline' => round($baseline, 1),
            'predicted' => round($predicted, 1),
            'multiplier' => $multiplier,
            'recommended_extra_vehicles' => $recommended,
        ];
    }

    /**
     * The upcoming event-aware calendar for the Control Tower demand planner.
     */
    public function upcoming(int $days = 14): array
    {
        return Forecast::query()
            ->whereDate('date', '>=', today())
            ->whereDate('date', '<=', today()->addDays($days))
            ->with('createdBy')
            ->orderBy('date')
            ->orderBy('corridor')
            ->get()
            ->map(fn (Forecast $f) => [
                'date' => $f->date->toDateString(),
                'day_name' => $f->date->format('D'),
                'event_type' => $f->event_type->label(),
                'event_name' => $f->event_name,
                'corridor' => $f->corridor,
                'multiplier' => (float) $f->expected_demand_multiplier,
                'extra_vehicles' => $f->recommended_extra_vehicles,
                'notes' => $f->notes,
                'created_by' => $f->createdBy?->name,
            ])
            ->all();
    }

    /**
     * Auto-suggestion note when an event is added without a multiplier: derive
     * a sensible default from the event class.
     */
    public function defaultMultiplier(ForecastEventType $type): float
    {
        return match ($type) {
            ForecastEventType::Govt, ForecastEventType::Festive, ForecastEventType::FuelScarcity => 1.6,
            ForecastEventType::Church => 1.3,
            ForecastEventType::Weather => 1.4,
            ForecastEventType::Mosque => 0.7,
        };
    }
}
