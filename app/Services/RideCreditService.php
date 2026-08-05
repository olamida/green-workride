<?php

namespace App\Services;

use App\Enums\RideCreditStatus;
use App\Enums\TrustLedgerType;
use App\Enums\VerificationLevel;
use App\Models\Booking;
use App\Models\RideCredit;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Time-Bank: "Ride Now, Drive Later" community reciprocity.
 *
 * A passenger who cannot pay cash books with `payment_method = ride_credit`,
 * converting the fare into seats owed (ceil(fare / avg_fare_per_seat)). They
 * repay the seats by driving and carrying passengers — every passenger carried
 * clears one seat of their oldest open credit.
 */
class RideCreditService
{
    public function __construct(
        private PricingService $pricing,
        private TrustService $trust,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('workride.time_bank.enabled', false);
    }

    public function seatsFor(float $fare): int
    {
        $average = (float) config('workride.time_bank.avg_fare_per_seat', 600);

        return max(1, (int) ceil($fare / $average));
    }

    /**
     * Eligibility gates for owing a ride credit. Throws with a specific message
     * so the UI can explain exactly why the payment method is refused.
     */
    public function assertEligible(User $user, float $fare): void
    {
        if (! $this->enabled()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Time-Bank is not enabled yet. Please pay with wallet, cash or subsidy credits.',
            ]);
        }

        if ($user->verification_level->value < VerificationLevel::NinVerified->value) {
            throw ValidationException::withMessages([
                'payment_method' => 'NIN verification (Level 2) is required to book with ride credit.',
            ]);
        }

        if (! $user->vehicles()->exists()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Register a vehicle to qualify for ride credit — you repay by driving.',
            ]);
        }

        if ($this->hasOverdueCredit($user)) {
            // Best-effort cache of the flag for dashboards. The authoritative
            // check below reads the credit rows directly (they are committed
            // before the booking transaction, so they survive a rollback).
            $user->update(['has_overdue_ride_credit' => true]);

            throw ValidationException::withMessages([
                'payment_method' => 'You have an overdue ride credit. Repay it by driving, top up cash, or ask an admin to waive it.',
            ]);
        }

        $outstanding = $this->outstandingSeats($user);
        $max = (int) config('workride.time_bank.max_owed_seats', 3);
        $incoming = $this->seatsFor($fare);

        if ($outstanding + $incoming > $max) {
            throw ValidationException::withMessages([
                'payment_method' => "You already owe {$outstanding} seat(s) and may owe at most {$max} at a time.",
            ]);
        }
    }

    /**
     * Create the owed-seats debt for a ride booked with ride credit.
     */
    public function createOwedRide(User $user, Trip $trip, Booking $booking): RideCredit
    {
        $this->assertEligible($user, (float) $trip->fare_per_seat);

        $dueDays = (int) config('workride.time_bank.due_days', 7);

        $credit = RideCredit::create([
            'user_id' => $user->id,
            'trip_id' => $trip->id,
            'booking_id' => $booking->id,
            'seats_owed' => $this->seatsFor((float) $trip->fare_per_seat),
            'seats_repaid' => 0,
            'fare_value' => $trip->fare_per_seat,
            'due_date' => now()->addDays($dueDays),
            'status' => RideCreditStatus::Owed,
        ]);

        // The Trust funded this float — record it for the auditor. Idempotent
        // on TB-FLOAT-{bookingId}; a cancelled booking never reaches here.
        $this->trust->credit(
            TrustLedgerType::TimeBankFloat,
            (float) $trip->fare_per_seat,
            "TB-FLOAT-{$booking->id}",
            ['user_id' => $user->id, 'trip_id' => $trip->id, 'booking_id' => $booking->id, 'seats_owed' => $credit->seats_owed],
        );

        return $credit;
    }

    /**
     * The driver carried a passenger → repay one seat of their oldest open
     * credit. Called once per settled passenger on trip completion.
     */
    public function repayWithDrive(User $driver, ?Booking $booking = null): ?RideCredit
    {
        if (! $this->enabled()) {
            return null;
        }

        $credit = RideCredit::where('user_id', $driver->id)
            ->where('status', RideCreditStatus::Owed->value)
            ->whereColumn('seats_repaid', '<', 'seats_owed')
            ->orderBy('due_date')
            ->first();

        if (! $credit) {
            return null;
        }

        $credit->increment('seats_repaid');
        $credit->refresh();

        // Each repaid seat releases a share of the Trust float it was funded
        // from. Idempotent on the booking reference so double-settle never
        // double-releases.
        $seatShare = round((float) $credit->fare_value / max($credit->seats_owed, 1), 2);
        $bookingKey = $credit->booking_id ?? $credit->id;
        $this->trust->debit(
            TrustLedgerType::TimeBankFloat,
            $seatShare,
            "TB-REPAY-{$bookingKey}-{$credit->seats_repaid}",
            ['user_id' => $driver->id, 'ride_credit_id' => $credit->id, 'seat' => $credit->seats_repaid],
        );

        if ($credit->seats_repaid >= $credit->seats_owed) {
            $credit->update(['status' => RideCreditStatus::Repaid->value]);
        }

        $this->refreshOverdueFlag($driver);

        return $credit;
    }

    /**
     * A booking paid with ride credit was cancelled / no-showed — the debt is
     * void since the ride never happened.
     */
    public function cancelRideCredit(Booking $booking): void
    {
        RideCredit::where('booking_id', $booking->id)
            ->where('status', RideCreditStatus::Owed->value)
            ->update(['status' => RideCreditStatus::Waived->value]);
    }

    public function outstandingSeats(User $user): int
    {
        return (int) RideCredit::where('user_id', $user->id)
            ->where('status', RideCreditStatus::Owed->value)
            ->get()
            ->sum(fn (RideCredit $credit) => $credit->outstandingSeats());
    }

    private function hasOverdueCredit(User $user): bool
    {
        return RideCredit::where('user_id', $user->id)
            ->where('status', RideCreditStatus::Owed->value)
            ->where('due_date', '<', now())
            ->exists();
    }

    /**
     * Mark any owed credits past their due date as overdue and flag the user.
     */
    public function flagOverdue(User $user): void
    {
        $due = RideCredit::where('user_id', $user->id)
            ->where('status', RideCreditStatus::Owed->value)
            ->where('due_date', '<', now())
            ->get();

        if ($due->isNotEmpty()) {
            RideCredit::whereIn('id', $due->pluck('id'))->update(['status' => RideCreditStatus::Overdue->value]);
            $user->update(['has_overdue_ride_credit' => true]);
        }
    }

    private function refreshOverdueFlag(User $user): void
    {
        $stillOverdue = RideCredit::where('user_id', $user->id)
            ->where('status', RideCreditStatus::Overdue->value)
            ->exists();

        if (! $stillOverdue) {
            $user->update(['has_overdue_ride_credit' => false]);
        }
    }
}
