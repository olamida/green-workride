<?php

namespace App\Jobs;

use App\Enums\DriverScoreLevel;
use App\Models\DriverScore;
use App\Models\RideRating;
use App\Models\RoadEvent;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

/**
 * Weekly driver scoreboard (guide §2.4 "Driver Scores" + §8 governance):
 * one aggregate snapshot per driver per week, scoring rides completed,
 * punctuality, mutual ratings, road-intelligence reports and green points.
 * Level bands come from DriverScoreLevel::fromScore().
 */
class CalculateDriverScoresJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?string $periodStart = null,
        public ?string $periodEnd = null,
    ) {}

    public function handle(): int
    {
        $periodEnd = $this->periodEnd ? Carbon::parse($this->periodEnd) : today();
        $periodStart = $this->periodStart ? Carbon::parse($this->periodStart) : $periodEnd->copy()->subWeek()->addDay();

        $periodStartDate = $periodStart->toDateString();
        $periodEndDate = $periodEnd->toDateString();

        $tripRows = Trip::query()
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$periodStartDate.' 00:00:00', $periodEndDate.' 23:59:59'])
            ->selectRaw('driver_id, COUNT(*) as rides_completed')
            ->groupBy('driver_id')
            ->get();

        $scored = 0;

        foreach ($tripRows as $row) {
            $driver = User::find($row->driver_id);

            if (! $driver) {
                continue;
            }

            $rating = RideRating::query()
                ->where('ratee_id', $driver->id)
                ->whereBetween('created_at', [$periodStartDate.' 00:00:00', $periodEndDate.' 23:59:59'])
                ->selectRaw('AVG(rating) as avg, COUNT(*) as count')
                ->first();

            $potholes = RoadEvent::query()
                ->where('user_id', $driver->id)
                ->whereBetween('created_at', [$periodStartDate.' 00:00:00', $periodEndDate.' 23:59:59'])
                ->count();

            $rides = (int) $row->rides_completed;
            $onTimeRate = 100.0; // arrival-time tracking is a RoadLab 2027 upgrade; default to perfect punctuality
            $ratingAvg = $rating && $rating->count > 0 ? round((float) $rating->avg, 2) : 5.0;

            $ridesComponent = min(40, $rides * 8);
            $onTimeComponent = $onTimeRate * 0.2;
            $ratingComponent = $ratingAvg * 6;
            $potholeComponent = min(10, $potholes * 2);
            $greenComponent = min(10, (int) $driver->green_points * 0.1);

            $score = (int) round($ridesComponent + $onTimeComponent + $ratingComponent + $potholeComponent + $greenComponent);
            $score = min(100, max(0, $score));

            DriverScore::updateOrCreate(
                ['user_id' => $driver->id, 'period_start' => $periodStartDate],
                [
                    'period_end' => $periodEndDate,
                    'rides_completed' => $rides,
                    'on_time_rate' => $onTimeRate,
                    'rating_avg' => $ratingAvg,
                    'pothole_reports' => $potholes,
                    'green_points_earned' => (int) $driver->green_points,
                    'score' => $score,
                    'level' => DriverScoreLevel::fromScore($score),
                ]
            );

            $scored++;
        }

        return $scored;
    }
}
