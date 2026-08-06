<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Trip;
use App\Services\ConnectGuideService;
use App\Services\GeofenceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Dynamic passenger-to-vehicle connect guide (last-mile at informal junctions).
 *
 * Participants only: live vehicle coordinates are sensitive, so the guide is
 * gated to the driver and confirmed/boarded passengers — the same participants
 * the private `trip.{id}` broadcast channel authorises. Opening the guide is a
 * read-only trust action (no money moves) and is written to the change-control
 * trail; terminal "arrived / vehicle left" states are derived client-side from
 * the geofence and existing booking status so they can never race a no-show
 * capture.
 */
class GuideController extends Controller
{
    public function __construct(
        private ConnectGuideService $guides,
        private GeofenceService $geofence,
    ) {}

    public function show(Trip $trip)
    {
        $user = auth()->user();

        abort_unless($trip->isParticipant($user), 403, 'Only trip participants can open the connect guide.');
        abort_unless(in_array($trip->status->value, ['scheduled', 'active'], true), 404, 'Guide is only available on live or scheduled trips.');

        $trip->load(['driver', 'vehicle', 'waypoints']);

        $target = $this->guides->targetFor($trip);
        $myBooking = $user->bookings()->where('trip_id', $trip->id)->whereIn('status', ['confirmed', 'boarded'])->first();

        ActivityLog::log($user, 'guide_opened', Trip::class, $trip->id, [
            'corridor' => $trip->corridor->value,
            'route' => $trip->route_name,
            'target_type' => $target['type'],
        ]);

        return view('trips.guide', [
            'trip' => $trip,
            'target' => $target,
            'myBooking' => $myBooking,
            'config' => [
                'trip_id' => $trip->id,
                'route_url' => route('trips.guide.route', $trip),
                'my_booking_id' => $myBooking?->id,
                'arrived_radius_m' => (int) config('workride.guide.arrived_radius_m', 50),
                'walking_speed_kmh' => (float) config('workride.guide.walking_speed_kmh', 5),
                'zoom_overview' => (int) config('workride.guide.zoom_overview', 14),
                'zoom_follow' => (int) config('workride.guide.zoom_follow', 16),
                're_route_threshold_m' => (int) config('workride.guide.re_route_threshold_m', 150),
            ],
        ]);
    }

    /**
     * Walking polyline for the guide's follow view. The passenger position is
     * known only on the client, so this endpoint accepts it per request and
     * falls back to a straight-line estimate when OSRM is unreachable.
     */
    public function route(Request $request, Trip $trip)
    {
        $user = $request->user();

        abort_unless($trip->isParticipant($user), 403, 'Only trip participants can use the connect guide.');

        $data = $request->validate([
            'from_lat' => ['required', 'numeric', 'between:-90,90'],
            'from_lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        if (! $this->geofence->isInsideFct((float) $data['from_lat'], (float) $data['from_lng'])) {
            throw ValidationException::withMessages(['from_lat' => 'Guide position must be inside the FCT.']);
        }

        $target = $this->guides->targetFor($trip);

        if ($target['lat'] === null || $target['lng'] === null) {
            throw ValidationException::withMessages(['target' => 'No boarding point shared yet — ask the driver to share a location.']);
        }

        $route = $this->guides->walkingRoute(
            ['lat' => (float) $data['from_lat'], 'lng' => (float) $data['from_lng']],
            ['lat' => (float) $target['lat'], 'lng' => (float) $target['lng']],
        );

        return response()->json([
            'distance_m' => $route['distance_m'],
            'duration_s' => $route['duration_s'],
            'points' => $route['points'],
            'provider' => $route['provider'],
        ]);
    }
}
