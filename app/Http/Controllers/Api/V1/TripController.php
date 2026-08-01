<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Corridor;
use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\User;
use App\Services\TripMatchingService;
use App\Services\TripService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TripController extends Controller
{
    public function __construct(private TripService $trips) {}

    public function index(Request $request, TripMatchingService $matcher)
    {
        $data = $request->validate([
            'corridor' => ['nullable', Rule::enum(Corridor::class)],
            'from_lat' => ['required', 'numeric', 'between:-90,90'],
            'from_lng' => ['required', 'numeric', 'between:-180,180'],
            'within_minutes' => ['nullable', 'integer', 'min:5', 'max:180'],
        ]);

        $corridor = isset($data['corridor']) ? Corridor::from($data['corridor']) : null;

        $trips = $matcher->findMatches(
            (float) $data['from_lat'],
            (float) $data['from_lng'],
            $corridor,
            isset($data['within_minutes']) ? (int) $data['within_minutes'] : null,
        );

        return response()->json([
            'trips' => $trips->map(fn (Trip $trip) => $this->tripPayload($trip)),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $isFreeVolunteer = $request->boolean('is_free_volunteer');

        $this->assertCanPublish($user, $isFreeVolunteer);

        $data = $request->validate($this->publishRules());

        $trip = $this->trips->publish($user, $data);

        return response()->json([
            'message' => 'Trip published.',
            'trip' => $this->tripPayload($trip),
        ], 201);
    }

    public function show(Trip $trip)
    {
        $trip->load(['driver', 'vehicle', 'waypoints', 'bookings.passenger']);

        return response()->json(['trip' => $this->tripPayload($trip, true)]);
    }

    public function updateLocation(Request $request, Trip $trip)
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $trip = $this->trips->updateLocation($trip, $request->user(), (float) $data['lat'], (float) $data['lng']);

        return response()->json([
            'message' => 'Location updated.',
            'trip' => $this->tripPayload($trip),
        ]);
    }

    public function start(Request $request, Trip $trip)
    {
        $trip = $this->trips->start($trip, $request->user());

        return response()->json([
            'message' => 'Trip started.',
            'trip' => $this->tripPayload($trip),
        ]);
    }

    public function complete(Request $request, Trip $trip)
    {
        $trip = $this->trips->completeTrip($trip, $request->user());

        return response()->json([
            'message' => 'Trip completed.',
            'trip' => $this->tripPayload($trip),
        ]);
    }

    public function cancel(Request $request, Trip $trip)
    {
        $trip = $this->trips->cancelTrip($trip, $request->user(), $request->input('reason'));

        return response()->json([
            'message' => 'Trip cancelled.',
            'trip' => $this->tripPayload($trip),
        ]);
    }

    private function publishRules(): array
    {
        return [
            'corridor' => ['required', Rule::enum(Corridor::class)],
            'origin_text' => ['required', 'string', 'max:255'],
            'destination_text' => ['required', 'string', 'max:255'],
            'total_seats' => ['required', 'integer', 'min:1', 'max:60'],
            'departure_time' => ['required', 'date', 'after:now'],
            'is_free_volunteer' => ['sometimes', 'boolean'],
            'current_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'current_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'waypoints' => ['nullable', 'array'],
            'waypoints.*.label' => ['required_with:waypoints', 'string', 'max:255'],
            'waypoints.*.lat' => ['required_with:waypoints', 'numeric', 'between:-90,90'],
            'waypoints.*.lng' => ['required_with:waypoints', 'numeric', 'between:-180,180'],
        ];
    }

    private function assertCanPublish(User $user, bool $isFreeVolunteer): void
    {
        if ($isFreeVolunteer) {
            abort_unless($user->canDriveVolunteer(), 403, 'Workplace verification (Level 1) is required to publish free volunteer rides.');

            return;
        }

        abort_unless($user->canDrivePaid(), 403, 'Driver verification (Level 3) is required to publish paid rides.');
    }

    private function tripPayload(Trip $trip, bool $detailed = false): array
    {
        $payload = [
            'id' => $trip->id,
            'route_name' => $trip->route_name,
            'corridor' => $trip->corridor->value,
            'origin_text' => $trip->origin_text,
            'destination_text' => $trip->destination_text,
            'current_lat' => $trip->current_lat ? (float) $trip->current_lat : null,
            'current_lng' => $trip->current_lng ? (float) $trip->current_lng : null,
            'total_seats' => $trip->total_seats,
            'available_seats' => $trip->available_seats,
            'fare_per_seat' => (float) $trip->fare_per_seat,
            'is_free_volunteer' => $trip->is_free_volunteer,
            'status' => $trip->status->value,
            'departure_time' => $trip->departure_time->toIso8601String(),
            'match_distance_m' => $trip->match_distance_m ?? null,
            'driver' => $trip->driver ? [
                'id' => $trip->driver->id,
                'name' => $trip->driver->name,
                'verification_level' => $trip->driver->verification_level->value,
            ] : null,
        ];

        if ($detailed) {
            $payload['vehicle'] = $trip->vehicle ? [
                'id' => $trip->vehicle->id,
                'plate_number' => $trip->vehicle->plate_number,
                'make' => $trip->vehicle->make,
                'model' => $trip->vehicle->model,
                'color' => $trip->vehicle->color,
                'seats' => $trip->vehicle->seats,
            ] : null;
            $payload['waypoints'] = $trip->waypoints->map(fn ($wp) => [
                'label' => $wp->label,
                'lat' => (float) $wp->lat,
                'lng' => (float) $wp->lng,
                'sequence' => $wp->sequence,
            ]);
            $payload['bookings'] = $trip->bookings->map(fn ($booking) => [
                'id' => $booking->id,
                'passenger' => $booking->passenger?->name,
                'status' => $booking->status->value,
                'payment_method' => $booking->payment_method->value,
            ]);
        }

        return $payload;
    }
}
