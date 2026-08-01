<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
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

        return view('bookings.index', compact('user'));
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

        return redirect()->route('bookings.index')
            ->with('status', 'Seat confirmed on '.$trip->route_name.' ('.($booking->payment_method->label()).').');
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
