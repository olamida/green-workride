<?php

namespace App\Services;

use App\Enums\Corridor;
use App\Models\Trip;
use App\Models\TripTemplate;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Driver trip templates (guide §11 driver tooling): save a corridor/time/
 * vehicle once and republish with one tap. A template is a pre-filled form,
 * never a parallel publisher — every publish still routes through
 * TripService::publish so fixed anti-surge fares and the atomic seat lock are
 * never bypassed. fare_per_seat is stored for display only; the published
 * trip always carries PricingService's fare.
 */
class TripTemplateService
{
    public function __construct(private TripService $trips) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(User $driver, array $data): TripTemplate
    {
        $corridor = Corridor::from($data['corridor']);

        return TripTemplate::create([
            'driver_id' => $driver->id,
            'name' => $data['name'] ?? $corridor->short().' '.(string) ($data['departure_time'] ?? '07:00'),
            'corridor' => $corridor,
            'route_name' => $data['route_name'] ?? $corridor->label(),
            'origin_text' => $data['origin_text'] ?? null,
            'destination_text' => $data['destination_text'] ?? null,
            'departure_time' => $data['departure_time'] ?? '07:00',
            'days' => $data['days'] ?? [],
            'vehicle_id' => isset($data['vehicle_id']) && $data['vehicle_id'] !== '' ? (int) $data['vehicle_id'] : null,
            'total_seats' => (int) ($data['total_seats'] ?? 4),
            'fare_per_seat' => isset($data['fare_per_seat']) && $data['fare_per_seat'] !== '' ? (float) $data['fare_per_seat'] : null,
            'is_free_volunteer' => (bool) ($data['is_free_volunteer'] ?? false),
            'women_only' => (bool) ($data['women_only'] ?? false),
            'waypoints' => $data['waypoints'] ?? [],
            'is_active' => true,
            'times_used' => 0,
        ]);
    }

    /**
     * @return Collection<int, TripTemplate>
     */
    public function forDriver(User $driver): Collection
    {
        return TripTemplate::query()
            ->with('vehicle')
            ->where('driver_id', $driver->id)
            ->orderBy('is_active', 'desc')
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    /**
     * "Save this commute" — persist a just-published trip as a template so the
     * driver can republish the same route tomorrow with one tap. Safe to call
     * with a template already saved: it returns the existing row untouched.
     */
    public function saveFromTrip(Trip $trip, string $name = ''): TripTemplate
    {
        return TripTemplate::updateOrCreate(
            ['driver_id' => $trip->driver_id, 'route_name' => $trip->route_name],
            [
                'name' => $name !== '' ? $name : 'Morning commute · '.$trip->corridor->short(),
                'corridor' => $trip->corridor,
                'route_name' => $trip->route_name,
                'origin_text' => $trip->origin_text,
                'destination_text' => $trip->destination_text,
                'departure_time' => $trip->departure_time->format('H:i'),
                'days' => [],
                'vehicle_id' => $trip->vehicle_id,
                'total_seats' => $trip->total_seats,
                'fare_per_seat' => $trip->fare_per_seat,
                'is_free_volunteer' => $trip->is_free_volunteer,
                'women_only' => $trip->women_only,
                'waypoints' => $trip->waypoints ?? [],
                'is_active' => true,
            ],
        );
    }

    /**
     * One-tap republish for the template's next run day. Always routes through
     * TripService::publish — the template's fare and seats are pre-fill hints,
     * never trusted inputs.
     */
    public function publish(TripTemplate $template, ?CarbonInterface $date = null): Trip
    {
        return $this->publishFromTemplate($template, $date, false, []);
    }

    /**
     * "Publish this week": the next run day plus repeat companions for every
     * subsequent run day before the end of the current week. The repeat horizon
     * is capped by the configured `trip_templates.horizon_days` and never
     * bleeds into next week — a Monday publish stops at Sunday whatever the
     * configured horizon. Reuses TripService's repeat-group machinery, so
     * re-running is a no-op per (repeat_group, departure).
     *
     * @return int number of Trip rows created (primary + companions)
     */
    public function publishWeek(TripTemplate $template): int
    {
        $days = array_values($template->days ?? []);

        if (empty($days)) {
            $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        }

        $primary = $template->nextDeparture();

        $horizonDays = $primary
            ? min(
                (int) config('workride.trip_templates.horizon_days', 14),
                max(0, 7 - (int) $primary->dayOfWeek),
            )
            : null;

        $trip = $this->publishFromTemplate($template, null, true, $days, $horizonDays);

        return Trip::where('repeat_group', $trip->repeat_group)->count();
    }

    public function destroy(TripTemplate $template, User $driver): void
    {
        $this->assertOwner($template, $driver);
        $template->delete();
    }

    public function assertOwner(TripTemplate $template, User $driver): void
    {
        if ($template->driver_id !== $driver->id) {
            throw ValidationException::withMessages(['template' => 'Only the template owner can do this.']);
        }
    }

    /**
     * @param  array<int, string>  $repeatDays
     */
    private function publishFromTemplate(
        TripTemplate $template,
        ?CarbonInterface $date,
        bool $repeatWeek,
        array $repeatDays,
        ?int $horizonDays = null,
    ): Trip {
        if (! $template->is_active) {
            throw ValidationException::withMessages(['template' => 'This template is paused — resume it before publishing.']);
        }

        $date = $date ?? $template->nextDeparture();

        if (! $date) {
            throw ValidationException::withMessages(['template' => 'No upcoming run day found for this template.']);
        }

        if (blank($template->origin_text) || blank($template->destination_text)) {
            throw ValidationException::withMessages(['template' => 'This template has no origin or destination saved — publish from the trip form instead.']);
        }

        $data = [
            'corridor' => $template->corridor->value,
            'origin_text' => $template->origin_text,
            'destination_text' => $template->destination_text,
            'total_seats' => $template->total_seats,
            'departure_time' => $date,
            'is_free_volunteer' => $template->is_free_volunteer,
            'women_only' => $template->women_only,
            'vehicle_id' => $template->vehicle_id,
            'waypoints' => $template->waypoints ?? [],
            'repeat' => $repeatWeek,
            'repeat_days' => $repeatWeek ? $repeatDays : [],
        ];

        $trip = $this->trips->publish($template->driver, $data, $horizonDays);

        $template->markUsed(now());

        return $trip;
    }
}
