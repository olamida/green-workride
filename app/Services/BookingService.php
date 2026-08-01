<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\TripStatus;
use App\Events\BookingCancelled;
use App\Events\BookingConfirmed;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Atomic seat booking with wallet holds.
 *
 * book() serializes on the trip row (SELECT ... FOR UPDATE) so concurrent
 * bookings cannot oversell seats. Wallet holds are recorded idempotently and
 * captured on boarding or refunded on cancellation.
 */
class BookingService
{
    public function __construct(
        private WalletService $wallet,
        private PricingService $pricing,
        private RideCreditService $rideCredits,
        private EmployerService $employers,
        private EmployerLedgerService $employerLedger,
    ) {}

    public function book(Trip $trip, User $passenger, array $data): Booking
    {
        if ($trip->driver_id === $passenger->id) {
            throw ValidationException::withMessages(['trip' => 'You cannot book your own trip.']);
        }

        try {
            return DB::transaction(function () use ($trip, $passenger, $data) {
                $trip = Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();

                if (! in_array($trip->status, [TripStatus::Scheduled, TripStatus::Active], true)) {
                    throw ValidationException::withMessages(['trip' => 'This trip is no longer available.']);
                }

                if ($trip->departure_time->isPast()) {
                    throw ValidationException::withMessages(['trip' => 'This trip has already departed.']);
                }

                if ($trip->available_seats < 1) {
                    throw ValidationException::withMessages(['trip' => 'This trip is full.']);
                }

                if ($trip->bookings()->where('passenger_id', $passenger->id)->exists()) {
                    throw ValidationException::withMessages(['trip' => 'You already have a booking on this trip.']);
                }

                $paymentMethod = $this->resolvePaymentMethod($trip, $data);

                $fare = $trip->fare_per_seat;
                [$employerContribution, $employerCoverage, $employer] = $this->employers->bestCoverage($trip, $passenger, (float) $fare);

                $booking = $trip->bookings()->create([
                    'passenger_id' => $passenger->id,
                    'pickup_lat' => $data['pickup_lat'] ?? null,
                    'pickup_lng' => $data['pickup_lng'] ?? null,
                    'status' => BookingStatus::Confirmed,
                    'fare_paid' => $paymentMethod === PaymentMethod::RideCredit ? 0 : $fare,
                    'employer_id' => $employer?->id,
                    'employer_contribution' => $employerContribution,
                    'employer_coverage' => $employerCoverage?->value,
                    'payment_method' => $paymentMethod,
                ]);

                if ($this->needsHold($trip, $paymentMethod)) {
                    $this->wallet->holdForBooking($booking);
                }

                if ($paymentMethod === PaymentMethod::RideCredit) {
                    $this->rideCredits->createOwedRide($passenger, $trip, $booking);
                }

                $trip->decrement('available_seats');

                event(new BookingConfirmed($booking->load('passenger')));

                return $booking;
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === 23000) {
                throw ValidationException::withMessages(['trip' => 'You already have a booking on this trip.']);
            }

            throw $exception;
        }
    }

    public function cancelBooking(Booking $booking, User $actor, ?string $reason = null): Booking
    {
        $isParticipant = $booking->passenger_id === $actor->id
            || $booking->trip->driver_id === $actor->id
            || $actor->isAdmin();

        if (! $isParticipant) {
            throw ValidationException::withMessages(['booking' => 'You cannot cancel this booking.']);
        }

        if (in_array($booking->status, [BookingStatus::Completed, BookingStatus::Cancelled, BookingStatus::NoShow], true)) {
            throw ValidationException::withMessages(['booking' => 'This booking is already closed.']);
        }

        return DB::transaction(function () use ($booking) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $booking->update(['status' => BookingStatus::Cancelled]);

            if ($this->needsHold($booking->trip, $booking->payment_method)) {
                $this->wallet->releaseHold($booking);
            }

            if ($booking->payment_method === PaymentMethod::RideCredit) {
                $this->rideCredits->cancelRideCredit($booking);
            }

            $this->employerLedger->refund($booking);

            $booking->trip->increment('available_seats');

            event(new BookingCancelled($booking->fresh()));

            return $booking->fresh();
        });
    }

    public function board(Booking $booking, User $driver): Booking
    {
        $this->assertTripDriver($booking, $driver);

        if ($booking->status !== BookingStatus::Confirmed) {
            throw ValidationException::withMessages(['booking' => 'Only confirmed bookings can be boarded.']);
        }

        return DB::transaction(function () use ($booking) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $booking->update(['status' => BookingStatus::Boarded]);

            $this->settle($booking);

            return $booking->fresh();
        });
    }

    public function noShow(Booking $booking, User $driver): Booking
    {
        $this->assertTripDriver($booking, $driver);

        if ($booking->status !== BookingStatus::Confirmed) {
            throw ValidationException::withMessages(['booking' => 'Only confirmed bookings can be marked as no-show.']);
        }

        return DB::transaction(function () use ($booking) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $booking->update(['status' => BookingStatus::NoShow]);

            if ($this->needsHold($booking->trip, $booking->payment_method)) {
                $capturePercent = (float) config('workride.no_show_capture_percent', 50);
                $this->wallet->captureForBooking($booking, round($booking->passengerHoldAmount() * $capturePercent / 100, 2));
                $this->employerLedger->coverPartial($booking, $capturePercent);
            }

            if ($booking->payment_method === PaymentMethod::RideCredit) {
                $this->rideCredits->cancelRideCredit($booking);
            }

            $booking->trip->increment('available_seats');

            return $booking->fresh();
        });
    }

    /**
     * Capture the held fare (or log cash collection) once the service is
     * delivered. Used by board() and by TripService::completeTrip().
     * When the Time-Bank feature is on, the driver's earning (fare minus
     * commission, union fee and insurance) is credited to their earned wallet.
     */
    public function settle(Booking $booking): void
    {
        $method = $booking->payment_method;

        if ($method === PaymentMethod::Cash) {
            $this->wallet->logCashCollection($booking);
            $this->employerLedger->cover($booking);

            return;
        }

        if (in_array($method, [PaymentMethod::Wallet, PaymentMethod::SubsidyCredit], true)) {
            $this->wallet->captureForBooking($booking);
            $this->employerLedger->cover($booking);

            if (config('workride.time_bank.enabled')) {
                $this->creditDriverEarning($booking);
            }
        }
    }

    /**
     * Credit the driver's earned balance for a paid digital ride. Idempotent
     * keyed on `EARN-{bookingId}` so a double settle never double-pays.
     */
    private function creditDriverEarning(Booking $booking): void
    {
        $fare = (float) $booking->fare_paid;
        $earning = $this->pricing->driverEarning($fare);

        if ($earning <= 0) {
            return;
        }

        $this->wallet->creditEarned(
            $booking->trip->driver,
            $earning,
            "EARN-{$booking->id}",
            "Trip earnings — Trip #{$booking->trip_id}",
            ['booking_id' => $booking->id, 'trip_id' => $booking->trip_id, 'fare' => $fare],
        );
    }

    private function resolvePaymentMethod(Trip $trip, array $data): PaymentMethod
    {
        if ($trip->is_free_volunteer) {
            return PaymentMethod::Free;
        }

        return match ($data['payment_method'] ?? 'wallet') {
            'cash' => PaymentMethod::Cash,
            'subsidy_credit' => PaymentMethod::SubsidyCredit,
            'ride_credit' => PaymentMethod::RideCredit,
            default => PaymentMethod::Wallet,
        };
    }

    private function needsHold(Trip $trip, PaymentMethod $method): bool
    {
        if ($trip->is_free_volunteer) {
            return false;
        }

        if ($method === PaymentMethod::RideCredit) {
            return false;
        }

        return in_array($method, [PaymentMethod::Wallet, PaymentMethod::SubsidyCredit], true);
    }

    private function assertTripDriver(Booking $booking, User $user): void
    {
        if ($booking->trip->driver_id !== $user->id) {
            throw ValidationException::withMessages(['booking' => 'Only the trip driver can perform this action.']);
        }
    }
}
