<?php

namespace App\Models;

use App\Enums\DriverScoreLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Weekly driver score snapshot: rides, punctuality, ratings, pothole reports,
 * green points → 0-100 score with a level band. The Control Tower scoreboard.
 */
class DriverScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period_start',
        'period_end',
        'rides_completed',
        'on_time_rate',
        'rating_avg',
        'pothole_reports',
        'green_points_earned',
        'score',
        'level',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'rides_completed' => 'integer',
            'on_time_rate' => 'decimal:2',
            'rating_avg' => 'decimal:2',
            'pothole_reports' => 'integer',
            'green_points_earned' => 'integer',
            'score' => 'integer',
            'level' => DriverScoreLevel::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Attach the latest weekly score snapshot to each trip in a collection as
     * a dynamic `driver_score` attribute (null when the driver has no score yet).
     * Uses one grouped query — never a per-trip lookup.
     */
    public static function attachLatestToTrips(Collection $trips): Collection
    {
        $trips = $trips->values();

        $ids = $trips
            ->map(fn ($trip) => $trip->driver_id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            foreach ($trips as $trip) {
                $trip->setAttribute('driver_score', null);
            }

            return $trips;
        }

        $latest = static::query()
            ->whereIn('user_id', $ids->all())
            ->orderByDesc('period_start')
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $rows) => $rows->first());

        foreach ($trips as $trip) {
            $trip->setAttribute('driver_score', $latest->get($trip->driver_id));
        }

        return $trips;
    }

    /**
     * Read a trip's attached scorecard snapshot (null when absent).
     */
    public static function forTrip($trip): ?self
    {
        return $trip->driver_score ?? null;
    }
}
