<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Validation\ValidationException;

/**
 * Dual-balance wallet: cash_balance + subsidy_credits.
 *
 * Subsidy credits are always spent first. Optimistic locking on the `version`
 * column prevents double-spend races. Every mutation writes an idempotent
 * transaction row keyed by a unique reference.
 */
class WalletService
{
    public function walletFor(User $user): Wallet
    {
        return $user->wallet ?? Wallet::create(['user_id' => $user->id]);
    }

    public function creditCash(User $user, float $amount, string $reference, ?string $description = null, array $meta = []): void
    {
        $this->credit($user, $amount, $reference, $description, $meta, TransactionType::Credit);
    }

    public function creditSubsidy(User $user, float $amount, string $reference, ?string $description = null, array $meta = []): void
    {
        $this->credit($user, $amount, $reference, $description, $meta, TransactionType::Subsidy);
    }

    /**
     * Hold the seat fare from the passenger's wallet (subsidy first).
     * The amount is debited from the spendable balances and recorded as a hold.
     */
    public function holdForBooking(Booking $booking): void
    {
        $wallet = $this->walletFor($booking->passenger);
        $amount = round((float) $booking->fare_paid, 2);

        if ($amount <= 0) {
            return;
        }

        $subsidy = round(min((float) $wallet->subsidy_credits, $amount), 2);
        $cash = round($amount - $subsidy, 2);

        if (round($subsidy + (float) $wallet->cash_balance, 2) < $amount) {
            throw ValidationException::withMessages([
                'payment_method' => 'Insufficient wallet balance. Please top up or pay with cash.',
            ]);
        }

        $this->debit($wallet, $subsidy, $cash);

        $wallet->transactions()->create([
            'type' => TransactionType::Hold,
            'amount' => $amount,
            'reference' => $this->holdReference($booking),
            'description' => "Seat hold — Trip #{$booking->trip_id}",
            'meta' => ['booking_id' => $booking->id, 'trip_id' => $booking->trip_id, 'subsidy' => $subsidy, 'cash' => $cash],
        ]);

        $wallet->refresh();
    }

    /**
     * Finalize a hold. Full capture by default; partial for no-shows.
     * The unconsumed portion is returned to the passenger's wallet.
     */
    public function captureForBooking(Booking $booking, ?float $captureAmount = null): void
    {
        $transaction = $this->holdTransaction($booking);

        if (! $transaction || $transaction->type !== TransactionType::Hold) {
            return;
        }

        $amount = round((float) $booking->fare_paid, 2);
        $capture = round(min(max($captureAmount ?? $amount, 0), $amount), 2);

        if ($capture >= $amount) {
            $transaction->update([
                'type' => TransactionType::Capture,
                'description' => "Fare captured — Trip #{$booking->trip_id}",
            ]);

            return;
        }

        if ($capture <= 0) {
            $this->releaseHold($booking, $transaction);

            return;
        }

        $refund = round($amount - $capture, 2);
        $this->restore($transaction->wallet, $transaction, $refund);

        $transaction->update([
            'type' => TransactionType::Capture,
            'amount' => $capture,
            'description' => "Partial capture (no-show) — Trip #{$booking->trip_id}",
            'meta' => array_merge($transaction->meta ?? [], ['captured' => $capture, 'refunded' => $refund]),
        ]);
    }

    /**
     * Release the full hold back to the passenger's wallet (booking cancelled).
     */
    public function releaseHold(Booking $booking, ?Transaction $transaction = null): void
    {
        $transaction ??= $this->holdTransaction($booking);

        if (! $transaction || $transaction->type !== TransactionType::Hold) {
            return;
        }

        $this->restore($transaction->wallet, $transaction, (float) $booking->fare_paid);

        $transaction->update([
            'type' => TransactionType::Refund,
            'description' => "Fare refunded — Trip #{$booking->trip_id}",
        ]);
    }

    /**
     * Record cash collected by the driver on the driver's wallet ledger.
     */
    public function logCashCollection(Booking $booking): void
    {
        $wallet = $this->walletFor($booking->trip->driver);
        $amount = round((float) $booking->fare_paid, 2);

        if ($amount <= 0) {
            return;
        }

        $updated = $wallet->newQuery()
            ->whereKey($wallet->id)
            ->where('version', $wallet->version)
            ->update([
                'cash_collected_log' => round((float) $wallet->cash_collected_log + $amount, 2),
                'version' => $wallet->version + 1,
            ]);

        if (! $updated) {
            throw ValidationException::withMessages(['trip' => 'Driver wallet changed concurrently. Please retry.']);
        }

        $wallet->refresh();

        $wallet->transactions()->create([
            'type' => TransactionType::Capture,
            'amount' => $amount,
            'reference' => "BOOK-{$booking->id}-CASH",
            'description' => "Cash collected — Trip #{$booking->trip_id}",
            'meta' => ['booking_id' => $booking->id, 'trip_id' => $booking->trip_id, 'payment_method' => PaymentMethod::Cash->value],
        ]);
    }

    public function holdTransaction(Booking $booking): ?Transaction
    {
        return Transaction::where('reference', $this->holdReference($booking))->first();
    }

    private function credit(User $user, float $amount, string $reference, ?string $description, array $meta, TransactionType $type): void
    {
        $amount = round($amount, 2);

        if ($amount <= 0 || Transaction::where('reference', $reference)->exists()) {
            return;
        }

        $wallet = $this->walletFor($user);

        $fields = ['version' => $wallet->version + 1];

        if ($type === TransactionType::Subsidy) {
            $fields['subsidy_credits'] = round((float) $wallet->subsidy_credits + $amount, 2);
        } else {
            $fields['cash_balance'] = round((float) $wallet->cash_balance + $amount, 2);
        }

        $updated = $wallet->newQuery()
            ->whereKey($wallet->id)
            ->where('version', $wallet->version)
            ->update($fields);

        if (! $updated) {
            throw ValidationException::withMessages(['wallet' => 'Wallet changed concurrently. Please retry.']);
        }

        $wallet->refresh();

        $wallet->transactions()->create([
            'type' => $type,
            'amount' => $amount,
            'reference' => $reference,
            'description' => $description,
            'meta' => $meta,
        ]);
    }

    private function debit(Wallet $wallet, float $subsidy, float $cash): void
    {
        $updated = $wallet->newQuery()
            ->whereKey($wallet->id)
            ->where('version', $wallet->version)
            ->update([
                'cash_balance' => round((float) $wallet->cash_balance - $cash, 2),
                'subsidy_credits' => round((float) $wallet->subsidy_credits - $subsidy, 2),
                'version' => $wallet->version + 1,
            ]);

        if (! $updated) {
            throw ValidationException::withMessages(['payment_method' => 'Wallet changed concurrently. Please retry.']);
        }
    }

    private function restore(Wallet $wallet, Transaction $transaction, float $amount): void
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            return;
        }

        $meta = $transaction->meta ?? [];
        $subsidyPart = round((float) ($meta['subsidy'] ?? 0), 2);
        $cashPart = round((float) ($meta['cash'] ?? 0), 2);
        $original = round((float) ($meta['subsidy'] ?? 0) + (float) ($meta['cash'] ?? 0), 2) ?: round((float) $transaction->amount, 2);

        $subsidyRefund = $original > 0 ? round(min($subsidyPart, $amount * ($subsidyPart / $original)), 2) : 0;
        $cashRefund = round($amount - $subsidyRefund, 2);

        $updated = $wallet->newQuery()
            ->whereKey($wallet->id)
            ->where('version', $wallet->version)
            ->update([
                'cash_balance' => round((float) $wallet->cash_balance + $cashRefund, 2),
                'subsidy_credits' => round((float) $wallet->subsidy_credits + $subsidyRefund, 2),
                'version' => $wallet->version + 1,
            ]);

        if (! $updated) {
            throw ValidationException::withMessages(['payment_method' => 'Wallet changed concurrently. Please retry.']);
        }

        $wallet->refresh();
    }

    private function holdReference(Booking $booking): string
    {
        return "BOOK-{$booking->id}-HOLD";
    }
}
