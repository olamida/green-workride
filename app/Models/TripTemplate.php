<?php

namespace App\Models;

use App\Enums\Corridor;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A driver's saved recurring commute (guide §11 driver tooling): one tap to
 * republish the same corridor at the same time, without re-entering origin,
 * destination, vehicle and seats every morning. Publishes still go through
 * TripService::publish so fixed anti-surge fares and the atomic seat lock are
 * never bypassed — a template is a pre-filled form, not a parallel publisher.
 */
class TripTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'name',
        'corridor',
        'route_name',
        'origin_text',
        'destination_text',
        'departure_time',
        'days',
        'vehicle_id',
        'total_seats',
        'fare_per_seat',
        'is_free_volunteer',
        'women_only',
        'waypoints',
        'is_active',
        'times_used',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'corridor' => Corridor::class,
            'days' => 'array',
            'departure_time' => 'string',
            'total_seats' => 'integer',
            'fare_per_seat' => 'decimal:2',
            'is_free_volunteer' => 'boolean',
            'women_only' => 'boolean',
            'waypoints' => 'array',
            'is_active' => 'boolean',
            'times_used' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function corridorLabel(): string
    {
        return $this->corridor?->label() ?? '—';
    }

    /**
     * Route title: "Kubwa Junction → Federal Secretariat", falling back to the
     * corridor label when origin/destination were never saved.
     */
    public function routeTitle(): string
    {
        if ($this->origin_text && $this->destination_text) {
            return "{$this->origin_text} → {$this->destination_text}";
        }

        return $this->corridorLabel();
    }

    /**
     * Human-readable run days ("Mon–Fri", "Weekends", "Every day").
     */
    public function daysLabel(): string
    {
        $days = $this->days ?? [];

        if (empty($days)) {
            return 'Every day';
        }

        $labels = collect(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])
            ->mapWithKeys(fn ($day) => [$day => ucfirst(substr($day, 0, 3))]);

        $run = $labels->only($days)->values();

        if ($run->count() === 5 && array_diff(['mon', 'tue', 'wed', 'thu', 'fri'], $days) === []) {
            return 'Mon–Fri';
        }

        return $run->implode(' · ');
    }

    /**
     * Whether the template publishes on a given date. An empty day list means
     * "runs any day".
     */
    public function runsOn(CarbonInterface $date): bool
    {
        $days = $this->days ?? [];

        if (empty($days)) {
            return true;
        }

        return in_array(strtolower($date->format('D')), array_map('strtolower', $days), true);
    }

    /**
     * The next future departure date for this template: today's time if it is
     * still ahead and today is a run day, otherwise tomorrow if it is a run
     * day. Anything further out is out of the single-tap publish window —
     * "publish this week" exists for next-week runs. Returns null when neither
     * today nor tomorrow runs the template.
     */
    public function nextDeparture(?CarbonInterface $after = null): ?CarbonInterface
    {
        $after = $after ?? now();

        [$hour, $minute] = array_pad(explode(':', (string) $this->departure_time), 2, '00');

        $candidate = $after->copy()->setTime((int) $hour, (int) $minute)->startOfMinute();

        if ($candidate->gt($after) && $this->runsOn($candidate)) {
            return $candidate;
        }

        $candidate = $after->copy()->addDay()->setTime((int) $hour, (int) $minute)->startOfMinute();

        if ($this->runsOn($candidate)) {
            return $candidate;
        }

        return null;
    }

    /**
     * Stamp a successful template publish ("Publish today" usage counter).
     */
    public function markUsed(CarbonInterface $at): void
    {
        $this->update([
            'times_used' => $this->times_used + 1,
            'last_used_at' => $at,
        ]);
    }
}
