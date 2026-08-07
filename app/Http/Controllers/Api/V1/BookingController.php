<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Trip;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function index(Request $request)
    {
        $bookings = $request->user()->bookings()
            ->with(['trip.driver', 'trip.vehicle'])
            ->latest()
            ->get()
            ->map(fn (Booking $booking) => $this->payload($booking));

        return response()->json(['bookings' => $bookings]);
    }

    public function store(Request $request, Trip $trip)
    {
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(['wallet', 'cash', 'subsidy_credit', 'ride_credit'])],
            'pickup_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $booking = $this->bookings->book($trip, $request->user(), $data);

        return response()->json([
            'message' => 'Seat booked.',
            'booking' => $this->payload($booking),
        ], 201);
    }

    public function softHold(Request $request, Trip $trip): JsonResponse
    {
        $data = $request->validate([
            'payment_method' => ['nullable', Rule::in(['wallet', 'cash', 'subsidy_credit'])],
            'pickup_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $booking = $this->bookings->softHold($trip, $request->user(), $data);

        return response()->json([
            'message' => 'Seat held for '.config('workride.soft_hold.ttl_minutes', 3).' minutes.',
            'booking' => $this->payload($booking),
            'soft_hold_expires_at' => $booking->soft_hold_expires_at?->toIso8601String(),
        ], 201);
    }

    public function confirmSoftHold(Request $request, Booking $booking): JsonResponse
    {
        $booking = $this->bookings->confirmSoftHold($booking, $request->user());

        return response()->json([
            'message' => 'Seat confirmed.',
            'booking' => $this->payload($booking),
        ]);
    }

    public function cancel(Request $request, Booking $booking)
    {
        $booking = $this->bookings->cancelBooking($booking, $request->user(), $request->input('reason'));

        return response()->json([
            'message' => 'Booking cancelled.',
            'booking' => $this->payload($booking),
        ]);
    }

    public function board(Request $request, Booking $booking)
    {
        $booking = $this->bookings->board($booking, $request->user());

        return response()->json([
            'message' => 'Passenger boarded.',
            'booking' => $this->payload($booking),
        ]);
    }

    public function noShow(Request $request, Booking $booking)
    {
        $booking = $this->bookings->noShow($booking, $request->user());

        return response()->json([
            'message' => 'Passenger marked as no-show.',
            'booking' => $this->payload($booking),
        ]);
    }

    private function payload(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'trip_id' => $booking->trip_id,
            'status' => $booking->status->value,
            'fare_paid' => (float) $booking->fare_paid,
            'payment_method' => $booking->payment_method->value,
            'pickup_lat' => $booking->pickup_lat ? (float) $booking->pickup_lat : null,
            'pickup_lng' => $booking->pickup_lng ? (float) $booking->pickup_lng : null,
            'created_at' => $booking->created_at?->toIso8601String(),
            'trip' => $booking->trip ? [
                'id' => $booking->trip->id,
                'route_name' => $booking->trip->route_name,
                'corridor' => $booking->trip->corridor->value,
                'origin_text' => $booking->trip->origin_text,
                'destination_text' => $booking->trip->destination_text,
                'departure_time' => $booking->trip->departure_time->toIso8601String(),
                'status' => $booking->trip->status->value,
                'is_free_volunteer' => $booking->trip->is_free_volunteer,
                'driver' => $booking->trip->driver ? [
                    'id' => $booking->trip->driver->id,
                    'name' => $booking->trip->driver->name,
                ] : null,
            ] : null,
        ];
    }
}
