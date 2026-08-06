<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\TripInterestStatus;
use App\Enums\TripStatus;
use App\Events\BookingCancelled;
use App\Events\BookingConfirmed;
use App\Events\BookingDeclined;
use App\Events\BookingRequested;
use App\Events\TripSeatsUpdated;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\TripInterest;
use App\Models\User;
use App\Notifications\BookingRequested as BookingRequestedNotification;
use App\Notifications\RequestApproved;
use App\Notifications\RequestDeclined;
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

        $benefitsEligible = $passenger->canBookBenefits();

        // Women-only rides are a trust feature — phone-only riders can't join
        // until they complete formal verification (Level 1+).
        if ($trip->women_only && ! $benefitsEligible) {
            throw ValidationException::withMessages(['trip' => 'Women-only rides are reserved for verified workers.']);
        }

        if ($trip->women_only && $passenger->gender !== 'female') {
            throw ValidationException::withMessages(['trip' => 'This is a women-only ride.']);
        }

        // Free volunteer rides are the supply-bootstrap benefit — a phone-only
        // rider free-loading on them would gut the Green-Points incentive.
        if ($trip->is_free_volunteer && ! $benefitsEligible) {
            throw ValidationException::withMessages(['trip' => 'Volunteer rides are reserved for verified workers.']);
        }

        try {
            return DB::transaction(function () use ($trip, $passenger, $data, $benefitsEligible) {
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

                $paymentMethod = $this->resolvePaymentMethod($trip, $data, $benefitsEligible);

                $fare = $trip->fare_per_seat;
                [$employerContribution, $employerCoverage, $employer] = $benefitsEligible
                    ? $this->employers->bestCoverage($trip, $passenger, (float) $fare)
                    : [0.0, null, null];

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
                $trip->refresh();

                // An "I want this journey" interest becomes a real match.
                TripInterest::where('trip_id', $trip->id)
                    ->where('user_id', $passenger->id)
                    ->update(['status' => TripInterestStatus::Matched, 'matched_at' => now()]);

                event(new BookingConfirmed($booking->load('passenger')));
                event(TripSeatsUpdated::forTrip($trip));

                return $booking;
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === 23000) {
                throw ValidationException::withMessages(['trip' => 'You already have a booking on this trip.']);
            }

            throw $exception;
        }
    }

    /**
     * Share-request (Sprint 3 §3.4): a rider asks to join a shared trip
     * without committing money. No seat is held and no wallet move happens —
     * the driver's approve()/decline() decides. Reuses/updates an existing
     * booking row for this (trip, passenger) pair so the unique index holds.
     */
    public function requestToJoin(Trip $trip, User $passenger, array $data = []): Booking
    {
        if ($trip->driver_id === $passenger->id) {
            throw ValidationException::withMessages(['trip' => 'You cannot request to join your own trip.']);
        }

        if ($trip->is_free_volunteer && ! $passenger->canBookBenefits()) {
            throw ValidationException::withMessages(['trip' => 'Volunteer rides are reserved for verified workers.']);
        }

        if ($trip->women_only && (! $passenger->canBookBenefits() || $passenger->gender !== 'female')) {
            throw ValidationException::withMessages(['trip' => 'This is a women-only ride.']);
        }

        return DB::transaction(function () use ($trip, $passenger, $data) {
            $trip = Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();

            if (! in_array($trip->status, [TripStatus::Scheduled, TripStatus::Active], true)) {
                throw ValidationException::withMessages(['trip' => 'This trip is no longer accepting requests.']);
            }

            if ($trip->departure_time->isPast()) {
                throw ValidationException::withMessages(['trip' => 'This trip has already departed.']);
            }

            $booking = $trip->bookings()->where('passenger_id', $passenger->id)->first();

            if ($booking) {
                if (in_array($booking->status, [BookingStatus::Requested, BookingStatus::Confirmed, BookingStatus::Boarded], true)) {
                    throw ValidationException::withMessages(['trip' => 'You already have a pending or confirmed seat on this trip.']);
                }

                // Cancelled / no-show rows re-open as a fresh request.
                $booking->update([
                    'status' => BookingStatus::Requested,
                    'fare_paid' => 0,
                    'payment_method' => PaymentMethod::Wallet,
                    'share_code' => $data['share_code'] ?? $booking->share_code,
                ]);
            } else {
                $booking = $trip->bookings()->create([
                    'passenger_id' => $passenger->id,
                    'status' => BookingStatus::Requested,
                    'fare_paid' => 0,
                    'payment_method' => PaymentMethod::Wallet,
                    'share_code' => $data['share_code'] ?? null,
                ]);
            }

            $fresh = $booking->fresh();
            event(new BookingRequested($fresh->load('passenger')));
            $trip->driver->notify(new BookingRequestedNotification($fresh->load('trip')));

            return $fresh;
        });
    }

    /**
     * Driver approves a pending request: the seat is held (subsidy → earned →
     * cash priority, employer coverage applied) exactly like a wallet booking.
     * Approval fails loudly if the rider's balances cannot cover the fare, so
     * a "confirmed" booking always means real money is committed.
     */
    public function approveRequest(Booking $booking, User $driver): Booking
    {
        $this->assertTripDriver($booking, $driver);

        if ($booking->status !== BookingStatus::Requested) {
            throw ValidationException::withMessages(['booking' => 'Only pending requests can be approved.']);
        }

        return DB::transaction(function () use ($booking) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $trip = Trip::whereKey($booking->trip_id)->lockForUpdate()->firstOrFail();

            if (! in_array($trip->status, [TripStatus::Scheduled, TripStatus::Active], true)) {
                throw ValidationException::withMessages(['trip' => 'This trip is no longer available.']);
            }

            if ($trip->available_seats < 1) {
                throw ValidationException::withMessages(['trip' => 'This trip is full.']);
            }

            $fare = $trip->is_free_volunteer ? 0 : $trip->fare_per_seat;
            [$employerContribution, $employerCoverage, $employer] = $this->employers->bestCoverage($trip, $booking->passenger, (float) $fare);

            $booking->update([
                'status' => BookingStatus::Confirmed,
                'fare_paid' => $fare,
                'payment_method' => PaymentMethod::Wallet,
                'employer_id' => $employer?->id,
                'employer_contribution' => $employerContribution,
                'employer_coverage' => $employerCoverage?->value,
            ]);

            if ($this->needsHold($trip, PaymentMethod::Wallet)) {
                $this->wallet->holdForBooking($booking);
            }

            $trip->decrement('available_seats');
            $trip->refresh();

            TripInterest::where('trip_id', $trip->id)
                ->where('user_id', $booking->passenger_id)
                ->update(['status' => TripInterestStatus::Matched, 'matched_at' => now()]);

            $fresh = $booking->fresh();
            event(new BookingConfirmed($fresh->load('passenger')));
            event(TripSeatsUpdated::forTrip($trip));

            $booking->passenger->notify(new RequestApproved($fresh->load('trip')));

            return $fresh;
        });
    }

    /**
     * Driver declines a pending request. No seat, no money, no hold — pure
     * state flip plus an informational notification to the rider.
     */
    public function declineRequest(Booking $booking, User $driver): Booking
    {
        $this->assertTripDriver($booking, $driver);

        if ($booking->status !== BookingStatus::Requested) {
            throw ValidationException::withMessages(['booking' => 'Only pending requests can be declined.']);
        }

        return DB::transaction(function () use ($booking) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $booking->update(['status' => BookingStatus::Cancelled]);

            $fresh = $booking->fresh();
            event(new BookingDeclined($fresh));
            $booking->passenger->notify(new RequestDeclined($fresh->load('trip')));

            return $fresh;
        });
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

            // A pending share-request never holds a seat or money — cancelling
            // it is a pure state flip with no wallet/seat side effects.
            $heldSeat = ! in_array($booking->status, [BookingStatus::Requested], true);
            $booking->update(['status' => BookingStatus::Cancelled]);

            if ($heldSeat) {
                if ($this->needsHold($booking->trip, $booking->payment_method)) {
                    $this->wallet->releaseHold($booking);
                }

                if ($booking->payment_method === PaymentMethod::RideCredit) {
                    $this->rideCredits->cancelRideCredit($booking);
                }

                $this->employerLedger->refund($booking);

                $booking->trip->increment('available_seats');
                $booking->trip->refresh();

                TripInterest::where('trip_id', $booking->trip_id)
                    ->where('user_id', $booking->passenger_id)
                    ->update(['status' => TripInterestStatus::Pending, 'matched_at' => null]);
            }

            event(new BookingCancelled($booking->fresh()));
            event(TripSeatsUpdated::forTrip($booking->trip));

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
            $booking->trip->refresh();

            event(TripSeatsUpdated::forTrip($booking->trip));

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

    private function resolvePaymentMethod(Trip $trip, array $data, bool $benefitsEligible): PaymentMethod
    {
        if ($trip->is_free_volunteer) {
            return PaymentMethod::Free;
        }

        $method = match ($data['payment_method'] ?? 'wallet') {
            'cash' => PaymentMethod::Cash,
            'subsidy_credit' => PaymentMethod::SubsidyCredit,
            'ride_credit' => PaymentMethod::RideCredit,
            default => PaymentMethod::Wallet,
        };

        // Subsidised economies (MDA credits, Time-Bank seats) and ride credits
        // are verified-worker benefits — phone-only riders must pay wallet/cash.
        if (! $benefitsEligible && in_array($method, [PaymentMethod::SubsidyCredit, PaymentMethod::RideCredit], true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Subsidy and ride credits are reserved for verified workers. Pay with wallet or cash.',
            ]);
        }

        return $method;
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
