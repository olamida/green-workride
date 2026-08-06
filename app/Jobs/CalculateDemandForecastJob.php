<?php

namespace App\Jobs;

use App\Enums\Corridor;
use App\Models\Booking;
use App\Models\DemandForecast;
use App\Models\Forecast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

/**
 * Demand forecasting Phase 2 (guide §9): instead of the manual "avg ×
 * multiplier" suggestion, this job trains on the boarded/completed booking
 * history — the same weekday and hour over the last four weeks — and writes
 * per-corridor, per-hour predicted demand snapshots. Ops reads the learned
 * table to deploy vehicles ahead of demand, never after.
 */
class CalculateDemandForecastJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?string $forDate = null,
        public int $days = 14,
    ) {}

    public function handle(): int
    {
        $start = $this->forDate ? Carbon::parse($this->forDate)->startOfDay() : today();
        $windowStart = $start->copy()->subWeeks(4)->startOfDay();

        $corridors = Corridor::cases();
        $written = 0;

        foreach ($corridors as $corridor) {
            // Multipliers for the window, keyed by "Y-m-d" — the manual events
            // the Ops planner logged still layer on top of the learned signal.
            $eventMultipliers = Forecast::query()
                ->whereDate('date', '>=', $start->toDateString())
                ->whereDate('date', '<=', $start->copy()->addDays($this->days - 1)->toDateString())
                ->where(fn ($q) => $q->where('corridor', $corridor->value)->orWhereNull('corridor'))
                ->get()
                ->mapWithKeys(fn (Forecast $f) => [$f->date->toDateString() => (float) $f->expected_demand_multiplier]);

            for ($day = 0; $day < $this->days; $day++) {
                $date = $start->copy()->addDays($day);

                for ($hour = 5; $hour <= 21; $hour++) {
                    $baseline = $this->baseline($corridor->value, $date, $hour, $windowStart);

                    if ($baseline === 0.0) {
                        DemandForecast::query()
                            ->where('date', $date->toDateString())
                            ->where('hour', $hour)
                            ->where('corridor', $corridor->value)
                            ->delete();

                        continue;
                    }

                    $multiplier = $eventMultipliers[$date->toDateString()] ?? 1.0;

                    DemandForecast::updateOrCreate(
                        ['date' => $date->toDateString(), 'hour' => $hour, 'corridor' => $corridor->value],
                        [
                            'baseline' => $baseline,
                            'multiplier' => $multiplier,
                            'predicted' => round($baseline * $multiplier, 2),
                            'data_points' => $this->dataPoints($corridor->value, $date, $hour, $windowStart),
                        ]
                    );

                    $written++;
                }
            }
        }

        return $written;
    }

    /**
     * Average boarded/completed bookings for the same weekday + hour over the
     * last four weeks. Weekday/hour filtering happens in PHP so the same SQL
     * runs on MySQL (prod) and SQLite (tests).
     */
    private function baseline(string $corridor, Carbon $date, int $hour, Carbon $windowStart): float
    {
        $bookings = Booking::query()
            ->whereHas('trip', fn ($q) => $q->where('corridor', $corridor))
            ->whereIn('status', ['boarded', 'completed'])
            ->where('created_at', '>', $windowStart)
            ->where('created_at', '<=', $date->copy()->startOfDay())
            ->get()
            ->filter(fn (Booking $b) => $b->created_at->dayOfWeek === $date->dayOfWeek && (int) $b->created_at->format('G') === $hour)
            ->count();

        return round($bookings / 4, 2);
    }

    private function dataPoints(string $corridor, Carbon $date, int $hour, Carbon $windowStart): int
    {
        return Booking::query()
            ->whereHas('trip', fn ($q) => $q->where('corridor', $corridor))
            ->whereIn('status', ['boarded', 'completed'])
            ->where('created_at', '>', $windowStart)
            ->where('created_at', '<=', $date->copy()->startOfDay())
            ->get()
            ->filter(fn (Booking $b) => $b->created_at->dayOfWeek === $date->dayOfWeek && (int) $b->created_at->format('G') === $hour)
            ->count();
    }
}
