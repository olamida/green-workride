<?php

namespace App\Services;

use App\Enums\EmployerTransactionType;
use App\Models\Booking;
use App\Models\Employer;
use App\Models\EmployerTransaction;
use App\Models\EmployerWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Prepaid corporate billing wallet ledger (guide §2.2 stream #2/#4).
 *
 * Mirrors WalletService's optimistic locking + idempotent references so the
 * MDA/government audit trail can reconcile every naira the employer pays for
 * staff commutes: funding in, ride coverage out, refunds back.
 */
class EmployerLedgerService
{
    public function walletFor(Employer $employer): EmployerWallet
    {
        return $employer->wallet ?? EmployerWallet::create(['employer_id' => $employer->id]);
    }

    public function balance(Employer $employer): float
    {
        return round((float) $this->walletFor($employer)->cash_balance, 2);
    }

    public function assertAffordable(Employer $employer, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        if ($this->balance($employer) < $amount) {
            throw ValidationException::withMessages([
                'employer' => "{$employer->name} has insufficient mobility funds to cover this ride.",
            ]);
        }
    }

    /**
     * Credit the employer's prepaid wallet (admin funding / top-up).
     */
    public function fund(Employer $employer, float $amount, string $reference, ?string $description = null, array $meta = []): void
    {
        $this->credit($employer, round($amount, 2), $reference, $description, $meta, EmployerTransactionType::Funding);
    }

    /**
     * Debit the full employer contribution when a covered booking is boarded.
     */
    public function cover(Booking $booking): void
    {
        $this->debitBookingCoverage($booking, 100);
    }

    /**
     * Debit (or refund the remainder of) the employer contribution for a
     * no-show: the same no_show_capture_percent applies to both parties.
     */
    public function coverPartial(Booking $booking, float $capturePercent): void
    {
        $this->debitBookingCoverage($booking, $capturePercent);
    }

    /**
     * Return the employer contribution when a covered booking is cancelled.
     *
     * Only refunds money that actually left the employer wallet: coverage is
     * debited on boarding (COVER), so a booking cancelled while still
     * confirmed has nothing to refund. No-show partial refunds are already
     * credited inside coverPartial().
     */
    public function refund(Booking $booking): void
    {
        if (! $booking->employer_id || $booking->employer_contribution <= 0) {
            return;
        }

        $employer = $booking->employer;

        if (! $employer) {
            return;
        }

        $reference = "EMP-{$booking->id}-REFUND";

        if (EmployerTransaction::where('reference', $reference)->exists()) {
            return;
        }

        $covered = EmployerTransaction::where('reference', "EMP-{$booking->id}-COVER")->exists();

        if (! $covered) {
            return;
        }

        $this->credit($employer, round((float) $booking->employer_contribution, 2), $reference, "Coverage refund — Trip #{$booking->trip_id}", $this->bookingMeta($booking));
    }

    private function debitBookingCoverage(Booking $booking, float $capturePercent): void
    {
        if (! $booking->employer_id || $booking->employer_contribution <= 0) {
            return;
        }

        $employer = $booking->employer;
        $contribution = round((float) $booking->employer_contribution, 2);

        if (! $employer || $contribution <= 0) {
            return;
        }

        $capture = round($contribution * min(max($capturePercent, 0), 100) / 100, 2);
        $refund = round($contribution - $capture, 2);

        if ($capture > 0) {
            $this->debit(
                $employer,
                $capture,
                "EMP-{$booking->id}-COVER",
                "Ride coverage — Trip #{$booking->trip_id}",
                $this->bookingMeta($booking, ['captured' => $capture]),
            );
        }

        if ($refund > 0) {
            $this->credit(
                $employer,
                $refund,
                "EMP-{$booking->id}-REFUND",
                "Coverage refund (no-show) — Trip #{$booking->trip_id}",
                $this->bookingMeta($booking, ['refunded' => $refund]),
            );
        }
    }

    /**
     * @return array<string, int|float>
     */
    private function bookingMeta(Booking $booking, array $extra = []): array
    {
        return array_merge([
            'booking_id' => $booking->id,
            'trip_id' => $booking->trip_id,
            'passenger_id' => $booking->passenger_id,
            'fare' => (float) $booking->fare_paid,
            'employer_contribution' => (float) $booking->employer_contribution,
        ], $extra);
    }

    private function debit(Employer $employer, float $amount, string $reference, ?string $description, array $meta, ?EmployerTransactionType $type = null): void
    {
        if ($amount <= 0 || EmployerTransaction::where('reference', $reference)->exists()) {
            return;
        }

        $wallet = $this->walletFor($employer);

        DB::transaction(function () use ($wallet, $employer, $amount, $reference, $description, $meta, $type) {
            $wallet = EmployerWallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            if (round((float) $wallet->cash_balance - $amount, 2) < 0) {
                throw ValidationException::withMessages([
                    'employer' => "{$employer->name} has insufficient mobility funds for this ride.",
                ]);
            }

            $updated = $wallet->newQuery()
                ->whereKey($wallet->id)
                ->where('version', $wallet->version)
                ->update([
                    'cash_balance' => round((float) $wallet->cash_balance - $amount, 2),
                    'version' => $wallet->version + 1,
                ]);

            if (! $updated) {
                throw ValidationException::withMessages(['employer' => 'Employer wallet changed concurrently. Please retry.']);
            }

            $wallet->refresh();

            $wallet->transactions()->create([
                'type' => ($type ?? EmployerTransactionType::Cover)->value,
                'amount' => $amount,
                'reference' => $reference,
                'description' => $description,
                'meta' => $meta,
            ]);
        });
    }

    private function credit(Employer $employer, float $amount, string $reference, ?string $description, array $meta, ?EmployerTransactionType $type = null): void
    {
        if ($amount <= 0 || EmployerTransaction::where('reference', $reference)->exists()) {
            return;
        }

        $wallet = $this->walletFor($employer);

        DB::transaction(function () use ($wallet, $amount, $reference, $description, $meta, $type) {
            $wallet = EmployerWallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $updated = $wallet->newQuery()
                ->whereKey($wallet->id)
                ->where('version', $wallet->version)
                ->update([
                    'cash_balance' => round((float) $wallet->cash_balance + $amount, 2),
                    'version' => $wallet->version + 1,
                ]);

            if (! $updated) {
                throw ValidationException::withMessages(['employer' => 'Employer wallet changed concurrently. Please retry.']);
            }

            $wallet->refresh();

            $wallet->transactions()->create([
                'type' => ($type ?? EmployerTransactionType::Funding)->value,
                'amount' => $amount,
                'reference' => $reference,
                'description' => $description,
                'meta' => $meta,
            ]);
        });
    }
}
