<?php

namespace App\Models;

use App\Enums\BusScheduleStatus;
use App\Enums\Corridor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A recurring supply backbone row (guide §6 Workflow 5 — Citymapper-style
 * "every 15 mins 6:30–9am"). One row says "Kubwa→CBD every 15 min Mon–Fri
 * 06:30–09:00"; the nightly job materialises real Trip rows for today +
 * tomorrow so the normal board/booking/GTFS machinery all just works.
 */
class BusSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'vehicle_id',
        'driver_id',
        'departure_time',
        'end_time',
        'frequency_minutes',
        'days_of_week',
        'status',
        'workplace_id',
    ];

    protected function casts(): array
    {
        return [
            'departure_time' => 'string',
            'end_time' => 'string',
            'frequency_minutes' => 'integer',
            'days_of_week' => 'array',
            'workplace_id' => 'integer',
            'status' => BusScheduleStatus::class,
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(GtfsRoute::class, 'route_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function workplace(): BelongsTo
    {
        return $this->belongsTo(Workplace::class, 'workplace_id');
    }

    public function isActive(): bool
    {
        return $this->status === BusScheduleStatus::Active;
    }

    public function runsOn(string $day): bool
    {
        return in_array($day, $this->days_of_week ?? [], true);
    }

    /**
     * Corridor for the materialised trips, derived from the GTFS route.
     */
    public function corridor(): Corridor
    {
        $corridor = $this->route?->corridor;

        return $corridor !== null && Corridor::tryFrom($corridor) !== null
            ? Corridor::from($corridor)
            : Corridor::KubwaCbd;
    }

    /**
     * Human route label for the materialised trips (falls back to the corridor).
     */
    public function routeLabel(): string
    {
        return $this->route?->route_long_name
            ?? $this->route?->route_short_name
            ?? $this->corridor()->label();
    }

    /**
     * Departure times (H:i) for one calendar day, honouring the frequency
     * window. A null end_time yields a single departure.
     *
     * @return list<string>
     */
    public function departureTimes(): array
    {
        $times = [];

        $start = Carbon::parse($this->departure_time);
        $end = $this->end_time !== null
            ? Carbon::parse($this->end_time)
            : $start->copy();

        for ($t = $start->copy(); ! $t->gt($end); $t = $t->copy()->addMinutes($this->frequency_minutes)) {
            $times[] = $t->format('H:i');
        }

        return $times;
    }

    /**
     * The idempotency reference for one materialised slot.
     */
    public function referenceFor(string $date, string $time): string
    {
        return sprintf('SCHED-%d-%s-%s', $this->id, $date, str_replace(':', '', $time));
    }
}
