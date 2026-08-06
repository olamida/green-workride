<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Trip;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function index()
    {
        $user = auth()->user();
        $user->load([
            'bookings.trip.driver',
            'bookings.trip.vehicle',
            'trips.bookings.passenger',
        ]);

        // My Rides is split into three mental buckets so a rider lands on the
        // rides that need attention NOW:
        //   Active   — the vehicle is on the road, or leaving within the
        //              classic 30-minute "leaving soon" window.
        //   Upcoming — confirmed/requested seats on future scheduled trips.
        //   Past     — completed, cancelled or no-show rides (receipts,
        //              certificates, ratings).
        $leavingSoon = now()->copy()->addMinutes((int) config('workride.departure_window_minutes', 30));

        $activeBookings = $user->bookings->filter(function ($booking) use ($leavingSoon) {
            if (! in_array($booking->status->value, ['requested', 'confirmed', 'boarded'], true)) {
                return false;
            }

            $trip = $booking->trip;

            return $trip
                && in_array($trip->status->value, ['scheduled', 'active'], true)
                && ($trip->status->value === 'active' || $trip->departure_time?->lte($leavingSoon));
        })->values();

        $upcomingBookings = $user->bookings->filter(function ($booking) use ($leavingSoon) {
            if (! in_array($booking->status->value, ['requested', 'confirmed', 'boarded'], true)) {
                return false;
            }

            $trip = $booking->trip;

            return $trip
                && $trip->status->value === 'scheduled'
                && $trip->departure_time?->gt($leavingSoon);
        })->values();

        $pastBookings = $user->bookings
            ->reject(fn ($booking) => $activeBookings->contains($booking) || $upcomingBookings->contains($booking))
            ->values();

        return view('bookings.index', compact('user', 'activeBookings', 'upcomingBookings', 'pastBookings'));
    }

    public function book(Request $request, Trip $trip)
    {
        abort_unless($request->user()->canBook(), 403, 'Workplace verification (Level 1) is required to book rides.');

        $data = $request->validate([
            'payment_method' => ['required', Rule::in(['wallet', 'cash', 'subsidy_credit', 'ride_credit'])],
            'pickup_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        try {
            $booking = $this->bookings->book($trip, $request->user(), $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $this->applyReferral($request, $trip, $booking);

        return redirect()->route('bookings.index')
            ->with('status', 'Seat confirmed on '.$trip->route_name.' ('.($booking->payment_method->label()).').');
    }

    /**
     * Share-request (Sprint 3 §3.4): rider asks to join a shared trip before
     * committing money. The driver approves/declines from the ride or My Rides.
     */
    public function request(Request $request, Trip $trip)
    {
        abort_unless($request->user()->canBook(), 403, 'Verify your phone or workplace to request a seat.');

        $data = $request->validate([
            'share_code' => ['nullable', 'string', 'max:32'],
        ]);

        // Prefer the explicit form value, else the share page's session entry
        // (set by SafetyController::share) which survives guest → login.
        $data['share_code'] ??= $request->session()->pull("trip_share.{$trip->id}");

        try {
            $booking = $this->bookings->requestToJoin($trip, $request->user(), $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $this->applyReferral($request, $trip, $booking);

        return redirect()->route('bookings.index')
            ->with('status', 'Request sent to the driver — your seat is held once they approve.');
    }

    public function approve(Request $request, Booking $booking)
    {
        try {
            $this->bookings->approveRequest($booking, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', 'Request approved — seat held.');
    }

    public function decline(Request $request, Booking $booking)
    {
        try {
            $this->bookings->declineRequest($booking, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', 'Request declined — the rider has been notified.');
    }

    /**
     * Attribute a booking made via a shared ride link to the sharer. The
     * referral is held in the session (set by the public share page) so it
     * survives a guest → login round-trip, and is consumed once here.
     */
    private function applyReferral(Request $request, Trip $trip, Booking $booking): void
    {
        $ref = $request->session()->pull("trip_referral.{$trip->id}");

        if (! $ref || (int) $ref === $trip->driver_id || (int) $ref === $booking->passenger_id) {
            return;
        }

        $booking->update(['referred_by_user_id' => (int) $ref]);

        ActivityLog::log($booking->passenger, 'booking_referred', Booking::class, $booking->id, [
            'trip_id' => $trip->id,
            'referrer_id' => (int) $ref,
        ]);
    }

    public function cancel(Request $request, Booking $booking)
    {
        try {
            $this->bookings->cancelBooking($booking, $request->user(), $request->input('reason'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', 'Booking cancelled and refunded.');
    }

    public function board(Request $request, Booking $booking)
    {
        try {
            $this->bookings->board($booking, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', 'Passenger boarded. Fare captured.');
    }

    public function noShow(Request $request, Booking $booking)
    {
        try {
            $this->bookings->noShow($booking, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', 'Passenger marked as no-show.');
    }
}
