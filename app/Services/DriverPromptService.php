<?php

namespace App\Services;

use App\Enums\Corridor;
use App\Enums\DemandRequestStatus;
use App\Enums\DriverPromptStatus;
use App\Enums\TripStatus;
use App\Enums\VerificationLevel;
use App\Models\DemandRequest;
use App\Models\DriverPrompt;
use App\Models\Junction;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\DriverDemandPrompt;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Demand → supply prompting (gallery "service planning" Phase 3): when live
 * demand outstrips supply on a corridor, qualified drivers are nudged to
 * publish. Every prompt is idempotent per (driver, day, corridor) via the
 * unique reference, so the "1 push per driver per day per corridor" rate
 * limit is enforced by the schema itself — re-evaluations never double-nudge.
 *
 * Supply = seats on scheduled/active trips in the corridor's time window.
 * Demand = pending rider check-ins (attributed to the nearest junction, then
 * that junction's corridor) within the demand window.
 */
class DriverPromptService
{
    public function __construct(
        private GeofenceService $geofence,
        private NotificationService $notifications,
    ) {}

    /**
     * Idempotency key: one reference per driver per day per corridor.
     */
    public function referenceFor(User $driver, Corridor $corridor, ?CarbonInterface $at = null): string
    {
        $at = $at ?? now();

        return sprintf('PROMPT-%d-%s-%s', $driver->id, $at->format('Ymd'), $corridor->value);
    }

    /**
     * Drivers eligible to be prompted for a corridor right now: verified
     * drivers, not banned, not already driving, and — when the signal exists —
     * with corridor affinity (completed a trip on this corridor recently).
     * Falls back to any verified idle driver when no affinity is found.
     *
     * @return Collection<int, User>
     */
    public function qualifiedDrivers(Corridor $corridor, ?CarbonInterface $at = null, int $limit = 5): Collection
    {
        $at = $at ?? now();
        $affinityDays = (int) config('workride.driver_prompts.affinity_days', 14);

        $base = User::query()
            ->where('verification_level', '>=', VerificationLevel::DriverVerified->value)
            ->where('is_banned', false)
            ->whereDoesntHave('trips', fn ($q) => $q->where('status', TripStatus::Active));

        $affinity = (clone $base)
            ->whereHas('trips', function ($q) use ($corridor, $at, $affinityDays) {
                $q->where('corridor', $corridor->value)
                    ->where('status', TripStatus::Completed)
                    ->where('updated_at', '>=', $at->copy()->subDays($affinityDays));
            })
            ->orderByDesc('updated_at')
            ->get();

        if ($affinity->isNotEmpty()) {
            return $affinity->take($limit);
        }

        return $base
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Live people waiting on a corridor: pending check-ins in the demand
     * window, each attributed to its nearest junction within 1 km, grouped by
     * that junction's corridor. Mirrors DemandService::hotspots() attribution.
     */
    public function demandForCorridor(Corridor $corridor, ?CarbonInterface $at = null): int
    {
        $at = $at ?? now();
        $windowHours = (int) config('workride.driver_prompts.window_hours', 2);
        $radiusM = 1000;

        $junctions = Junction::where('is_active', true)->get();
        $checkins = DemandRequest::query()
            ->where('status', DemandRequestStatus::Pending)
            ->where('requested_at', '>=', $at->copy()->subHours($windowHours))
            ->get();

        $total = 0;

        foreach ($checkins as $checkin) {
            if ($checkin->pickup_lat === null || $checkin->pickup_lng === null) {
                continue;
            }

            $nearest = $junctions->first(function (Junction $junction) use ($checkin, $radiusM) {
                if ($junction->lat === null || $junction->lng === null) {
                    return false;
                }

                return $this->geofence->haversine(
                    (float) $checkin->pickup_lat,
                    (float) $checkin->pickup_lng,
                    (float) $junction->lat,
                    (float) $junction->lng,
                ) <= $radiusM;
            });

            if ($nearest?->corridor === $corridor->value) {
                $total += (int) $checkin->passengers_count;
            }
        }

        return $total;
    }

    /**
     * Seats available on the corridor within the supply window.
     */
    public function supplyForCorridor(Corridor $corridor, ?CarbonInterface $at = null): int
    {
        $at = $at ?? now();
        $supplyWindowHours = (int) config('workride.driver_prompts.supply_window_hours', 3);

        return (int) Trip::query()
            ->where('corridor', $corridor->value)
            ->whereIn('status', [TripStatus::Scheduled, TripStatus::Active])
            ->where('departure_time', '>=', $at->copy()->startOfMinute())
            ->where('departure_time', '<=', $at->copy()->addHours($supplyWindowHours)->endOfMinute())
            ->sum('available_seats');
    }

    /**
     * Whether the corridor triggers right now: at least min_passengers waiting
     * AND supply below demand / supply_divisor (the spec's predicted > 10 and
     * supply < predicted/3).
     */
    public function triggersFor(Corridor $corridor, ?CarbonInterface $at = null): bool
    {
        $at = $at ?? now();

        return $this->demandForCorridor($corridor, $at) >= (int) config('workride.driver_prompts.min_passengers', 10)
            && $this->supplyForCorridor($corridor, $at) < $this->demandForCorridor($corridor, $at) / max(1, (int) config('workride.driver_prompts.supply_divisor', 3));
    }

    /**
     * Create (and notify) prompts for qualified drivers on a corridor. No-op
     * when the corridor does not currently trigger — supply already covers
     * demand, so no driver is nudged.
     *
     * @return int number of NEW prompts (notifications sent)
     */
    public function promptForCorridor(Corridor $corridor, ?CarbonInterface $at = null, int $limit = 5): int
    {
        $at = $at ?? now();

        if (! $this->triggersFor($corridor, $at)) {
            return 0;
        }

        $people = $this->demandForCorridor($corridor, $at);
        $created = 0;

        foreach ($this->qualifiedDrivers($corridor, $at, $limit) as $driver) {
            $reference = $this->referenceFor($driver, $corridor, $at);

            $prompt = DriverPrompt::updateOrCreate(
                ['reference' => $reference],
                [
                    'driver_id' => $driver->id,
                    'corridor' => $corridor,
                    'people_count' => $people,
                    'time_band' => $at->format('H:i'),
                    'status' => DriverPromptStatus::Prompted,
                ],
            );

            if ($prompt->wasRecentlyCreated) {
                $created++;
                $prompt->update(['notified_at' => now()]);
                $this->notifications->send($driver, new DriverDemandPrompt($corridor, $people));
            }
        }

        return $created;
    }

    /**
     * The "Nudge 5 drivers" button: for every corridor that triggers, prompt
     * up to prompt_limit drivers.
     *
     * @return array{corridors: array<int, array{corridor: string, demand: int, supply: int, prompted: int}>}
     */
    public function nudgeAll(?CarbonInterface $at = null): array
    {
        $at = $at ?? now();
        $limit = (int) config('workride.driver_prompts.prompt_limit', 5);
        $results = [];

        foreach (Corridor::cases() as $corridor) {
            $results[] = [
                'corridor' => $corridor->value,
                'demand' => $this->demandForCorridor($corridor, $at),
                'supply' => $this->supplyForCorridor($corridor, $at),
                'prompted' => $this->triggersFor($corridor, $at)
                    ? $this->promptForCorridor($corridor, $at, $limit)
                    : 0,
            ];
        }

        return ['corridors' => $results];
    }

    /**
     * Live prompts for a driver (the board's "Demand wants you" panel): the
     * last 24 hours, newest first, with the corridor + people count.
     *
     * @return Collection<int, DriverPrompt>
     */
    public function activeFor(User $driver, ?CarbonInterface $at = null): Collection
    {
        $at = $at ?? now();

        return DriverPrompt::query()
            ->with('driver')
            ->where('driver_id', $driver->id)
            ->where('notified_at', '>=', $at->copy()->subDay())
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }
}
