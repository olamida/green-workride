<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Trip;
use Illuminate\Http\Request;

/**
 * Safety pack — public ride sharing page + one-tap SOS that lands in the
 * Ops Control Tower's change-control trail (guide §10 stakeholder + §8).
 */
class SafetyController extends Controller
{
    /**
     * Public, guest-safe share page for a trip ("send this to your colleague").
     * Deliberately minimal: corridor, departure, driver + verification badge,
     * seats and fare — with a sign-in CTA. No live location is streamed.
     */
    public function share(Trip $trip)
    {
        abort_unless(in_array($trip->status->value, ['scheduled', 'active'], true), 404, 'This trip is no longer active.');

        $trip->load(['driver', 'vehicle']);

        return view('trips.share', compact('trip'));
    }

    /**
     * One-tap SOS for participants. Writes an auditable ActivityLog row the
     * Control Tower watches; no personal details beyond the reporter + trip.
     */
    public function sos(Request $request, Trip $trip)
    {
        $user = $request->user();

        abort_unless($trip->isParticipant($user), 403, 'Only trip participants can raise an SOS.');

        ActivityLog::log($user, 'sos', Trip::class, $trip->id, [
            'corridor' => $trip->corridor->value,
            'route_name' => $trip->route_name,
            'lat' => $request->float('lat') ?? $trip->current_lat,
            'lng' => $request->float('lng') ?? $trip->current_lng,
            'reported_at' => now()->toIso8601String(),
        ]);

        return back()->with('status', 'SOS sent. The WorkRide Control Tower has been alerted and your emergency contact is on standby.');
    }

    /**
     * Emergency contact profile (name + phone). Never shared with other riders;
     * used only if a driver/vehicle never shows up or an SOS is raised.
     */
    public function updateEmergencyContact(Request $request)
    {
        $data = $request->validate([
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $request->user()->update($data);

        return back()->with('status', 'Emergency contact saved.');
    }
}
